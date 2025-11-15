# Nexus Org Structure Package

A comprehensive Laravel package for managing organizational structures with directory synchronization capabilities.

## Features

- **Hierarchical Organizational Units**: Create and manage organizational units with parent-child relationships
- **Positions**: Define job positions within organizational units
- **Employee Assignments**: Track employee position assignments with temporal validity
- **Reporting Relationships**: Establish and manage manager-subordinate reporting lines
- **Directory Synchronization**: Built-in support for LDAP/AD synchronization with extensible adapter pattern
- **Multi-tenancy**: Full tenant isolation for all organizational data
- **Comprehensive Testing**: 100% test coverage with Pest/PHPUnit

## Installation

```bash
composer require nexus/org-structure
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Nexus\OrgStructure\OrgStructureServiceProvider"
```

## Usage

### Basic Usage

```php
use Nexus\OrgStructure\Services\DefaultOrganizationService;

$service = app(DefaultOrganizationService::class);

// Create organizational unit
$orgUnitId = $service->createOrgUnit(
    tenantId: '01HXXXXXXXXXXXXXXXXXXXXX',
    name: 'Engineering Department',
    code: 'ENG',
    parentId: null, // null for root units
    metadata: ['description' => 'Software development team']
);

// Create position
$positionId = $service->createPosition(
    tenantId: '01HXXXXXXXXXXXXXXXXXXXXX',
    title: 'Senior Developer',
    code: 'SR-DEV',
    orgUnitId: $orgUnitId,
    metadata: ['level' => 'Senior', 'salary_grade' => 'G5']
);

// Create employee assignment
$assignmentId = $service->createAssignment(
    tenantId: '01HXXXXXXXXXXXXXXXXXXXXX',
    employeeId: '01HXXXXXXXXXXXXXXXXXXXXX',
    positionId: $positionId,
    orgUnitId: $orgUnitId,
    effectiveFrom: '2025-01-01',
    effectiveTo: null, // null for current assignments
    isPrimary: true
);

// Create reporting relationship
$service->createReportingLine(
    tenantId: '01HXXXXXXXXXXXXXXXXXXXXX',
    managerEmployeeId: '01HXXXXXXXXXXXXXXXXXXXXX',
    subordinateEmployeeId: '01HXXXXXXXXXXXXXXXXXXXXX',
    positionId: $positionId,
    effectiveFrom: '2025-01-01'
);
```

### Advanced Queries

```php
// Get employee's current assignments
$assignments = $service->getAssignmentsForEmployee($employeeId);

// Get employee's manager
$manager = $service->getManager($employeeId);

// Get employee's subordinates
$subordinates = $service->getSubordinates($managerId);

// Resolve full reporting chain
$chain = $service->resolveReportingChain($employeeId);

// Get organizational unit hierarchy
$orgUnit = $service->getOrgUnit($orgUnitId);
echo $orgUnit['hierarchy_path']; // "Company > Engineering > Backend Team"
```

### Directory Synchronization

```php
use Nexus\OrgStructure\Services\DirectorySync\LdapDirectorySyncAdapter;

$adapter = new LdapDirectorySyncAdapter();

// Configure LDAP connection
$adapter->configure([
    'host' => 'ldap.example.com',
    'port' => 389,
    'base_dn' => 'dc=example,dc=com',
    'bind_dn' => 'cn=admin,dc=example,dc=com',
    'bind_password' => 'password',
]);

// Test connection
$connected = $adapter->testConnection();

// Sync organizational units
$syncResult = $adapter->syncOrgUnits($tenantId);

// Sync positions
$syncResult = $adapter->syncPositions($tenantId);

// Sync assignments
$syncResult = $adapter->syncAssignments($tenantId);
```

## Models

### OrgUnit
- Hierarchical organizational units
- Parent-child relationships
- Scopes: `roots()`, `forTenant($tenantId)`

### Position
- Job positions within org units
- Belongs to organizational unit
- Scopes: `forTenant($tenantId)`, `forOrgUnit($orgUnitId)`

### Assignment
- Employee position assignments
- Temporal validity (effective_from/to)
- Primary/secondary assignments
- Scopes: `current()`, `primary()`, `forEmployee($employeeId)`

### ReportingLine
- Manager-subordinate relationships
- Temporal validity
- Scopes: `current()`, `forManager($managerId)`, `forSubordinate($subordinateId)`

## Contracts

### OrganizationServiceContract
Defines the interface for organizational operations:
- CRUD operations for all entities
- Query methods for relationships
- Reporting chain resolution

### DirectorySyncAdapterContract
Defines the interface for directory synchronization:
- Connection testing
- Entity synchronization methods
- Normalization of directory data

## Testing

Run the test suite:

```bash
./vendor/bin/pest packages/nexus-org-structure/tests/
```

## Architecture Principles

This package follows the Maximum Atomicity design principles:

- **Independent Testability**: Package can be tested in isolation
- **Contract-Based Design**: Clear interfaces for all services
- **Extensible Adapters**: Directory sync adapters follow adapter pattern
- **Multi-Tenant Ready**: All operations are tenant-scoped
- **Temporal Data**: Assignments and reporting lines support effective dating

## Requirements

- PHP 8.3+
- Laravel 11.0+ or 12.0+
- Database supporting JSON columns (MySQL 5.7+, PostgreSQL, SQLite)

## License

MIT License