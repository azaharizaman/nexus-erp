# Setup and Usage Guide

This package ships with migrations, configuration, and runtime services that can be wired into any Laravel application via Testbench-compatible tooling. The sections below walk through installation, configuration, and the primary runtime capabilities.

## Installation

```bash
composer require azaharizaman/laravel-uom-management
```

Laravel auto-discovers the service provider. No manual registration is required, but you may publish assets to customise behaviour.

## Publishing Assets

```bash
php artisan vendor:publish --provider="Azaharizaman\\LaravelUomManagement\\LaravelUomManagementServiceProvider" --tag=laravel-uom-management-config
php artisan vendor:publish --provider="Azaharizaman\\LaravelUomManagement\\LaravelUomManagementServiceProvider" --tag=laravel-uom-management-migrations
php artisan vendor:publish --provider="Azaharizaman\\LaravelUomManagement\\LaravelUomManagementServiceProvider" --tag=laravel-uom-management-seeders
```

- **Config (`config/uom.php`)** controls precision, alias behaviour, packaging depth, logging and seeder defaults.
- **Migration** publishes the schema so you can customise tables as needed.
- **Seeder** makes the baseline dataset available for application-level seeding.

## Running Migrations and Seeders

```bash
php artisan migrate
php artisan uom:seed
```

The `uom:seed` command resolves the configured seeder class (defaulting to `Azaharizaman\LaravelUomManagement\Database\Seeders\UomDatabaseSeeder`) and pipes arguments through to Laravel's `db:seed` command.

## Service Container Bindings

The service provider exposes the following bindings:

| Binding | Alias | Description |
| --- | --- | --- |
| `Azaharizaman\LaravelUomManagement\Contracts\UnitConverter` | `uom.converter` | Core conversion service built on `brick/math`. |
| `Azaharizaman\LaravelUomManagement\Contracts\AliasResolver` | `uom.aliases` | Resolves unit identifiers and alias metadata. |
| `Azaharizaman\LaravelUomManagement\Contracts\CompoundUnitConverter` | `uom.compound` | Converts compound units by dimensional signature. |
| `Azaharizaman\LaravelUomManagement\Contracts\PackagingCalculator` | `uom.packaging` | Translates between base-unit quantities and packaged units. |
| `Azaharizaman\LaravelUomManagement\Contracts\CustomUnitRegistrar` | `uom.custom-units` | Registers owner-scoped custom units and conversion rules. |

## Conversion Examples

```php
use Azaharizaman\LaravelUomManagement\Contracts\UnitConverter;
use Azaharizaman\LaravelUomManagement\Contracts\PackagingCalculator;

$converter = app(UnitConverter::class);
$value = $converter->convert('2.5', 'KG', 'G', 3); // 2500.000

$packaging = app(PackagingCalculator::class)->resolvePackaging('G', 'KG');
$packages = app(PackagingCalculator::class)->baseToPackages('2500', $packaging, 2); // 2.50
```

The converter accepts numeric strings, integers, floats, or `Brick\Math\BigDecimal` instances. Precision defaults to the unit configuration but can be overridden per call.

## Alias Resolution

```php
use Azaharizaman\LaravelUomManagement\Contracts\AliasResolver;

$resolver = app(AliasResolver::class);
$unit = $resolver->resolveOrFail('kilo');

$aliases = $resolver->aliasesFor('KG');
// Returns ["KG", "kilo"] with preferred aliases first when configured.
```

## Custom Unit Registration

```php
use Azaharizaman\LaravelUomManagement\Contracts\CustomUnitRegistrar;
use Azaharizaman\LaravelUomManagement\Models\UomType;

$registrar = app(CustomUnitRegistrar::class);
$massType = UomType::query()->where('slug', 'mass')->firstOrFail();

$box = $registrar->register([
    'code' => 'BX',
    'name' => 'Box',
    'uom_type_id' => $massType->getKey(),
    'conversion_factor' => '0.5',
]);

$crate = $registrar->register([
    'code' => 'CR',
    'name' => 'Crate',
    'uom_type_id' => $massType->getKey(),
    'conversion_factor' => '2',
], null, [
    ['target' => 'BX', 'factor' => '4']
]);
```

The registrar enforces code uniqueness per owner, prevents zero conversion factors, and blocks custom formulas when disabled in configuration.

## Available Artisan Commands

| Command | Description |
| --- | --- |
| `php artisan uom:seed` | Seed the baseline UOM dataset using the configured seeder. |
| `php artisan uom:convert 12 kg g` | Convert quantities between units. Supports `--precision` to override output scale. |
| `php artisan uom:units [type]` | List units, optionally filtered by type, with `--aliases` to include alias metadata. |

These commands rely on the package's service bindings, so they will respect any overrides you register in your container.

## Configuration Overview

Key options available in `config/uom.php`:

- `conversion.default_precision` and `conversion.math_scale` govern output rounding.
- `conversion.allow_custom_formulas` toggles support for non-linear custom conversions.
- `aliases.preferred_first` controls alias ordering.
- `packaging.max_depth` and `packaging.enforce_unique_paths` safeguard packaging recursion.
- `logging.enabled` and `logging.immutable` reserve space for future auditing features.
- `seeders.class` and `seeders.publish_tag` define seeding defaults.

Adjust these values before publishing if you need to change the defaults across environments.
