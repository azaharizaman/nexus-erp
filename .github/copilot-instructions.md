# Laravel ERP System - GitHub Copilot Instructions

**Version:** 1.0.0  
**Last Updated:** November 8, 2025  
**Project:** Laravel Headless ERP Backend System

---

## Project Overview

This is an enterprise-grade, headless ERP backend system built with Laravel 12+ and PHP 8.2+. The system is designed to rival SAP, Odoo, and Microsoft Dynamics while maintaining superior modularity, extensibility, and agentic capabilities.

### Key Characteristics

- **Architecture:** Headless backend-only system (no UI components)
- **Integration:** RESTful APIs and CLI commands only
- **Design Philosophy:** Contract-driven, domain-driven, event-driven
- **Target:** AI agents, custom frontends, and automated systems
- **Modularity:** Enable/disable modules without system-wide impact
- **Security:** Zero-trust model with blockchain verification

---

## Technology Stack

### Core Requirements

- **PHP:** ≥ 8.2 (use latest PHP 8.2+ features)
- **Laravel:** ≥ 12.x (latest stable version)
- **Database:** Agnostic design (MySQL, PostgreSQL, SQLite, SQL Server)
- **Composer Stability:** `dev` for internal packages

### Required Packages

```json
{
  "azaharizaman/laravel-uom-management": "dev-main",
  "azaharizaman/laravel-inventory-management": "dev-main",
  "azaharizaman/laravel-backoffice": "dev-main",
  "azaharizaman/laravel-serial-numbering": "dev-main",
  "azaharizaman/php-blockchain": "dev-main",
  "laravel/scout": "^10.0",
  "laravel/pulse": "^1.0",
  "pestphp/pest": "^4.0",
  "laravel/pint": "^1.0",
  "lorisleiva/laravel-actions": "^2.0",
  "spatie/laravel-permission": "^6.0",
  "spatie/laravel-model-status": "^2.0",
  "spatie/laravel-activitylog": "^4.0",
  "brick/math": "^0.12"
}
```

### Architecture Patterns

- **Contract-Driven Development:** All functionality defined by interfaces
- **Domain-Driven Design:** Business logic organized by domain boundaries
- **Event-Driven Architecture:** Module communication via Laravel events
- **Repository Pattern:** Data access abstraction layer
- **Service Layer Pattern:** Business logic encapsulation
- **Action Pattern:** Discrete business operations using `lorisleiva/laravel-actions`
- **SOLID Principles:** Single responsibility, dependency injection throughout

---

## Laravel Scout Integration

### Search Functionality Requirements

**MANDATORY:** All Eloquent models in the Laravel ERP system MUST implement Laravel Scout for search functionality. This ensures consistent, performant search capabilities across all domains.

#### Model Implementation
```php
namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class InventoryItem extends Model
{
    use Searchable;
    
    // Scout configuration
    public function searchableAs(): string
    {
        return 'inventory_items';
    }
    
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category?->name,
            'tenant_id' => $this->tenant_id,
        ];
    }
    
    // Scout automatically indexes on create/update/delete
}
```

#### Search Usage
```php
// Basic search
$results = InventoryItem::search('laptop')->get();

// Advanced search with filters
$results = InventoryItem::search('laptop')
    ->where('tenant_id', $tenantId)
    ->take(20)
    ->get();

// Search with pagination
$results = InventoryItem::search('laptop')->paginate(15);
```

#### Configuration
- **Driver:** Use `collection` for development, configure production driver (Algolia, MeiliSearch, etc.) via `SCOUT_DRIVER` env variable
- **Queue:** Enable queued indexing via `SCOUT_QUEUE=true` for production performance
- **Tenant Isolation:** Include `tenant_id` in searchable array for proper multi-tenant search

#### Requirements
- ✅ All models MUST use `Laravel\Scout\Searchable` trait
- ✅ Implement `searchableAs()` method for index naming
- ✅ Implement `toSearchableArray()` method for search data
- ✅ Include tenant_id for multi-tenant isolation
- ✅ Test search functionality in feature tests
- ✅ Configure Scout driver in production environment

---

## Pest Testing Framework

### Pest v4+ Integration

**MANDATORY:** All testing in the Laravel ERP system MUST use Pest v4+ as the primary testing framework. Pest provides a more expressive and modern testing experience compared to PHPUnit.

#### Installation & Configuration
- **Framework:** Pest v4.1.3 (primary testing framework)
- **PHPUnit:** v12.4.1 (dependency for Pest compatibility)
- **Configuration:** `tests/Pest.php` extends `Tests\TestCase`

#### Testing Standards
```php
// Feature Test Example
test('can create inventory item', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/inventory-items', [
            'code' => 'ITEM-001',
            'name' => 'Test Item',
            'quantity' => 100,
        ]);
    
    $response->assertCreated();
    expect($response->json('data.code'))->toBe('ITEM-001');
});

// Unit Test Example
test('inventory item has required attributes', function () {
    $item = InventoryItem::factory()->create();
    
    expect($item)->toHaveAttribute('code')
        ->and($item->code)->toBeString()
        ->and($item->quantity)->toBeNumeric();
});
```

#### Pest Features to Use
- **Higher-Order Testing:** `$user->can('create', Item::class)`
- **Expectation API:** `expect($value)->toBe($expected)`
- **Test Data Factories:** `User::factory()->create()`
- **Model Factories:** `InventoryItem::factory()->create()`
- **Parallel Testing:** `./vendor/bin/pest --parallel`
- **Coverage:** `./vendor/bin/pest --coverage`

#### Requirements
- ✅ All new tests MUST use Pest syntax (`test()`, `it()`, `expect()`)
- ✅ Use Pest expectation API instead of PHPUnit assertions
- ✅ Leverage Pest's higher-order testing capabilities
- ✅ Run tests with `./vendor/bin/pest` instead of `phpunit`
- ✅ Use Pest plugins for architecture testing (`pest-plugin-arch`)
- ✅ Maintain PHPUnit compatibility for legacy tests during migration

#### Running Tests
```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/Api/V1/InventoryTest.php

# Run with coverage
./vendor/bin/pest --coverage

# Run in parallel
./vendor/bin/pest --parallel
```

---

## Laravel Pulse Monitoring

### Performance Monitoring Integration

**OPTIONAL:** Laravel Pulse provides real-time performance monitoring and can be enabled for production environments.

#### Installation & Configuration
- **Package:** laravel/pulse ^1.4.3
- **Storage:** Database tables for metrics storage
- **Dashboard:** Web-based monitoring interface

#### Configuration
- **Recorders:** All recorders enabled by default
- **Retention:** Configurable data retention periods
- **Authorization:** Admin-only access to dashboard

#### Dashboard Access
- **Route:** `/pulse` (configurable)
- **Middleware:** Authentication required
- **Metrics:** Application performance, cache hits, queue jobs, slow queries

#### Requirements
- ✅ Publish migrations and run them for database setup
- ✅ Configure proper authorization for dashboard access
- ✅ Set up automated cleanup for old metrics data
- ✅ Monitor key performance indicators in production

---

## Laravel Pint Code Quality

### Code Style Enforcement

**MANDATORY:** All code MUST follow Laravel Pint standards for consistent code style.

#### Configuration
- **Tool:** Laravel Pint v1.25.1
- **Standard:** PSR-12 with Laravel-specific rules
- **Automation:** Pre-commit hooks and CI/CD integration

#### Usage
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint

# Fix specific file
./vendor/bin/pint app/Models/User.php
```

#### Requirements
- ✅ Run `./vendor/bin/pint` before committing changes
- ✅ Fix all style issues identified by Pint
- ✅ Configure IDE to use Pint for auto-formatting
- ✅ Include Pint in CI/CD pipeline for automated checking

---
