# Installation Guide

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or 12.0+
- MySQL 5.7+ / PostgreSQL 10+ / SQLite 3.24+

## Installation Steps

### 1. Install via Composer

```bash
composer require azaharizaman/backoffice
```

### 2. Publish Configuration and Migrations

Run the installation command to publish configuration and migrations:

```bash
php artisan backoffice:install
```

This command will:
- Publish the configuration file to `config/backoffice.php`
- Publish migration files to `database/migrations/`
- Optionally run migrations
- Optionally create default office types

### 3. Manual Installation (Alternative)

If you prefer manual installation:

#### Publish Configuration
```bash
php artisan vendor:publish --provider="AzahariZaman\BackOffice\BackOfficeServiceProvider" --tag="backoffice-config"
```

#### Publish Migrations
```bash
php artisan vendor:publish --provider="AzahariZaman\BackOffice\BackOfficeServiceProvider" --tag="backoffice-migrations"
```

#### Run Migrations
```bash
php artisan migrate
```

#### Create Default Office Types
```bash
php artisan backoffice:create-office-types
```

## Configuration

### Environment Variables

Add these optional environment variables to your `.env` file:

```env
# Cache configuration
BACKOFFICE_CACHE_ENABLED=true
BACKOFFICE_CACHE_TTL=3600

# Table prefix (optional)
BACKOFFICE_TABLE_PREFIX=backoffice_

# Maximum hierarchy depth
BACKOFFICE_MAX_DEPTH=10
```

### Database Configuration

The package supports all Laravel-supported databases:

#### MySQL
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### SQLite
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

## Verification

### 1. Check Migrations

Verify that all tables have been created:

```bash
php artisan migrate:status
```

You should see these migrations as completed:
- `create_backoffice_companies_table`
- `create_backoffice_office_types_table`
- `create_backoffice_offices_table`
- `create_backoffice_departments_table`
- `create_backoffice_unit_groups_table`
- `create_backoffice_units_table`
- `create_backoffice_staff_table`
- `create_backoffice_office_office_type_table`
- `create_backoffice_staff_unit_table`

### 2. Check Configuration

Verify the configuration file exists:

```bash
ls -la config/backoffice.php
```

### 3. Test Basic Functionality

Create a simple test to verify everything is working:

```php
// In a controller or tinker session
use AzahariZaman\BackOffice\Models\Company;

$company = Company::create([
    'name' => 'Test Company',
    'is_active' => true,
]);

echo "Company created with ID: " . $company->id;
```

## Post-Installation

### 1. Review Configuration

Edit `config/backoffice.php` to customize:
- Model classes (if extending)
- Validation rules
- Default office types
- Cache settings
- Event configuration

### 2. Set Up Authorization

If using Laravel's built-in authentication, the package policies will automatically be registered. To customize authorization logic, extend the policy classes:

```php
// In a custom policy class
use AzahariZaman\BackOffice\Policies\CompanyPolicy as BaseCompanyPolicy;

class CompanyPolicy extends BaseCompanyPolicy
{
    public function create($user): bool
    {
        // Your custom authorization logic
        return $user->hasRole('admin');
    }
}
```

Then update the configuration:

```php
// config/backoffice.php
'policies' => [
    'company' => App\Policies\CompanyPolicy::class,
    // ... other policies
],
```

### 3. Customize Models (Optional)

If you need to extend the models, create your own model classes:

```php
use AzahariZaman\BackOffice\Models\Company as BaseCompany;

class Company extends BaseCompany
{
    // Your custom methods and properties
    public function customMethod()
    {
        // Custom functionality
    }
}
```

Update the configuration:

```php
// config/backoffice.php
'models' => [
    'company' => App\Models\Company::class,
    // ... other models
],
```

## Troubleshooting

### Migration Issues

If migrations fail:

1. **Check database connection**:
```bash
php artisan migrate:status
```

2. **Check for existing tables**:
Some table names might conflict with existing tables. Review the migration files and adjust table names if necessary.

3. **Foreign key constraints**:
Ensure your database supports foreign key constraints and they are enabled.

### Permission Issues

If you encounter permission errors:

1. **Check file permissions**:
```bash
chmod -R 755 config/
chmod -R 755 database/migrations/
```

2. **Clear cache**:
```bash
php artisan config:clear
php artisan cache:clear
```

### Class Not Found Errors

If you get "Class not found" errors:

1. **Clear autoloader cache**:
```bash
composer dump-autoload
```

2. **Check namespace**:
Ensure you're using the correct namespace in your imports:
```php
use AzahariZaman\BackOffice\Models\Company;
```

## Next Steps

- Read the [Configuration Guide](configuration.md)
- Explore [Models & Relationships](models.md)
- Check out [Examples](examples.md)
- Review [Best Practices](best-practices.md)