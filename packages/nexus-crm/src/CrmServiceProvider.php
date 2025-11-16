<?php

declare(strict_types=1);

namespace Nexus\Crm;

use Illuminate\Support\ServiceProvider;

/**
 * CRM Service Provider
 *
 * Registers CRM services and bindings for customer relationship management
 * functionality in the Nexus ERP ecosystem.
 */
class CrmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/crm.php',
            'crm'
        );

        // Register core CRM services
        $this->registerCoreServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/crm.php' => config_path('crm.php'),
        ], 'crm-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register commands if any
        if ($this->app->runningInConsole()) {
            // Commands will be registered here in future phases
        }
    }

    /**
     * Register core CRM services.
     */
    private function registerCoreServices(): void
    {
        // Register pipeline engine and related services
        $this->app->singleton(\Nexus\Crm\Core\PipelineEngine::class, function ($app) {
            return new \Nexus\Crm\Core\PipelineEngine(
                $app->make(\Nexus\Crm\Core\ConditionEvaluatorManager::class),
                $app->make(\Nexus\Crm\Core\AssignmentStrategyResolver::class),
                $app->make(\Nexus\Crm\Core\IntegrationManager::class)
            );
        });

        $this->app->singleton(\Nexus\Crm\Core\ConditionEvaluatorManager::class, function ($app) {
            $manager = new \Nexus\Crm\Core\ConditionEvaluatorManager();
            // Register default evaluator
            // For now, it's built-in, but can be extended
            return $manager;
        });
        $this->app->singleton(\Nexus\Crm\Core\AssignmentStrategyResolver::class);
        $this->app->singleton(\Nexus\Crm\Core\IntegrationManager::class);

        // Register dashboard service
        $this->app->singleton(\Nexus\Crm\Services\CrmDashboard::class);
    }
}