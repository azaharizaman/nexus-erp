<?php

declare(strict_types=1);

namespace Nexus\Erp\Crm\Contracts;

use Nexus\Crm\Core\DTOs\CrmDefinition;

interface NexusCrmAdapterInterface
{
    /**
     * Sync a CRM definition to the CRM core (e.g., register a definition)
     */
    public function syncDefinition(CrmDefinition $definition): void;

    /**
     * Process timers for a tenant/instance and return number processed
     */
    public function processTimersForTenant(string $tenantId): int;
}
