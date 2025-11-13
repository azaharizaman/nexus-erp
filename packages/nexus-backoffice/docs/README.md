# BackOffice Package Documentation

Welcome to the comprehensive documentation for the BackOffice Laravel package. This package provides a complete backend solution for managing hierarchical company structures, offices, departments, staff, and organizational units.

## Table of Contents

- [Installation Guide](installation.md)
- [Configuration](configuration.md)
- [Models & Relationships](models.md)
- **[Model Factories](factories.md)** ⭐ _New!_
- **[Position Management](positions.md)** ⭐ _New!_
- [Organizational Chart & Reporting Lines](organizational-chart.md)
- [Staff Resignation Management](resignation.md)
- [Staff Transfer System](staff-transfers.md)
- [Traits & Behaviors](traits.md)
- [Policies & Authorization](policies.md)
- [Console Commands](commands.md)
- [API Reference](api.md)
- [Examples](examples.md)
- [Best Practices](best-practices.md)
- [Troubleshooting](troubleshooting.md)

## Overview

The BackOffice package is designed to handle complex organizational structures with the following key features:

### Hierarchical Structures
- **Companies**: Parent-child company relationships
- **Offices**: Physical office hierarchies with unlimited depth
- **Departments**: Logical department hierarchies
- **Units**: Flat organizational units grouped by unit groups

### Staff Management
- Staff can belong to offices and/or departments
- Multiple unit assignments per staff member
- Comprehensive staff information tracking
- **Position Management**:
  - Structured job positions with hierarchical types
  - 10 position types from C-Level to Assistant
  - Grade/salary band management
  - Department precedence logic for position defaults
  - Position-based filtering and reporting
- **Organizational Chart & Reporting Lines**:
  - Hierarchical supervisor/subordinate relationships
  - Comprehensive organizational chart generation
  - Reporting path analysis and statistics
  - Multiple export formats (JSON, CSV, DOT/Graphviz)
  - Reorganization suggestions and analytics
- **Staff Resignation Management**:
  - Schedule resignations with future dates
  - Automatic resignation processing
  - Resignation reason tracking
  - Resignation cancellation support
- **Staff Transfer System**:
  - Transfer staff between offices and departments
  - Approval workflow with status tracking
  - Scheduled transfers with effective dates
  - Complete audit trail and history
  - Transfer validation and business rules

### Flexible Architecture
- Model traits for reusable functionality
- Observer pattern for automatic event handling
- Policy-based authorization
- Configurable validation rules
- Extensible through custom models

## Quick Start

1. **Install the package**:
```bash
composer require azaharizaman/backoffice
```

2. **Install the package components**:
```bash
php artisan backoffice:install
```

3. **Create your first company using factories** (recommended):
```php
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Staff;

// Create company
$company = Company::factory()->create([
    'name' => 'My Company',
    'code' => 'MYCO',
]);
```

4. **Create offices and departments**:
```php
// Create main office using factory
$mainOffice = Office::factory()->for($company)->create([
    'name' => 'Head Office',
    'code' => 'HO',
    'address' => '123 Main Street',
]);

// Create department using factory
$department = Department::factory()->for($company)->create([
    'name' => 'Human Resources',
    'code' => 'HR',
]);
```

5. **Add staff using factories**:
```php
// Create staff using factory
$staff = Staff::factory()
    ->withBoth($mainOffice, $department)
    ->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@company.com',
        'position' => 'HR Manager',
    ]);

// Create organizational hierarchy
$ceo = Staff::factory()->ceo()->inOffice($mainOffice)->create();
$manager = Staff::factory()->manager()->withSupervisor($ceo)->create();
$employee = Staff::factory()->withSupervisor($manager)->create();
```

6. **Manage staff resignations**:
```php
use Carbon\Carbon;

// Schedule resignation 30 days from now
$staff->scheduleResignation(
    Carbon::now()->addDays(30),
    'Found better opportunity'
);

// Process resignations automatically
php artisan backoffice:process-resignations --force
```

> **💡 Tip**: For testing and development, always use [Model Factories](factories.md) to create data. See the [Factories Documentation](factories.md) for comprehensive examples.

## Key Concepts

### Hierarchy Management
The package provides robust hierarchy management through the `HasHierarchy` trait, which offers:
- Ancestor/descendant traversal
- Root/leaf identification
- Circular reference prevention
- Path calculation

### Flexible Assignment
Staff can be assigned to:
- Office only
- Department only
- Both office and department
- Multiple units across different unit groups

### Event-Driven Architecture
All models implement observer patterns for:
- Validation on creation/update
- Automatic cleanup on deletion
- Hierarchy integrity maintenance
- Custom business logic hooks

## Support

For issues, feature requests, or questions, please:
1. Check the [troubleshooting guide](troubleshooting.md)
2. Review the [examples](examples.md)
3. Open an issue on GitHub

## Contributing

Please see [CONTRIBUTING.md](../CONTRIBUTING.md) for guidelines on contributing to this package.