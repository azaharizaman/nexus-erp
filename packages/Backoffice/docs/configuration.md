# Configuration Guide

The BackOffice package comes with a comprehensive configuration file that allows you to customize various aspects of the package behavior.

## Configuration File

The configuration file is located at `config/backoffice.php` after publishing. Here's a detailed explanation of each configuration option:

## Table Configuration

### Table Prefix

```php
'table_prefix' => 'backoffice_',
```

Controls the prefix for all package database tables. Change this if you want to use a different naming convention or avoid conflicts with existing tables.

**Example**: Setting this to `org_` would create tables like `org_companies`, `org_offices`, etc.

## Model Configuration

### Custom Models

```php
'models' => [
    'company' => \AzahariZaman\BackOffice\Models\Company::class,
    'office' => \AzahariZaman\BackOffice\Models\Office::class,
    'office_type' => \AzahariZaman\BackOffice\Models\OfficeType::class,
    'department' => \AzahariZaman\BackOffice\Models\Department::class,
    'staff' => \AzahariZaman\BackOffice\Models\Staff::class,
    'unit' => \AzahariZaman\BackOffice\Models\Unit::class,
    'unit_group' => \AzahariZaman\BackOffice\Models\UnitGroup::class,
],
```

Allows you to specify custom model classes that extend the package models. This is useful when you need to add custom methods or properties.

**Example**:
```php
// Create your custom model
class Company extends \AzahariZaman\BackOffice\Models\Company
{
    public function getDisplayNameAttribute()
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }
}

// Update configuration
'models' => [
    'company' => App\Models\Company::class,
    // ... other models
],
```

## Route Configuration

```php
'routes' => [
    'enabled' => false,
    'prefix' => 'backoffice',
    'middleware' => ['web'],
],
```

- **enabled**: Whether to register package routes (currently disabled by default)
- **prefix**: URL prefix for package routes
- **middleware**: Middleware to apply to package routes

## Validation Configuration

### Default Validation Rules

The package provides default validation rules for all models:

```php
'validation' => [
    'company' => [
        'name' => 'required|string|max:255',
        'code' => 'nullable|string|max:50|unique:backoffice_companies,code',
        'description' => 'nullable|string|max:1000',
        'parent_company_id' => 'nullable|exists:backoffice_companies,id',
        'is_active' => 'boolean',
    ],
    // ... other model validations
],
```

You can customize these rules or add new ones for your specific requirements.

**Example**: Adding custom validation
```php
'validation' => [
    'company' => [
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:10|unique:backoffice_companies,code', // Made required
        'tax_number' => 'nullable|string|max:50', // Added custom field
        // ... other rules
    ],
],
```

## Soft Deletes Configuration

```php
'soft_deletes' => [
    'company' => true,
    'office' => true,
    'office_type' => true,
    'department' => true,
    'staff' => true,
    'unit' => true,
    'unit_group' => true,
],
```

Controls which models use soft deletes. Set to `false` to disable soft deletes for specific models.

## Default Office Types

```php
'default_office_types' => [
    ['name' => 'Head Office', 'code' => 'HO', 'description' => 'Main headquarters'],
    ['name' => 'Branch Office', 'code' => 'BO', 'description' => 'Regional branch office'],
    ['name' => 'Sales Office', 'code' => 'SO', 'description' => 'Sales and marketing office'],
    ['name' => 'Service Center', 'code' => 'SC', 'description' => 'Customer service center'],
    ['name' => 'Warehouse', 'code' => 'WH', 'description' => 'Storage and distribution facility'],
],
```

Default office types created when running `php artisan backoffice:create-office-types`. Customize this array to match your organization's needs.

## Hierarchy Settings

```php
'hierarchy' => [
    'max_depth' => 10,
    'prevent_circular_references' => true,
],
```

- **max_depth**: Maximum depth allowed for hierarchical structures
- **prevent_circular_references**: Whether to automatically prevent circular references

## Cache Configuration

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'key_prefix' => 'backoffice_',
],
```

- **enabled**: Whether to enable caching for hierarchy queries
- **ttl**: Cache time-to-live in seconds
- **key_prefix**: Prefix for cache keys

## Event Configuration

```php
'events' => [
    'company_created' => true,
    'company_updated' => true,
    'company_deleted' => true,
    'office_created' => true,
    'office_updated' => true,
    'office_deleted' => true,
    'department_created' => true,
    'department_updated' => true,
    'department_deleted' => true,
    'staff_created' => true,
    'staff_updated' => true,
    'staff_deleted' => true,
],
```

Controls which model events should be fired. Set to `false` to disable specific events.

## Environment Variables

You can also control some configuration through environment variables:

```env
# config/backoffice.php will check for these
BACKOFFICE_CACHE_ENABLED=true
BACKOFFICE_CACHE_TTL=3600
BACKOFFICE_TABLE_PREFIX=backoffice_
BACKOFFICE_MAX_DEPTH=10
```

To use environment variables, update your configuration file:

```php
'cache' => [
    'enabled' => env('BACKOFFICE_CACHE_ENABLED', true),
    'ttl' => env('BACKOFFICE_CACHE_TTL', 3600),
    'key_prefix' => env('BACKOFFICE_TABLE_PREFIX', 'backoffice_'),
],

'hierarchy' => [
    'max_depth' => env('BACKOFFICE_MAX_DEPTH', 10),
    'prevent_circular_references' => true,
],
```

## Advanced Configuration

### Custom Service Provider

If you need to completely customize the package behavior, you can create your own service provider:

```php
use AzahariZaman\BackOffice\BackOfficeServiceProvider as BaseServiceProvider;

class CustomBackOfficeServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        
        // Your custom initialization logic
        $this->registerCustomObservers();
        $this->registerCustomPolicies();
    }
    
    protected function registerCustomObservers(): void
    {
        // Register custom observers
    }
    
    protected function registerCustomPolicies(): void
    {
        // Register custom policies
    }
}
```

Then register your custom service provider in `config/app.php`.

### Database Connection

To use a different database connection for the package models:

```php
// In your custom model
class Company extends \AzahariZaman\BackOffice\Models\Company
{
    protected $connection = 'backoffice_db';
}
```

Configure the connection in `config/database.php`:

```php
'connections' => [
    'backoffice_db' => [
        'driver' => 'mysql',
        'host' => env('BACKOFFICE_DB_HOST', '127.0.0.1'),
        'port' => env('BACKOFFICE_DB_PORT', '3306'),
        'database' => env('BACKOFFICE_DB_DATABASE', 'backoffice'),
        'username' => env('BACKOFFICE_DB_USERNAME', 'forge'),
        'password' => env('BACKOFFICE_DB_PASSWORD', ''),
        // ... other options
    ],
],
```

## Configuration Best Practices

1. **Always publish configuration**: Don't modify the vendor configuration directly
2. **Use environment variables**: For settings that vary between environments
3. **Version control**: Include the published configuration in version control
4. **Document changes**: Comment any customizations you make
5. **Test thoroughly**: Always test configuration changes in a development environment first

## Next Steps

- Learn about [Models & Relationships](models.md)
- Explore [Traits & Behaviors](traits.md)
- Check out [Examples](examples.md)