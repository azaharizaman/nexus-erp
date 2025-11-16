<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base Action Class for Nexus ERP
 * 
 * Provides common patterns for business logic orchestration:
 * - Database transaction handling
 * - Error logging
 * - Result standardization
 * - Validation patterns
 */
abstract class Action
{
    /**
     * Execute the action with automatic database transaction handling.
     * 
     * @param mixed ...$parameters
     * @return mixed
     * @throws Throwable
     */
    public function execute(...$parameters)
    {
        try {
            return $this->useTransactions() 
                ? DB::transaction(fn() => $this->handle(...$parameters))
                : $this->handle(...$parameters);
        } catch (Throwable $e) {
            $this->handleException($e, $parameters);
            throw $e;
        }
    }

    /**
     * The main business logic implementation.
     * 
     * @param mixed ...$parameters
     * @return mixed
     */
    abstract public function handle(...$parameters);

    /**
     * Whether this action should run within a database transaction.
     * 
     * @return bool
     */
    protected function useTransactions(): bool
    {
        return true;
    }

    /**
     * Handle exceptions that occur during action execution.
     * 
     * @param Throwable $exception
     * @param array $parameters
     * @return void
     */
    protected function handleException(Throwable $exception, array $parameters): void
    {
        Log::error('Action execution failed', [
            'action' => static::class,
            'parameters' => $this->sanitizeParametersForLogging($parameters),
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Sanitize parameters for safe logging (remove sensitive data).
     * 
     * @param array $parameters
     * @return array
     */
    protected function sanitizeParametersForLogging(array $parameters): array
    {
        // Remove sensitive fields from logging
        $sensitiveFields = ['password', 'token', 'secret', 'api_key'];
        
        return array_map(function ($param) use ($sensitiveFields) {
            if (is_array($param)) {
                foreach ($sensitiveFields as $field) {
                    if (isset($param[$field])) {
                        $param[$field] = '[REDACTED]';
                    }
                }
            }
            return $param;
        }, $parameters);
    }

    /**
     * Create a standardized success result.
     * 
     * @param mixed $data
     * @param string|null $message
     * @return array
     */
    protected function success($data = null, ?string $message = null): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];
    }

    /**
     * Create a standardized error result.
     * 
     * @param string $message
     * @param mixed $errors
     * @return array
     */
    protected function error(string $message, $errors = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];
    }

    /**
     * Validate required parameters.
     * 
     * @param array $data
     * @param array $requiredFields
     * @throws \InvalidArgumentException
     */
    protected function validateRequired(array $data, array $requiredFields): void
    {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }
}