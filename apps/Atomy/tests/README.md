# Monorepo Test Suite

This directory contains the root-level test configuration for running tests across all apps and packages in the Laravel ERP monorepo.

## Directory Structure

```
/workspaces/laravel-erp/
├── tests/                          # Root-level test configuration
│   ├── Pest.php                    # Pest configuration
│   ├── TestCase.php                # Base test case
│   └── README.md                   # This file
├── apps/
│   └── headless-erp-app/
│       └── tests/                  # App-specific tests
│           ├── Unit/
│           ├── Feature/
│           └── Integration/
└── packages/
    ├── audit-logging/
    │   └── tests/                  # Package-specific tests
    ├── serial-numbering/
    │   └── tests/
    └── settings-management/
        └── tests/
```

## Running Tests

### Run All Tests (Entire Monorepo)

```bash
# Using Pest (recommended)
vendor/bin/pest

# Using PHPUnit
vendor/bin/phpunit

# With code coverage
vendor/bin/pest --coverage
vendor/bin/phpunit --coverage-html coverage
```

### Run Tests by Test Suite

```bash
# Run specific app tests
vendor/bin/pest apps/headless-erp-app/tests
vendor/bin/phpunit --testsuite=HeadlessErpApp

# Run specific package tests
vendor/bin/pest packages/audit-logging/tests
vendor/bin/phpunit --testsuite=AuditLogging

# Run by test type
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
vendor/bin/phpunit --testsuite=Integration
```

### Run Tests by Path

```bash
# Run specific test directory
vendor/bin/pest apps/headless-erp-app/tests/Unit/UnitOfMeasure

# Run specific test file
vendor/bin/pest apps/headless-erp-app/tests/Unit/UnitOfMeasure/UomTest.php

# Run specific test
vendor/bin/pest apps/headless-erp-app/tests/Unit/UnitOfMeasure/UomTest.php --filter="test_UomCategory_enum_has_all_6_categories"
```

### Run Tests in Parallel

```bash
# Pest parallel execution
vendor/bin/pest --parallel

# PHPUnit parallel execution (requires paratest)
vendor/bin/paratest
```

### Run Tests with Options

```bash
# Verbose output
vendor/bin/pest --verbose
vendor/bin/phpunit --verbose

# Stop on first failure
vendor/bin/pest --stop-on-failure
vendor/bin/phpunit --stop-on-failure

# Disable Xdebug warnings
XDEBUG_MODE=off vendor/bin/pest
XDEBUG_MODE=off vendor/bin/phpunit
```

## PHPUnit Test Suites

The following test suites are defined in `phpunit.xml`:

### By Application/Package

- **Root**: Root-level integration tests (if any)
- **HeadlessErpApp**: Main application tests
- **AuditLogging**: Audit logging package tests
- **SerialNumbering**: Serial numbering package tests
- **SettingsManagement**: Settings management package tests

### By Test Type (Backward Compatibility)

- **Unit**: All unit tests across apps and packages
- **Feature**: All feature tests across apps and packages
- **Integration**: All integration tests across apps and packages

## Test Organization Guidelines

### Apps

Each app in `apps/` should have its own `tests/` directory with:

```
apps/{app-name}/tests/
├── Pest.php              # App-specific Pest configuration
├── TestCase.php          # App-specific base test case
├── Unit/                 # Unit tests
├── Feature/              # Feature tests
└── Integration/          # Integration tests
```

### Packages

Each package in `packages/` should have its own `tests/` directory with:

```
packages/{package-name}/tests/
├── Pest.php              # Package-specific Pest configuration
├── TestCase.php          # Package-specific base test case (optional)
├── Unit/                 # Unit tests
├── Feature/              # Feature tests
└── Integration/          # Integration tests
```

## Environment Configuration

Test environment variables are configured in `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
</php>
```

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
          php-version: '8.3'
          extensions: mbstring, xml, ctype, json, bcmath
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run test suite
        run: vendor/bin/pest --coverage --min=80
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

## Tips and Best Practices

### 1. Run Tests Before Committing

```bash
# Quick check
vendor/bin/pest --stop-on-failure

# Full check with coverage
vendor/bin/pest --coverage --min=80
```

### 2. Run Specific Tests During Development

```bash
# When working on UOM feature
vendor/bin/pest apps/headless-erp-app/tests/Unit/UnitOfMeasure --watch

# When working on a package
vendor/bin/pest packages/audit-logging/tests
```

### 3. Use Test Filters for Debugging

```bash
# Run only tests matching pattern
vendor/bin/pest --filter="Uom"

# Run only tests in specific group
vendor/bin/pest --group=uom
```

### 4. Generate Coverage Reports

```bash
# HTML coverage report
vendor/bin/pest --coverage-html coverage

# Open in browser
open coverage/index.html
```

### 5. Profile Slow Tests

```bash
# Show test execution times
vendor/bin/pest --profile

# Show only slow tests
vendor/bin/pest --profile --min=100
```

## Common Issues

### 1. "Class not found" errors

Make sure composer autoload is up to date:
```bash
composer dump-autoload
```

### 2. Database connection errors

Ensure SQLite is installed for in-memory testing:
```bash
php -m | grep sqlite
```

### 3. Xdebug warnings

Disable Xdebug during testing:
```bash
XDEBUG_MODE=off vendor/bin/pest
```

Or configure `xdebug.mode=off` in `php.ini`.

### 4. Memory limit errors

Increase PHP memory limit:
```bash
php -d memory_limit=512M vendor/bin/pest
```

## Additional Resources

- [Pest Documentation](https://pestphp.com/)
- [PHPUnit Documentation](https://phpunit.de/)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Project Coding Guidelines](/CODING_GUIDELINES.md)

---

**Last Updated:** November 12, 2025
