<?php

declare(strict_types=1);

namespace Nexus\Atomy\Providers;

use Nexus\Atomy\Support\Contracts\SearchServiceContract;
use Nexus\Atomy\Support\Services\Search\ScoutSearchService;
use Illuminate\Support\ServiceProvider;

/**
 * Search Service Provider
 *
 * Registers search service bindings.
 * Abstracts the search implementation from business logic.
 */
class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(SearchServiceContract::class, function ($app) {
            $driver = config('packages.search_driver', 'scout');

            return match ($driver) {
                'scout' => new ScoutSearchService(),
                // Future implementations can be added here:
                // 'database' => new DatabaseSearchService(),
                // 'meilisearch' => new MeilisearchSearchService(),
                // 'null' => new NullSearchService(),
                default => new ScoutSearchService(),
            };
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
