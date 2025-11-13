<?php

declare(strict_types=1);

namespace Nexus\Sequencing;

use Nexus\Sequencing\Contracts\PatternParserContract;
use Nexus\Sequencing\Contracts\SequenceRepositoryContract;
use Nexus\Sequencing\Http\Middleware\InjectTenantContext;
use Nexus\Sequencing\Models\Sequence;
use Nexus\Sequencing\Policies\SequencePolicy;
use Nexus\Sequencing\Repositories\DatabaseSequenceRepository;
use Nexus\Sequencing\Services\PatternParserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Serial Numbering Service Provider
 *
 * Registers package services, bindings, and routes.
 */
class SequencingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/serial-numbering.php',
            'serial-numbering'
        );

        // Bind SequenceRepository contract to implementation
        $this->app->singleton(
            SequenceRepositoryContract::class,
            DatabaseSequenceRepository::class
        );

        // Bind PatternParser contract to implementation
        $this->app->singleton(
            PatternParserContract::class,
            PatternParserService::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/serial-numbering.php' => config_path('serial-numbering.php'),
        ], 'serial-numbering-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('tenant.context', InjectTenantContext::class);

        // Register policies
        Gate::policy(Sequence::class, SequencePolicy::class);

        // Define Gates for sequence operations
        Gate::define('manage-sequences', function ($user) {
            return $user->hasPermissionTo('manage-sequences');
        });

        Gate::define('reset-sequence', function ($user) {
            return $user->hasPermissionTo('reset-sequence');
        });

        Gate::define('override-sequence-number', function ($user) {
            return $user->hasPermissionTo('override-sequence-number');
        });
    }
}
