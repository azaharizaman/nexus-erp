# Nexus Tenancy Management

Multi-tenancy management package for Nexus ERP system. Provides robust tenant isolation, context management, and impersonation capabilities for SaaS applications.

## Features

- **Tenant Isolation**: Automatic query scoping to ensure data isolation between tenants
- **Tenant Context Management**: Global tenant context accessible throughout the application
- **Tenant Impersonation**: Support staff can impersonate tenants for support purposes
- **Lifecycle Management**: Complete tenant CRUD operations with status management
- **Event-Driven**: Comprehensive event system for tenant lifecycle hooks
- **Cache Support**: Built-in caching for improved performance

## Installation

This package is part of the Nexus ERP monorepo and is automatically available when using the main application.

## Usage

### Basic Tenant Operations

```php
use Nexus\Tenancy\Actions\CreateTenantAction;
use Nexus\Tenancy\Actions\UpdateTenantAction;
use Nexus\Tenancy\Contracts\TenantManagerContract;

// Create a new tenant
$tenant = CreateTenantAction::run([
    'name' => 'Acme Corporation',
    'domain' => 'acme',
    'status' => 'active',
]);

// Get current tenant
$tenantManager = app(TenantManagerContract::class);
$currentTenant = $tenantManager->current();

// Update tenant
UpdateTenantAction::run($tenant, [
    'name' => 'Acme Corp Updated',
]);
```

### Tenant-Scoped Models

```php
use Nexus\Tenancy\Traits\BelongsToTenant;

class Invoice extends Model
{
    use BelongsToTenant;
    
    // Queries are automatically scoped to current tenant
}
```

### Tenant Impersonation

```php
use Nexus\Tenancy\Actions\StartImpersonationAction;
use Nexus\Tenancy\Actions\EndImpersonationAction;

// Start impersonating a tenant (admin only)
StartImpersonationAction::run($tenant, $user, 'Customer support request #123');

// End impersonation
EndImpersonationAction::run($user);
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=tenancy-config
```

## License

MIT License - See LICENSE file for details
