<?php

declare(strict_types=1);

namespace Nexus\Workflow;

use Illuminate\Support\ServiceProvider;
use Nexus\Workflow\Contracts\WorkflowEngineContract;
use Nexus\Workflow\Engines\DatabaseWorkflowEngine;
use Nexus\Workflow\Console\Commands\WorkflowListCommand;
use Nexus\Workflow\Console\Commands\WorkflowImportCommand;
use Nexus\Workflow\Console\Commands\WorkflowExportCommand;
use Nexus\Workflow\Console\Commands\WorkflowActivateCommand;
use Nexus\Workflow\Console\Commands\WorkflowDeactivateCommand;
use Nexus\Workflow\Console\Commands\WorkflowShowCommand;

/**
 * Workflow Service Provider
 * 
 * Registers the nexus-workflow package with Laravel.
 * Auto-discovered by Laravel's package discovery.
 * 
 * Phase 1: Basic workflow engine and state machine
 * Phase 2: Database-driven workflows, multi-approver support, task management
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

        // Register Phase 2 Database Workflow Engine
        $this->app->bind(WorkflowEngineContract::class, function ($app) {
            $engine = config('workflow.engine', 'database');
            
            if ($engine === 'database') {
                return new DatabaseWorkflowEngine(
                    $app->make(\Nexus\Workflow\Core\Services\StateTransitionService::class)
                );
            }
            
            // Default to database engine
            return new DatabaseWorkflowEngine(
                $app->make(\Nexus\Workflow\Core\Services\StateTransitionService::class)
            );
        });

        // Register singleton for DatabaseWorkflowEngine
        $this->app->singleton(DatabaseWorkflowEngine::class, function ($app) {
            return new DatabaseWorkflowEngine(
                $app->make(\Nexus\Workflow\Core\Services\StateTransitionService::class)
            );
        });
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

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'workflow-migrations');

            // Register Artisan commands (Phase 2)
            $this->commands([
                WorkflowListCommand::class,
                WorkflowImportCommand::class,
                WorkflowExportCommand::class,
                WorkflowActivateCommand::class,
                WorkflowDeactivateCommand::class,
                WorkflowShowCommand::class,
            ]);
        }

        // Load migrations (for package development and testing)
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
