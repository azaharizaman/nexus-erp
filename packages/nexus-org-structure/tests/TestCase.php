<?php

declare(strict_types=1);

namespace Nexus\OrgStructure\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Nexus\OrgStructure\OrgStructureServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Eloquent connection resolver is set
        \Illuminate\Database\Eloquent\Model::setConnectionResolver($this->app['db']);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load factories
        $this->withFactories(__DIR__ . '/../database/factories');
    }

    protected function getPackageProviders($app): array
    {
        return [
            OrgStructureServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use in-memory SQLite for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}