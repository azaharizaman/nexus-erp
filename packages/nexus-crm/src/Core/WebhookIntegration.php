<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nexus\Crm\Contracts\IntegrationContract;
use Nexus\Crm\Models\CrmEntity;

/**
 * Webhook Integration
 *
 * Sends webhooks as part of CRM pipeline actions.
 */
class WebhookIntegration implements IntegrationContract
{
    /**
     * Execute webhook integration.
     */
    public function execute(CrmEntity $entity, array $config, array $context = []): void
    {
        $url = $config['url'] ?? '';
        $method = $config['method'] ?? 'POST';
        $headers = $config['headers'] ?? [];
        $retries = $config['retries'] ?? 3;

        if (!$url) {
            throw new \InvalidArgumentException('Webhook URL is required');
        }

        // Security: Validate URL against whitelist
        $this->validateWebhookUrl($url);

        // Security: Filter sensitive data before sending
        $payload = [
            'entity' => $this->filterSensitiveData($entity->toArray()),
            'context' => $this->filterSensitiveData($context),
            'timestamp' => now()->toISOString(),
        ];

        // Security: Sign payload if secret is configured
        if (!empty($config['secret'])) {
            $headers['X-Webhook-Signature'] = $this->signPayload($payload, $config['secret']);
        }

        $lastException = null;
        
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::withHeaders($headers)
                    ->timeout(10)
                    ->retry($retries, 100) // Built-in retry with exponential backoff
                    ->send($method, $url, ['json' => $payload]);

                if ($response->successful()) {
                    // Success - emit success event
                    event(new \Nexus\Crm\Events\WebhookDeliveredEvent($entity, $url, $payload));
                    return;
                }

                // Non-successful HTTP status
                Log::warning('CRM Webhook returned non-success status', [
                    'entity_id' => $entity->id,
                    'url' => $url,
                    'status' => $response->status(),
                    'attempt' => $attempt,
                ]);
                
                $lastException = new \RuntimeException("Webhook returned status {$response->status()}");
                
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Transient network error - retry
                $lastException = $e;
                Log::warning('CRM Webhook connection failed (will retry)', [
                    'entity_id' => $entity->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);
                
                if ($attempt < $retries) {
                    usleep(100000 * $attempt); // Exponential backoff
                }
                
            } catch (\Exception $e) {
                // Configuration error or other critical failure - don't retry
                Log::error('CRM Webhook Integration Failed (critical)', [
                    'entity_id' => $entity->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);
                
                // Emit failure event for monitoring
                event(new \Nexus\Crm\Events\WebhookFailedEvent($entity, $url, $e->getMessage()));
                
                throw $e; // Re-throw critical errors
            }
        }

        // All retries exhausted - store for later retry
        Log::error('CRM Webhook Integration Failed after retries', [
            'entity_id' => $entity->id,
            'url' => $url,
            'error' => $lastException?->getMessage(),
            'attempts' => $retries,
        ]);

        // Emit failure event for monitoring
        event(new \Nexus\Crm\Events\WebhookFailedEvent($entity, $url, $lastException?->getMessage() ?? 'Unknown error'));
        
        // TODO: Store in failed_webhooks table for manual retry/investigation
    }

    /**
     * Validate webhook URL against whitelist.
     */
    private function validateWebhookUrl(string $url): void
    {
        $whitelist = config('crm.webhook_whitelist', []);

        // Validate URL format first
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \RuntimeException("Invalid webhook URL format: {$url}");
        }

        $parsedUrl = parse_url($url);
        if ($parsedUrl === false) {
            throw new \RuntimeException("Invalid webhook URL: {$url}");
        }
        $host = $parsedUrl['host'] ?? '';
        if ($host === '') {
            throw new \RuntimeException("Webhook URL '{$url}' does not contain a valid host");
        }

        if (empty($whitelist)) {
            // Still block private IPs and localhost for security
            if ($this->isPrivateOrLocalhost($host)) {
                throw new \RuntimeException("Webhook to private/local addresses not allowed: {$host}");
            }
            return;
        }

        foreach ($whitelist as $allowedPattern) {
            if (fnmatch($allowedPattern, $host)) {
                return; // URL is whitelisted
            }
        }

        throw new \RuntimeException("Webhook URL '{$url}' is not whitelisted");
    }

    /**
     * Check if host is private or localhost.
     */
    private function isPrivateOrLocalhost(string $host): bool
    {
        // Check for localhost
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return true;
        }

        // Resolve hostname to IP if needed
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        
        // Check if it's a valid IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Check for private IP ranges
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Filter sensitive data from payload.
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = config('crm.webhook_sensitive_fields', [
            'password',
            'secret',
            'token',
            'api_key',
            'credit_card',
            'ssn',
        ]);

        return $this->recursiveFilter($data, $sensitiveFields);
    }

    /**
     * Recursively filter sensitive fields from array.
     */
    private function recursiveFilter(array $data, array $sensitiveFields): array
    {
        foreach ($data as $key => $value) {
            // Check if key matches any sensitive field pattern
            foreach ($sensitiveFields as $field) {
                if (stripos($key, $field) !== false) {
                    $data[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            // Recursively filter nested arrays
            if (is_array($value)) {
                $data[$key] = $this->recursiveFilter($value, $sensitiveFields);
            }
        }

        return $data;
    }

    /**
     * Sign webhook payload with HMAC.
     */
    private function signPayload(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        return hash_hmac('sha256', $json, $secret);
    }

    /**
     * Compensate webhook integration (no-op for webhooks).
     */
    public function compensate(CrmEntity $entity, array $config, array $context = []): void
    {
        // Webhooks don't need compensation
    }
}