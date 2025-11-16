<?php

declare(strict_types=1);

namespace Nexus\Atomy\Crm\Adapters;

use Nexus\Atomy\Crm\Contracts\NexusCrmAdapterInterface;
use Nexus\Crm\Core\DTOs\CrmDefinition;
use Nexus\Crm\Core\Engine\CrmEngine;
use Nexus\Tenancy\Contracts\TenantManagerContract;
use Nexus\Atomy\Support\Contracts\ActivityLoggerContract as ErpActivityLoggerContract;

class NexusCrmAdapter implements NexusCrmAdapterInterface
{
    public function __construct(protected CrmEngine $engine)
    {
    }

    public function syncDefinition(CrmDefinition $definition): void
    {
        // Orchestrator-specific sync logic. For now, register by id via engine.
        // The engine might not be fully implemented in some contexts; we guard by method_exists.
        if (method_exists($this->engine, 'getDefinitionRegistry')) {
            $registry = $this->engine->getDefinitionRegistry();
            $registry->register($definition->id, $definition->toArray());
        }

        // If tenant manager is available, record a sync activity for the tenant
        if (interface_exists(TenantManagerContract::class) && app()->bound(TenantManagerContract::class)) {
            $tenantManager = app(TenantManagerContract::class);
            $tenant = method_exists($tenantManager, 'current') ? $tenantManager->current() : null;

            if ($tenant && interface_exists(ErpActivityLoggerContract::class) && app()->bound(ErpActivityLoggerContract::class)) {
                $logger = app(ErpActivityLoggerContract::class);
                // Record sync action (adapter is not authoritative for log format)
                $logger->log(
                    'crm.definition.synced',
                    $tenant,
                    null,
                    [
                        'tenant_id' => $tenant->id ?? null,
                        'definition' => $definition->id,
                    ],
                    'crm'
                );
            }
        }
    }

    public function processTimersForTenant(string $tenantId): int
    {
        if (method_exists($this->engine, 'getTimerProcessor')) {
            $processor = $this->engine->getTimerProcessor();
            if (method_exists($processor, 'processDueTimersForTenant')) {
                return $processor->processDueTimersForTenant($tenantId);
            }
        }

        return 0; // no-op by default
    }
}
