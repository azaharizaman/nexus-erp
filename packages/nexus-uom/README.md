# Laravel UOM Management

A Laravel package that centralises unit-of-measure (UOM) management, allowing applications to define unit types, register custom units, execute precise conversions, and manage packaging relationships with confidence.

## Requirements

- PHP 8.2 or newer
- Laravel 10, 11, or 13 (13.0.x-dev)
- Database connection supported by Laravel (SQLite is sufficient for evaluation)

## Installation

Require the package in your Laravel application:

```bash
composer require azaharizaman/laravel-uom-management
```

The service provider auto-discovers. Publish the configuration and migrations if you need to customise defaults:

```bash
php artisan vendor:publish --provider="Azaharizaman\\LaravelUomManagement\\LaravelUomManagementServiceProvider"
```

Run migrations to provision the base schema:

```bash
php artisan migrate
```

Optionally seed baseline units and conversions:

```bash
php artisan uom:seed
```

## Usage Overview

```php
use Azaharizaman\LaravelUomManagement\Services\DefaultUnitConverter;

$converter = app(DefaultUnitConverter::class);

// Convert 500 millilitres to litres.
$value = $converter->convert('500', 'ML', 'L');

// Convert compound units, e.g. km/hr to m/s.
$compoundConverter = app(\Azaharizaman\LaravelUomManagement\Services\DefaultCompoundUnitConverter::class);
$result = $compoundConverter->convert('120', 'KM/HR', 'M/S');
```

### Custom Units

```php
use Azaharizaman\LaravelUomManagement\Services\DefaultCustomUnitRegistrar;

$registrar = app(DefaultCustomUnitRegistrar::class);

$customUnit = $registrar->register([
    'type_code' => 'VOLUME',
    'code' => 'CUST-CUP',
    'name' => 'Customer Cup',
    'conversion_factor' => '237',
    'owner' => $tenant, // morph relation
]);
```

Consult the `docs/` directory for detailed walkthroughs of conversions, packaging, and compound units.

## Testing

Clone the repository and install dependencies:

```bash
composer install
```

Run the package test suite via Orchestra Testbench:

```bash
vendor/bin/phpunit
```

To generate coverage reports:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage-report
```

## Contributing

1. Fork the repository and create a feature branch.
2. Ensure tests and coding standards pass.
3. Submit a pull request describing the change and its motivation.

Issues and feature requests are welcome via GitHub.
