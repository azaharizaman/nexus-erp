<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Nexus\Workflow\WorkflowServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironmentSetup($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
