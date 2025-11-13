<?php

namespace Nexus\UomManagement;

use Nexus\UomManagement\Console\Commands\UomConvertCommand;
use Nexus\UomManagement\Console\Commands\UomListUnitsCommand;
use Nexus\UomManagement\Console\Commands\UomSeedCommand;
use Nexus\UomManagement\Contracts\AliasResolver as AliasResolverContract;
use Nexus\UomManagement\Contracts\CompoundUnitConverter as CompoundUnitConverterContract;
use Nexus\UomManagement\Contracts\CustomUnitRegistrar as CustomUnitRegistrarContract;
use Nexus\UomManagement\Contracts\PackagingCalculator as PackagingCalculatorContract;
use Nexus\UomManagement\Contracts\UnitConverter as UnitConverterContract;
use Nexus\UomManagement\Database\Seeders\UomDatabaseSeeder;
use Nexus\UomManagement\Services\DefaultAliasResolver;
use Nexus\UomManagement\Services\DefaultCompoundUnitConverter;
use Nexus\UomManagement\Services\DefaultCustomUnitRegistrar;
use Nexus\UomManagement\Services\DefaultPackagingCalculator;
use Nexus\UomManagement\Services\DefaultUnitConverter;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UomManagementServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-uom-management')
            ->hasConfigFile('uom')
            ->hasMigration('create_uom_tables')
            ->hasCommands([
                UomSeedCommand::class,
                UomConvertCommand::class,
                UomListUnitsCommand::class,
            ]);
    }

    public function bootingPackage(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/seeders/UomDatabaseSeeder.php' => database_path('seeders/UomDatabaseSeeder.php'),
            ], 'laravel-uom-management-seeders');
        }
    }

    public function registeringPackage(): void
    {
        $this->app->bindIf('uom.database.seeder', fn () => UomDatabaseSeeder::class);

        $this->app->singleton(UnitConverterContract::class, function ($app) {
            return new DefaultUnitConverter($app->make(ConfigRepository::class));
        });
        $this->app->alias(UnitConverterContract::class, 'uom.converter');

        $this->app->singleton(AliasResolverContract::class, DefaultAliasResolver::class);
        $this->app->alias(AliasResolverContract::class, 'uom.aliases');

        $this->app->singleton(CompoundUnitConverterContract::class, function ($app) {
            return new DefaultCompoundUnitConverter(
                $app->make(UnitConverterContract::class),
                $app->make(ConfigRepository::class)
            );
        });
        $this->app->alias(CompoundUnitConverterContract::class, 'uom.compound');

        $this->app->singleton(PackagingCalculatorContract::class, function ($app) {
            return new DefaultPackagingCalculator(
                $app->make(AliasResolverContract::class),
                $app->make(ConfigRepository::class)
            );
        });
        $this->app->alias(PackagingCalculatorContract::class, 'uom.packaging');

        $this->app->singleton(CustomUnitRegistrarContract::class, function ($app) {
            return new DefaultCustomUnitRegistrar(
                $app->make(DatabaseManager::class),
                $app->make(ConfigRepository::class)
            );
        });
        $this->app->alias(CustomUnitRegistrarContract::class, 'uom.custom-units');
    }
}
