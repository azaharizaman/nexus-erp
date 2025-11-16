# Package Structure Summary

This document provides an overview of the complete BackOffice Laravel package structure that has been created.

## Package Overview

**Name**: azaharizaman/backoffice  
**Namespace**: AzahariZaman\BackOffice  
**Purpose**: Hierarchical company structure management  
**Target**: PHP 8.2+, Laravel 12+

## Directory Structure

```
/home/conrad/Dev/azaharizaman/backoffice/
├── composer.json                    # Package configuration
├── README.md                        # Main package documentation
├── LICENSE.md                       # MIT License
├── CHANGELOG.md                     # Version history
├── CONTRIBUTING.md                  # Contribution guidelines
├── phpunit.xml                      # PHPUnit configuration
├── phpstan.neon                     # Static analysis configuration
├── config/
│   └── backoffice.php              # Package configuration file
├── database/
│   └── migrations/                  # Database migrations
│       ├── 2025_01_01_000001_create_backoffice_companies_table.php
│       ├── 2025_01_01_000002_create_backoffice_office_types_table.php
│       ├── 2025_01_01_000003_create_backoffice_offices_table.php
│       ├── 2025_01_01_000004_create_backoffice_departments_table.php
│       ├── 2025_01_01_000005_create_backoffice_unit_groups_table.php
│       ├── 2025_01_01_000006_create_backoffice_units_table.php
│       ├── 2025_01_01_000007_create_backoffice_staff_table.php
│       ├── 2025_01_01_000008_create_backoffice_office_office_type_table.php
│       └── 2025_01_01_000009_create_backoffice_staff_unit_table.php
├── src/
│   ├── BackOfficeServiceProvider.php # Main service provider
│   ├── Models/                      # Eloquent models
│   │   ├── Company.php
│   │   ├── Office.php
│   │   ├── Department.php
│   │   ├── Staff.php
│   │   ├── Unit.php
│   │   ├── UnitGroup.php
│   │   └── OfficeType.php
│   ├── Traits/                      # Model traits
│   │   └── HasHierarchy.php
│   ├── Policies/                    # Authorization policies
│   │   ├── CompanyPolicy.php
│   │   ├── OfficePolicy.php
│   │   ├── DepartmentPolicy.php
│   │   └── StaffPolicy.php
│   ├── Observers/                   # Model observers
│   │   ├── CompanyObserver.php
│   │   ├── OfficeObserver.php
│   │   ├── DepartmentObserver.php
│   │   └── StaffObserver.php
│   ├── Commands/                    # Artisan commands
│   │   ├── InstallBackOfficeCommand.php
│   │   └── CreateOfficeTypesCommand.php
│   ├── Enums/                       # Enumeration classes
│   │   ├── OfficeTypeStatus.php
│   │   └── StaffStatus.php
│   ├── Casts/                       # Custom attribute casts
│   │   ├── HierarchyPath.php
│   │   └── FullName.php
│   ├── Exceptions/                  # Custom exceptions
│   │   ├── CircularReferenceException.php
│   │   └── InvalidAssignmentException.php
│   └── Services/                    # (Empty - for future expansion)
├── docs/                           # Documentation
│   ├── README.md                   # Documentation index
│   ├── installation.md             # Installation guide
│   ├── configuration.md            # Configuration guide
│   ├── models.md                   # Models and relationships
│   └── examples.md                 # Usage examples
└── tests/                          # (Empty - test structure ready)
```

## Key Features Implemented

### 1. Hierarchical Structure Management
- **Companies**: Parent-child company relationships
- **Offices**: Physical office hierarchies with unlimited depth
- **Departments**: Logical department hierarchies
- **Circular Reference Prevention**: Automatic validation

### 2. Staff Management
- **Flexible Assignment**: Staff can belong to offices and/or departments
- **Unit Membership**: Staff can belong to multiple units
- **Comprehensive Information**: Employee details, positions, contact info

### 3. Unit Organization
- **Unit Groups**: Logical containers for units
- **Flat Structure**: Units are not hierarchical
- **Cross-functional Teams**: Staff can belong to multiple units

### 4. Office Categorization
- **Office Types**: Configurable office categorization
- **Many-to-Many**: Offices can have multiple types
- **Default Types**: Pre-configured common office types

### 5. Advanced Features
- **HasHierarchy Trait**: Reusable hierarchy functionality
- **Observer Pattern**: Automatic event handling
- **Policy-based Authorization**: Comprehensive access control
- **Custom Casts**: Specialized attribute handling
- **Artisan Commands**: Package management utilities

## Database Schema

### Tables Created
1. `backoffice_companies` - Company entities with hierarchy
2. `backoffice_office_types` - Office type categories
3. `backoffice_offices` - Physical office locations with hierarchy
4. `backoffice_departments` - Logical departments with hierarchy
5. `backoffice_unit_groups` - Unit group containers
6. `backoffice_units` - Staff unit groupings
7. `backoffice_staff` - Employee/staff records
8. `backoffice_office_office_type` - Office-OfficeType pivot table
9. `backoffice_staff_unit` - Staff-Unit pivot table

### Key Relationships
- Companies → Offices (One-to-Many)
- Companies → Departments (One-to-Many)
- Companies → UnitGroups (One-to-Many)
- Offices → Staff (One-to-Many)
- Departments → Staff (One-to-Many)
- Units → Staff (Many-to-Many)
- Offices → OfficeTypes (Many-to-Many)
- All hierarchical models support parent-child relationships

## Installation & Usage

### Quick Installation
```bash
composer require azaharizaman/backoffice
php artisan backoffice:install
```

### Basic Usage
```php
use AzahariZaman\BackOffice\Models\Company;

$company = Company::create([
    'name' => 'My Company',
    'code' => 'MYCO',
    'is_active' => true,
]);
```

## Configuration Options

The package provides extensive configuration through `config/backoffice.php`:
- Custom model classes
- Validation rules
- Default office types
- Cache settings
- Event configuration
- Hierarchy settings

## Documentation

Comprehensive documentation is provided covering:
- Installation and setup
- Configuration options
- Model relationships and usage
- Real-world examples
- Best practices
- API reference

## Development Tools

- **PHPUnit**: Testing framework configuration
- **PHPStan**: Static analysis configuration
- **Composer Scripts**: Automated testing and analysis
- **Code Standards**: PSR-12 compliant

## Security Features

- Input validation through observers
- Authorization policies for all models
- Circular reference prevention
- Soft delete protection
- Mass assignment protection

## Extensibility

The package is designed for extensibility:
- Custom model classes via configuration
- Trait-based functionality
- Observer pattern for custom logic
- Service provider for additional registrations
- Policy-based authorization

## Next Steps

1. **Testing**: Implement comprehensive test suite
2. **Factories**: Create model factories for testing
3. **Seeders**: Add database seeders for sample data
4. **API Resources**: Add JSON API resource classes
5. **Events**: Implement custom event classes
6. **Notifications**: Add notification support
7. **Caching**: Implement hierarchy caching
8. **Validation**: Add custom validation rules

This package provides a solid foundation for managing complex organizational structures in Laravel applications while maintaining flexibility and extensibility for future enhancements.