<?php

declare(strict_types=1);

namespace Nexus\Sequencing;

use Nexus\Sequencing\Contracts\PatternParserContract;
use Nexus\Sequencing\Contracts\SequenceRepositoryContract;
use Nexus\Sequencing\Core\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Core\Contracts\PatternEvaluatorInterface;
use Nexus\Sequencing\Core\Contracts\ResetStrategyInterface;
use Nexus\Sequencing\Core\Contracts\VariableRegistryInterface;
use Nexus\Sequencing\Core\Contracts\ConditionalProcessorInterface;
use Nexus\Sequencing\Core\Services\GenerationService;
use Nexus\Sequencing\Core\Services\ValidationService;
use Nexus\Sequencing\Core\Engine\RegexPatternEvaluator;
use Nexus\Sequencing\Core\Engine\VariableRegistry;
use Nexus\Sequencing\Core\Engine\BasicConditionalProcessor;
use Nexus\Sequencing\Core\Engine\TemplateRegistry;
use Nexus\Sequencing\Core\Templates\Financial\InvoiceTemplate;
use Nexus\Sequencing\Core\Templates\Financial\QuoteTemplate;
use Nexus\Sequencing\Core\Templates\Procurement\PurchaseOrderTemplate;
use Nexus\Sequencing\Core\Templates\HR\EmployeeIdTemplate;
use Nexus\Sequencing\Core\Templates\Inventory\StockTransferTemplate;
use Nexus\Sequencing\Core\Variables\DepartmentVariable;
use Nexus\Sequencing\Core\Variables\ProjectCodeVariable;
use Nexus\Sequencing\Core\Variables\CustomerTierVariable;
use Nexus\Sequencing\Core\Services\DefaultResetStrategy;
use Nexus\Sequencing\Adapters\Laravel\EloquentCounterRepository;
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

        // Bind Core service contracts to implementations
        $this->app->singleton(
            CounterRepositoryInterface::class,
            EloquentCounterRepository::class
        );

        // Bind Variable Registry
        $this->app->singleton(
            VariableRegistryInterface::class,
            VariableRegistry::class
        );

        // Bind Conditional Processor
        $this->app->singleton(
            ConditionalProcessorInterface::class,
            BasicConditionalProcessor::class
        );

        // Bind Template Registry and register built-in templates
        $this->app->singleton(TemplateRegistry::class, function () {
            $registry = new TemplateRegistry();
            
            // Register built-in templates (Phase 2.3)
            $registry->register(new InvoiceTemplate());
            $registry->register(new QuoteTemplate());
            $registry->register(new PurchaseOrderTemplate());
            $registry->register(new EmployeeIdTemplate());
            $registry->register(new StockTransferTemplate());
            
            return $registry;
        });

        // Bind PatternEvaluator with dependencies
        $this->app->singleton(
            PatternEvaluatorInterface::class,
            function ($app) {
                return new RegexPatternEvaluator(
                    $app->make(VariableRegistryInterface::class),
                    $app->make(ConditionalProcessorInterface::class)
                );
            }
        );

        $this->app->singleton(
            ResetStrategyInterface::class,
            DefaultResetStrategy::class
        );

        // Register Core GenerationService (depends on Core contracts)
        $this->app->singleton(GenerationService::class);

        // Register Core ValidationService
        $this->app->singleton(ValidationService::class);
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

        // Register custom variables (Phase 2.3)
        $this->registerCustomVariables();

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

    /**
     * Register custom variables with the variable registry.
     */
    private function registerCustomVariables(): void
    {
        $variableRegistry = $this->app->make(VariableRegistryInterface::class);

        // Register Phase 2.3 example custom variables
        $variableRegistry->register(new DepartmentVariable());
        $variableRegistry->register(new ProjectCodeVariable());
        $variableRegistry->register(new CustomerTierVariable());
    }
}
