<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Contracts\IntegrationContract;

/**
 * Integration Manager
 *
 * Manages integrations with external services like email, webhooks, etc.
 */
class IntegrationManager
{
    /**
     * Available integrations.
     */
    private array $integrations = [
        'email' => EmailIntegration::class,
        'webhook' => WebhookIntegration::class,
    ];

    /**
     * Execute an integration.
     */
    public function execute(string $type, CrmEntity $entity, array $config, array $context = []): void
    {
        $integrationClass = $this->integrations[$type] ?? null;

        if (!$integrationClass) {
            throw new \InvalidArgumentException("Unknown integration type: {$type}");
        }

        $integration = app($integrationClass);

        if (!$integration instanceof IntegrationContract) {
            throw new \InvalidArgumentException("Integration {$type} must implement IntegrationContract");
        }

        $integration->execute($entity, $config, $context);
    }

    /**
     * Register a custom integration.
     */
    public function registerIntegration(string $type, string $class): void
    {
        $this->integrations[$type] = $class;
    }
}