<?php

declare(strict_types=1);

namespace Nexus\Atomy\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Atomy\Crm\Contracts\NexusCrmAdapterInterface;
use Nexus\Atomy\Crm\Adapters\NexusCrmAdapter;

/**
 * Nexus CRM Orchestration provider.
 *
 * This provider lives in the `nexus-erp` package and binds ERP-specific
 * adapters for `nexus-crm` to avoid polluting the atomic package.
 */
class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The adapter binds orchestration implementations
        $this->app->bind(NexusCrmAdapterInterface::class, NexusCrmAdapter::class);
    }

    public function boot(): void
    {
        // Boot orchestration-specific registration: commands, observers, etc.
    }
}
