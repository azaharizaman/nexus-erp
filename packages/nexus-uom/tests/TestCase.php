<?php

namespace Nexus\Uom\Tests;

use Nexus\Uom\Database\Seeders\UomDatabaseSeeder;
use Nexus\Uom\LaravelUomManagementServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    private ?object $packageMigration = null;

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [LaravelUomManagementServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->runPackageMigration();
    }

    protected function tearDown(): void
    {
        if ($this->packageMigration !== null) {
            $this->packageMigration->down();
            $this->packageMigration = null;
        }

        parent::tearDown();
    }

    protected function seedBaselineDataset(): void
    {
        $seeder = new UomDatabaseSeeder();
        $seeder->setContainer($this->app);
        $seeder->run();
    }

    private function runPackageMigration(): void
    {
        $migrationPath = __DIR__ . '/../database/migrations/create_uom_tables.php';

        $migration = require $migrationPath;
        $migration->up();

        $this->packageMigration = $migration;
    }
}
