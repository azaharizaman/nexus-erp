<?php

declare(strict_types=1);

namespace Nexus\Tenancy;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Nexus\Tenancy\Contracts\TenantManagerContract;
use Nexus\Tenancy\Contracts\TenantRepositoryContract;
use Nexus\Tenancy\Http\Middleware\EnsureTenantActive;
use Nexus\Tenancy\Http\Middleware\IdentifyTenant;
use Nexus\Tenancy\Models\Tenant;
use Nexus\Tenancy\Repositories\TenantRepository;
use Nexus\Tenancy\Services\ImpersonationService;
use Nexus\Tenancy\Services\TenantManager;

/**
 * Tenancy Management Service Provider
 *
 * Registers multi-tenancy services and bindings for tenant isolation,
 * context management, and tenant impersonation functionality.
 */
class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/tenancy.php',
            'tenancy'
        );

        // Bind TenantRepository contract to implementation
        $this->app->singleton(TenantRepositoryContract::class, TenantRepository::class);

        // Bind TenantManager contract to implementation
        $this->app->singleton(TenantManagerContract::class, TenantManager::class);

        // Bind ImpersonationService
        $this->app->singleton(ImpersonationService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/tenancy.php' => config_path('tenancy.php'),
        ], 'tenancy-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'tenancy-migrations');

        // Register policies
        Gate::policy(Tenant::class, \Nexus\TenancyManagement\Policies\TenantPolicy::class);

        // Register middleware
        $this->app['router']->aliasMiddleware('tenant', IdentifyTenant::class);
        $this->app['router']->aliasMiddleware('tenant.active', EnsureTenantActive::class);
    }
}
