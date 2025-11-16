# BackOffice Package Testing Guide

## Overview

This document provides comprehensive information about testing the BackOffice package, including test structure, running tests, and coverage details.

## Test Structure

The test suite is organized into two main categories:

### Feature Tests (`tests/Feature/`)
Feature tests verify the complete functionality of models, relationships, and business logic:

- **CompanyTest.php** - Tests company hierarchy, validation, and relationships
- **OfficeTest.php** - Tests office creation, company associations, and hierarchies
- **DepartmentTest.php** - Tests department management and office relationships

### Unit Tests (`tests/Unit/`)
Unit tests focus on specific components and their isolated functionality:

- **HasHierarchyTraitTest.php** - Tests the hierarchical functionality trait
- **EnumsTest.php** - Tests enum values and methods
- **CompanyObserverTest.php** - Tests observer behavior and validation

## Running Tests

### Prerequisites

Before running tests, ensure you have:

1. **PHP 8.2+** installed
2. **Composer dependencies** installed:
   ```bash
   composer install
   ```

### Test Execution

#### Option 1: Using the Test Runner Script
```bash
./run-tests.sh
```

#### Option 2: Using PHPUnit Directly
```bash
# Run all tests
vendor/bin/phpunit

# Run with verbose output
vendor/bin/phpunit --verbose

# Run specific test file
vendor/bin/phpunit tests/Feature/CompanyTest.php

# Run specific test method
vendor/bin/phpunit --filter testMethodName

# Run with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage/
```

#### Option 3: Run Tests by Category
```bash
# Run only Feature tests
vendor/bin/phpunit tests/Feature/

# Run only Unit tests
vendor/bin/phpunit tests/Unit/
```

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)
The package uses SQLite in-memory database for testing:

```xml
<php>
    <env name="DB_CONNECTION" value="testing"/>
    <env name="APP_KEY" value="base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKwsEGEgjAWp="/>
</php>
```

### Test Database
Tests use Orchestra Testbench with SQLite in-memory database for:
- Fast execution
- Isolated test environment
- No external dependencies

## Test Coverage Areas

### Models Testing
- **Company Model**: Hierarchy management, validation, relationships
- **Office Model**: Company associations, office types, hierarchies
- **Department Model**: Office associations, department hierarchies
- **Staff Model**: Department assignments, status management
- **Unit & UnitGroup Models**: Relationships and assignments

### Functionality Testing
- **Hierarchy Management**: Parent-child relationships, depth calculation, path resolution
- **Observers**: Circular reference prevention, validation logic
- **Policies**: Authorization and access control
- **Enums**: Status values and labels
- **Traits**: Reusable hierarchy functionality

### Business Logic Testing
- **Circular Reference Prevention**: Ensures no invalid parent-child relationships
- **Active Status Validation**: Tests active/inactive scopes and filters
- **Relationship Integrity**: Validates foreign key constraints and relationships

## Common Test Patterns

### Model Creation Pattern
```php
$company = Company::create([
    'name' => 'Test Company',
    'code' => 'TEST',
    'is_active' => true,
]);
```

### Hierarchy Testing Pattern
```php
// Create parent-child relationships
$parent = Company::create(['name' => 'Parent']);
$child = Company::create([
    'name' => 'Child',
    'parent_company_id' => $parent->id
]);

// Test relationships
$this->assertTrue($parent->childCompanies->contains($child));
$this->assertEquals($parent->id, $child->parentCompany->id);
```

### Exception Testing Pattern
```php
$this->expectException(CircularReferenceException::class);
$this->expectExceptionMessage('Cannot set parent: Circular reference detected');

// Code that should throw exception
$company->update(['parent_company_id' => $company->id]);
```

## Test Data Requirements

### Required Base Data
Most tests require these foundational models:

1. **Company** - Base organizational entity
2. **OfficeType** - Required for office creation
3. **Office** - Required for department creation

### Sample Test Data Creation
```php
protected function createTestStructure()
{
    $company = Company::create([
        'name' => 'Test Company',
        'is_active' => true,
    ]);

    $officeType = OfficeType::create([
        'name' => 'Headquarters',
        'code' => 'HQ',
        'is_active' => true,
    ]);

    $office = Office::create([
        'name' => 'Main Office',
        'company_id' => $company->id,
        'office_type_id' => $officeType->id,
        'is_active' => true,
    ]);

    return compact('company', 'officeType', 'office');
}
```

## Testing Best Practices

### 1. Use RefreshDatabase Trait
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
    // Test methods...
}
```

### 2. Test Method Naming
Use descriptive test method names:
```php
#[Test]
public function it_can_create_company_hierarchy()
#[Test]
public function it_prevents_circular_reference_when_updating()
#[Test]
public function it_requires_name_and_company_fields()
```

### 3. Assertion Patterns
```php
// Database assertions
$this->assertDatabaseHas('table_name', ['field' => 'value']);

// Model assertions
$this->assertEquals($expected, $actual);
$this->assertTrue($condition);
$this->assertCount($expectedCount, $collection);

// Exception assertions
$this->expectException(ExceptionClass::class);
```

## Debugging Tests

### Common Issues and Solutions

1. **Migration Errors**
   - Ensure all migration files are present in `database/migrations/`
   - Check foreign key constraints

2. **Class Not Found Errors**
   - Verify autoload configuration in `composer.json`
   - Run `composer dump-autoload`

3. **Observer Not Working**
   - Ensure observers are registered in `BackOfficeServiceProvider`
   - Check observer class names and methods

### Debugging Commands
```bash
# Check autoload
composer dump-autoload

# Clear any cached config
php artisan config:clear

# Run with debug output
vendor/bin/phpunit --debug

# Run single test with verbose output
vendor/bin/phpunit tests/Feature/CompanyTest.php::it_can_create_a_company --verbose
```

## Performance Considerations

### Test Optimization
- Use `RefreshDatabase` instead of manual cleanup
- Create minimal test data required for each test
- Group related tests in the same test class
- Use data providers for multiple similar test cases

### Memory Management
- SQLite in-memory database provides fast, isolated tests
- Each test gets a fresh database state
- No cleanup required between tests

## Continuous Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.2
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: vendor/bin/phpunit
```

## Contributing Test Guidelines

When contributing tests:

1. **Follow naming conventions** for test files and methods
2. **Include both positive and negative test cases**
3. **Test edge cases** and error conditions
4. **Use meaningful assertions** that clearly indicate what's being tested
5. **Keep tests focused** - one concept per test method
6. **Include documentation** for complex test scenarios

## Test Coverage Goals

The test suite aims to cover:

- ✅ **Model Creation and Validation** (90%+ coverage)
- ✅ **Relationship Testing** (90%+ coverage)
- ✅ **Hierarchy Functionality** (95%+ coverage)
- ✅ **Observer Behavior** (85%+ coverage)
- ✅ **Business Logic Validation** (90%+ coverage)
- ⏳ **Policy Authorization** (Planned)
- ⏳ **Command Testing** (Planned)
- ⏳ **Service Provider Registration** (Planned)

## Conclusion

This test suite provides comprehensive coverage of the BackOffice package functionality. The tests serve as both validation tools and documentation of expected behavior. Regular test execution ensures package reliability and helps prevent regressions during development.