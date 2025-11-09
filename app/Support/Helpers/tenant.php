<?php

declare(strict_types=1);

use App\Domains\Core\Contracts\TenantManagerContract;
use App\Domains\Core\Models\Tenant;

if (! function_exists('tenant')) {
    /**
     * Get the current active tenant from context
     *
     * Convenience helper to access the current tenant set by IdentifyTenant middleware.
     * Returns null if no tenant is set in the current request context.
     */
    function tenant(): ?Tenant
    {
        $tenantManager = app(TenantManagerContract::class);

        return $tenantManager->current();
    }
}
