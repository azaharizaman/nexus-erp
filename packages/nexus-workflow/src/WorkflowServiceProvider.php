<?php

declare(strict_types=1);

namespace Nexus\Workflow;

use Illuminate\Support\ServiceProvider;

/**
 * Workflow Service Provider
 * 
 * Registers the nexus-workflow package with Laravel.
 * Auto-discovered by Laravel's package discovery.
 */
final class WorkflowServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/workflow.php',
            'workflow'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/workflow.php' => config_path('workflow.php'),
            ], 'workflow-config');
        }
    }
}
