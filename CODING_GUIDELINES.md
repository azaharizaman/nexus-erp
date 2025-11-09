# Laravel ERP - Coding Guidelines

This document outlines the coding standards and best practices for the Laravel ERP project. All code contributions must adhere to these guidelines to ensure consistency, maintainability, and code quality across the codebase.

## Table of Contents

- [PHP Standards](#php-standards)
- [Type Declarations](#type-declarations)
- [PHPDoc Documentation](#phpdoc-documentation)
- [Migration Standards](#migration-standards)
- [Common Mistakes and How to Avoid Them](#common-mistakes-and-how-to-avoid-them)

---

## PHP Standards

### 1. Strict Type Declarations

**✅ REQUIRED:** All PHP files MUST include `declare(strict_types=1);` immediately after the opening PHP tag.

#### ❌ Incorrect

```php
<?php

namespace App\Models;

class User extends Authenticatable
{
    // ...
}
```

#### ✅ Correct

```php
<?php

declare(strict_types=1);

namespace App\Models;

class User extends Authenticatable
{
    // ...
}
```

**Why:** Strict type declarations prevent type coercion errors and make the code more predictable by enforcing strict type checking at runtime.

**Applies to:** All PHP files including models, controllers, migrations, services, actions, enums, traits, and tests.

---

## Type Declarations

### 2. Method Parameter Type Hints

**✅ REQUIRED:** All method parameters MUST have explicit type declarations.

#### ❌ Incorrect

```php
public function scopeActive($query)
{
    return $query->where('status', TenantStatus::ACTIVE);
}
```

#### ✅ Correct

```php
public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
{
    return $query->where('status', TenantStatus::ACTIVE);
}
```

**Why:** Explicit type hints provide compile-time type checking, better IDE support, and self-documenting code.

### 3. Method Return Type Declarations

**✅ REQUIRED:** All methods MUST declare their return types.

#### ❌ Incorrect

```php
public function up()
{
    Schema::create('tenants', function (Blueprint $table) {
        // ...
    });
}

public function down()
{
    Schema::dropIfExists('tenants');
}
```

#### ✅ Correct

```php
public function up(): void
{
    Schema::create('tenants', function (Blueprint $table) {
        // ...
    });
}

public function down(): void
{
    Schema::dropIfExists('tenants');
}
```

**Why:** Return type declarations make the code more explicit, catch return type errors at compile time, and improve IDE autocomplete.

**Common return types:**
- `void` - For methods that don't return anything
- `bool` - For methods returning true/false
- `int`, `float`, `string`, `array` - For scalar types
- Class names - For methods returning objects (e.g., `User`, `Builder`)
- `?Type` - For nullable returns (e.g., `?User`)

---

## PHPDoc Documentation

### 4. Return Type Documentation

**✅ REQUIRED:** All public and protected methods MUST have PHPDoc blocks with `@return` annotations.

#### ❌ Incorrect

```php
/**
 * Get human-readable label for the status
 */
public function label(): string
{
    return match ($this) {
        self::ACTIVE => 'Active',
        self::SUSPENDED => 'Suspended',
        self::ARCHIVED => 'Archived',
    };
}
```

#### ✅ Correct

```php
/**
 * Get human-readable label for the status
 *
 * @return string
 */
public function label(): string
{
    return match ($this) {
        self::ACTIVE => 'Active',
        self::SUSPENDED => 'Suspended',
        self::ARCHIVED => 'Archived',
    };
}
```

**Why:** PHPDoc annotations provide additional context for documentation generators and IDE tooltips.

### 5. Complete PHPDoc Structure

**✅ RECOMMENDED:** Include all relevant PHPDoc tags for better documentation.

```php
/**
 * Adjust inventory stock level with audit trail
 *
 * @param InventoryItem $item The inventory item to adjust
 * @param float $quantity The adjustment quantity (positive or negative)
 * @param string $reason The reason for adjustment
 * @return bool True if adjustment successful
 * @throws InsufficientStockException If adjustment would result in negative stock
 * @throws InvalidQuantityException If quantity is zero or invalid
 */
public function execute(InventoryItem $item, float $quantity, string $reason): bool
{
    // Implementation
}
```

---

## Migration Standards

### 6. Migration Class Format

**✅ REQUIRED:** Use anonymous migration classes with `return new class extends Migration`.

#### ❌ Incorrect

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            // ...
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenants');
    }
}
```

#### ✅ Correct

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            // ...
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
```

**Why:** Anonymous migrations prevent class name conflicts, follow Laravel 12+ conventions, and are consistent with the framework's modern approach.

**Key differences:**
1. Use `return new class extends Migration` instead of named class
2. End with semicolon after closing brace (`;`)
3. Include `declare(strict_types=1);`
4. Declare return types as `: void`

---

## Common Mistakes and How to Avoid Them

### Mistake 1: Missing Strict Type Declaration

**Problem:** Files without `declare(strict_types=1);` allow implicit type conversions that can lead to bugs.

**Solution:** Always add `declare(strict_types=1);` after the opening PHP tag in every PHP file.

**Pre-commit check:**
```bash
# Check for missing strict types declarations
grep -L "declare(strict_types=1)" app/**/*.php database/**/*.php
```

### Mistake 2: Untyped Method Parameters

**Problem:** Methods without parameter type hints can accept any type, leading to runtime errors.

**Solution:** Always specify parameter types. Use IDE features or static analysis tools to identify missing type hints.

**Example of fixing:**
```php
// Before
public function process($data) { }

// After
public function process(array $data): void { }
```

### Mistake 3: Missing Return Type Declarations

**Problem:** Methods without return types can return unexpected values, causing hard-to-debug issues.

**Solution:** Always declare return types. Use `void` for methods that don't return anything.

**Example of fixing:**
```php
// Before
public function save($model) {
    return $model->save();
}

// After
public function save(Model $model): bool {
    return $model->save();
}
```

### Mistake 4: Incomplete PHPDoc Blocks

**Problem:** Missing `@return` or other PHPDoc tags reduce code documentation quality.

**Solution:** Write complete PHPDoc blocks for all public and protected methods.

**Template:**
```php
/**
 * Brief description of what the method does
 *
 * Longer description if needed, explaining complex logic,
 * business rules, or important considerations.
 *
 * @param Type $param Description of parameter
 * @return Type Description of return value
 * @throws ExceptionType When this exception is thrown
 */
```

### Mistake 5: Using Named Migration Classes

**Problem:** Named migration classes can cause conflicts and don't follow Laravel 12+ conventions.

**Solution:** Always use anonymous migrations with `return new class extends Migration`.

**Converting old migrations:**
```php
// Before
class CreateUsersTable extends Migration { }

// After
return new class extends Migration { };
```

---

## Code Review Checklist

Before submitting code for review, ensure:

- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have PHPDoc blocks with `@return` tags
- [ ] All migrations use anonymous class format
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain

---

## Automated Tools

### Laravel Pint

Run Laravel Pint to automatically fix code style issues:

```bash
./vendor/bin/pint
```

Pint will automatically format code according to Laravel's coding standards and PSR-12.

### PHPStan (Recommended)

Install and run PHPStan for static analysis:

```bash
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse app database
```

PHPStan will catch type errors, missing return types, and other issues before runtime.

---

## IDE Configuration

### PHPStorm

1. Enable strict type inspections:
   - Settings → Editor → Inspections → PHP → Type Compatibility
   - Enable "Missing @return tag" inspection
   - Enable "Missing parameter type declaration" inspection

2. Configure code style:
   - Settings → Editor → Code Style → PHP
   - Set from: Laravel (built-in)
   - Enable "Strict types declaration" in PHP inspections

### VS Code

1. Install PHP Intelephense extension
2. Add to `settings.json`:
```json
{
    "intelephense.diagnostics.strictTypes": true,
    "intelephense.diagnostics.typeErrors": true
}
```

---

## Additional Resources

- [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12/)
- [Laravel Documentation](https://laravel.com/docs)
- [PHP 8.2+ Type System](https://www.php.net/manual/en/language.types.declarations.php)
- [PHPDoc Standards](https://docs.phpdoc.org/guide/getting-started/your-first-set-of-documentation.html)

---

## Architecture Patterns

### 7. Repository Pattern

**✅ REQUIRED:** All data access operations MUST go through repository classes that implement repository contracts.

#### ❌ Incorrect

```php
class TenantManager
{
    public function create(array $data): Tenant
    {
        // Direct model access violates repository pattern
        return Tenant::create($data);
    }
}
```

#### ✅ Correct

```php
class TenantManager
{
    public function __construct(
        protected readonly TenantRepositoryContract $tenantRepository
    ) {}
    
    public function create(array $data): Tenant
    {
        // Use repository for data access
        return $this->tenantRepository->create($data);
    }
}
```

**Why:** The repository pattern abstracts data access logic, making code more testable, maintainable, and allows for easier database implementation changes.

**Steps to implement:**
1. Create a contract interface in `app/Domains/{Domain}/Contracts/`
2. Create a repository implementation in `app/Domains/{Domain}/Repositories/`
3. Bind the contract to implementation in a service provider
4. Inject the contract into services that need data access

---

## Security Best Practices

### 8. Authentication and Authorization

**✅ REQUIRED:** All operations that require authentication MUST check for authenticated users. All privileged operations MUST check authorization.

#### ❌ Incorrect

```php
public function impersonate(Tenant $tenant, string $reason): void
{
    // No authentication or authorization check
    activity()
        ->causedBy(auth()->user()) // Will fail if not authenticated
        ->log('Impersonation started');
}
```

#### ✅ Correct

```php
public function impersonate(Tenant $tenant, string $reason): void
{
    // Check authentication
    if (! auth()->check()) {
        throw new \RuntimeException('Impersonation requires an authenticated user');
    }
    
    // Check authorization
    if (! auth()->user()->can('impersonate-tenant', $tenant)) {
        throw new AuthorizationException('Unauthorized to impersonate this tenant');
    }
    
    // Now safe to use auth()->user()
    activity()
        ->causedBy(auth()->user())
        ->log('Impersonation started');
}
```

**Why:** Prevents runtime errors and security vulnerabilities by ensuring proper authentication and authorization checks.

**Common scenarios requiring checks:**
- Impersonation and privileged operations
- Audit logging with user context
- Operations that modify data on behalf of a user
- Support and administrative functions

---

### 9. Defensive Programming with Authentication

**✅ REQUIRED:** When using `auth()->user()` in methods that can be called without authentication, use defensive checks or conditional assignment.

#### ❌ Incorrect

```php
public function create(array $data): Tenant
{
    $tenant = Tenant::create($data);
    
    // Will fail if called from CLI, queue, or seeder
    activity()
        ->performedOn($tenant)
        ->causedBy(auth()->user())
        ->log('Tenant created');
        
    return $tenant;
}
```

#### ✅ Correct

```php
public function create(array $data): Tenant
{
    $tenant = $this->repository->create($data);
    
    // Defensive approach: check authentication first
    $activity = activity()->performedOn($tenant);
    
    if (auth()->check()) {
        $activity->causedBy(auth()->user());
    }
    
    $activity->log('Tenant created');
    
    return $tenant;
}
```

**Alternative approach for optional fields:**

```php
$context = [
    'action' => 'impersonation',
    'started_at' => now(),
];

// Only add user_id if authenticated
if (auth()->check()) {
    $context['user_id'] = auth()->id();
}
```

**Why:** Allows methods to work in multiple contexts (web requests, CLI commands, queued jobs, seeders) without throwing exceptions.

---

## Validation Best Practices

### 10. Complete Validation Rules

**✅ REQUIRED:** All fields that can be provided by users MUST have validation rules, even if they have defaults or are optional.

#### ❌ Incorrect

```php
$validator = Validator::make($data, [
    'name' => ['required', 'string'],
    'email' => ['required', 'email'],
    // Missing 'status' field validation even though it's in fillable
]);

// Setting default without validation
$validatedData['status'] = $validatedData['status'] ?? TenantStatus::ACTIVE;
```

#### ✅ Correct

```php
$validator = Validator::make($data, [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'max:255'],
    'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
]);

// Now status is validated if provided
$validatedData['status'] = $validatedData['status'] ?? TenantStatus::ACTIVE;
```

**Why:** Prevents invalid data from bypassing validation and ensures data integrity at the application level.

**Best practices:**
- Validate all fillable fields
- Use `Rule::in()` for enum values
- Include `max` constraints for strings
- Use `nullable` for optional fields
- Validate arrays and nested data structures

---

## PHPDoc Standards

### 11. Accurate Exception Documentation

**✅ REQUIRED:** The `@throws` tag in PHPDoc MUST match the actual exceptions thrown by the method.

#### ❌ Incorrect

```php
/**
 * Create a new tenant
 * 
 * @throws \InvalidArgumentException If validation fails
 */
public function create(array $data): Tenant
{
    $validator = Validator::make($data, [...]);
    
    if ($validator->fails()) {
        // Actually throws ValidationException, not InvalidArgumentException
        throw new ValidationException($validator);
    }
}
```

#### ✅ Correct

```php
/**
 * Create a new tenant
 * 
 * @throws \Illuminate\Validation\ValidationException If validation fails
 */
public function create(array $data): Tenant
{
    $validator = Validator::make($data, [...]);
    
    if ($validator->fails()) {
        throw new ValidationException($validator);
    }
}
```

**Why:** Accurate documentation helps developers understand error handling and use try-catch blocks correctly.

---

## Testing Best Practices

### 12. Performance Testing

**✅ RECOMMENDED:** When testing performance constraints, test individual operations rather than averages.

#### ❌ Incorrect

```php
public function test_performance(): void
{
    $start = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $this->service->operation();
    }
    
    $avgTime = ((microtime(true) - $start) * 1000) / 100;
    
    // Testing average can miss slow individual calls
    $this->assertLessThan(10, $avgTime);
}
```

#### ✅ Correct

```php
public function test_performance(): void
{
    $maxAllowed = 10; // milliseconds
    
    for ($i = 0; $i < 100; $i++) {
        $start = microtime(true);
        $this->service->operation();
        $duration = (microtime(true) - $start) * 1000;
        
        // Test each individual call
        $this->assertLessThan(
            $maxAllowed,
            $duration,
            "Iteration {$i} took {$duration}ms, should be under {$maxAllowed}ms"
        );
    }
}
```

**Why:** Testing averages can hide slow individual operations. Testing each call ensures consistent performance.

**Note:** Performance tests in unit/integration suites can be unreliable due to system load. Consider dedicated performance test suites for critical paths.

---

## Common Mistakes and How to Avoid Them

### Mistake 6: Direct Model Access in Services

**Problem:** Services directly using `Model::create()` or `Model::find()` instead of repositories.

**Solution:** Always use repository pattern for data access.

```php
// Before
class TenantManager
{
    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }
}

// After
class TenantManager
{
    public function __construct(
        protected readonly TenantRepositoryContract $repository
    ) {}
    
    public function create(array $data): Tenant
    {
        return $this->repository->create($data);
    }
}
```

### Mistake 7: Missing Authentication Checks

**Problem:** Using `auth()->user()` without checking if user is authenticated.

**Solution:** Always check `auth()->check()` before accessing `auth()->user()`.

```php
// Before
$activity->causedBy(auth()->user()); // Fails if not authenticated

// After
if (auth()->check()) {
    $activity->causedBy(auth()->user());
}
```

### Mistake 8: Missing Authorization for Privileged Operations

**Problem:** Allowing any authenticated user to perform privileged operations like impersonation.

**Solution:** Add authorization checks using gates or policies.

```php
// Before
public function impersonate(Tenant $tenant): void
{
    // Anyone can impersonate!
}

// After
public function impersonate(Tenant $tenant): void
{
    if (! auth()->user()->can('impersonate-tenant', $tenant)) {
        throw new AuthorizationException('Unauthorized');
    }
}
```

### Mistake 9: Incomplete Validation Rules

**Problem:** Not validating all user-provided fields, especially optional ones.

**Solution:** Add validation for all fillable fields, using `nullable` for optional ones.

```php
// Before
'name' => ['required'],
// Missing status validation

// After
'name' => ['required', 'string', 'max:255'],
'status' => ['nullable', 'string', Rule::in(TenantStatus::values())],
```

### Mistake 10: Incorrect PHPDoc Exception Types

**Problem:** Documenting wrong exception types in `@throws` tags.

**Solution:** Match `@throws` documentation with actual thrown exceptions.

```php
// Before
/** @throws \InvalidArgumentException */
throw new ValidationException($validator);

// After
/** @throws \Illuminate\Validation\ValidationException */
throw new ValidationException($validator);
```

---

## Code Review Checklist

Before submitting code for review, ensure:

- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have PHPDoc blocks with `@return` tags
- [ ] All migrations use anonymous class format
- [ ] Data access uses repository pattern (no direct `Model::create()` or `Model::find()` in services)
- [ ] Authentication checks before using `auth()->user()`
- [ ] Authorization checks for privileged operations
- [ ] Complete validation rules for all fillable fields
- [ ] Accurate `@throws` tags in PHPDoc matching actual exceptions
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain

---

## Middleware Best Practices

### 13. Avoid N+1 Queries in Middleware

**✅ REQUIRED:** Middleware should avoid triggering lazy loading that causes N+1 queries, especially for operations that run on every request.

#### ❌ Incorrect

```php
public function handle(Request $request, Closure $next): Response
{
    $user = auth()->user();
    
    // This triggers an N+1 query via lazy loading
    $tenant = $user->tenant;
    
    $this->tenantManager->setActive($tenant);
    return $next($request);
}
```

#### ✅ Correct

```php
public function handle(Request $request, Closure $next): Response
{
    $user = auth()->user();
    
    // Use direct query to avoid N+1
    $tenant = Tenant::find($user->tenant_id);
    
    $this->tenantManager->setActive($tenant);
    return $next($request);
}
```

**Why:** Middleware runs on every request. N+1 queries in middleware multiply across all requests, causing significant performance degradation. Always use direct queries or ensure relationships are eager loaded.

**Alternative approaches:**
1. Use `Tenant::find($user->tenant_id)` for direct lookup
2. Eager load on User model with `protected $with = ['tenant']` if needed globally
3. Use caching for frequently accessed tenant data

---

### 14. Middleware Ordering and Responsibilities

**✅ REQUIRED:** Middleware should not duplicate responsibilities already handled by other middleware. Document dependencies clearly.

#### ❌ Incorrect

```php
// IdentifyTenant middleware performing authentication
public function handle(Request $request, Closure $next): Response
{
    if (! auth()->check()) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
    // ... tenant resolution
}

// No comment about dependency on auth middleware
```

#### ✅ Correct

```php
// IdentifyTenant middleware with clear documentation
/**
 * This middleware should be applied after authentication middleware (e.g., auth:sanctum).
 */
public function handle(Request $request, Closure $next): Response
{
    // Note: This check is redundant if auth middleware is properly applied
    // but provides a safety net for misconfigured routes
    if (! auth()->check()) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
    // ... tenant resolution
}

// In bootstrap/app.php
$middleware->api(append: [
    IdentifyTenant::class, // Runs after auth:sanctum in API middleware stack
]);
```

**Why:** Clear middleware ordering prevents bugs and ensures each middleware has a single responsibility. Documentation helps future developers understand dependencies.

---

### 15. Complete PHPDoc for Middleware

**✅ REQUIRED:** Middleware PHPDoc must document all possible return conditions, especially error responses.

#### ❌ Incorrect

```php
/**
 * Handle an incoming request
 *
 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
 */
public function handle(Request $request, Closure $next): Response
```

#### ✅ Correct

```php
/**
 * Handle an incoming request
 *
 * Resolves tenant from authenticated user and sets it in the TenantManager.
 * This middleware should be applied after authentication middleware (e.g., auth:sanctum).
 *
 * Returns error responses for different failure modes:
 * - 401 Unauthenticated: if no user is authenticated
 * - 403 Forbidden: if user has no tenant_id
 * - 404 Not Found: if tenant cannot be resolved from database
 *
 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
 * @return Response
 */
public function handle(Request $request, Closure $next): Response
```

**Why:** Complete documentation helps developers understand all possible outcomes and error conditions.

---

## Testing Best Practices (Continued)

### 16. Unit vs Feature Test Classification

**✅ REQUIRED:** Unit tests should not depend on database or external systems. Tests using database should be feature/integration tests.

#### ❌ Incorrect

```php
// In tests/Unit/Support/Helpers/TenantHelperTest.php
class TenantHelperTest extends TestCase
{
    use RefreshDatabase; // Unit tests should not use database
    
    public function test_helper_returns_tenant(): void
    {
        $tenant = Tenant::factory()->create(); // Creating database records
        // ...
    }
}
```

#### ✅ Correct

Option 1: Move to feature tests
```php
// In tests/Feature/Support/Helpers/TenantHelperTest.php
class TenantHelperTest extends TestCase
{
    use RefreshDatabase; // OK for feature tests
    
    public function test_helper_returns_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        // ...
    }
}
```

Option 2: Use mocking for true unit tests
```php
// In tests/Unit/Support/Helpers/TenantHelperTest.php
class TenantHelperTest extends TestCase
{
    public function test_helper_returns_tenant(): void
    {
        $mockTenant = Mockery::mock(Tenant::class);
        $mockManager = Mockery::mock(TenantManagerContract::class);
        $mockManager->shouldReceive('current')->andReturn($mockTenant);
        // ...
    }
}
```

**Why:** Unit tests should be fast, isolated, and not depend on external state. Database dependencies make tests slower and more fragile.

---

### 17. Avoid Hard-Coded Values in Tests

**✅ REQUIRED:** Tests should avoid hard-coded values that might conflict with actual data or be environment-dependent.

#### ❌ Incorrect

```php
public function test_handles_missing_tenant(): void
{
    // Hard-coded ID might conflict with actual records
    $user = User::factory()->create(['tenant_id' => 99999]);
}
```

#### ✅ Correct

```php
public function test_handles_missing_tenant(): void
{
    // Generate guaranteed non-existent ID
    $nonExistentTenantId = Tenant::max('id') + 1;
    $user = User::factory()->create(['tenant_id' => $nonExistentTenantId]);
}
```

**Why:** Hard-coded values can cause test failures in different environments or with parallel test execution.

---

### 18. Performance Tests Should Be Flexible

**❌ AVOID:** Hard-coded time limits in performance tests that run in CI environments.

```php
public function test_performance(): void
{
    $maxAllowedMs = 10; // Too strict for CI
    
    for ($i = 0; $i < 100; $i++) {
        $start = microtime(true);
        $this->operation();
        $duration = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan($maxAllowedMs, $duration); // Will fail on slow CI
    }
}
```

**✅ RECOMMENDED:** Remove performance tests from unit/feature suites or make them configurable.

```php
// Option 1: Skip in CI
public function test_performance(): void
{
    if (env('CI')) {
        $this->markTestSkipped('Performance test skipped in CI environment');
    }
    // ... test code
}

// Option 2: Use environment-based thresholds
public function test_performance(): void
{
    $maxAllowedMs = env('PERF_THRESHOLD_MS', 50); // More lenient default
    // ... test code
}

// Option 3: Remove from regular test suite (best approach)
// Create dedicated performance benchmarking suite
```

**Why:** CI environments often have variable performance characteristics. Hard-coded time limits cause flaky tests that fail intermittently.

**Alternative:** Use dedicated performance testing tools like:
- Laravel Dusk for browser performance
- Apache JMeter for load testing
- Custom benchmark scripts for specific operations

---

## Documentation Standards (Continued)

### 19. Qualify Performance Claims

**✅ REQUIRED:** Performance metrics in documentation must be qualified with conditions and disclaimers.

#### ❌ Incorrect

```markdown
## Performance

- Middleware execution: < 10ms per request
- Helper function: < 5ms per call
```

#### ✅ Correct

```markdown
## Performance

- Middleware execution: typically < 10ms per request under normal load
- Helper function: typically < 5ms per call under normal load
- Single direct database query per request (Tenant::find())
- Performance may vary based on server resources and database latency
```

**Why:** Absolute performance claims are misleading. Performance varies by environment, load, and configuration.

---

## Common Mistakes and How to Avoid Them (Continued)

### Mistake 11: N+1 Queries in Middleware

**Problem:** Using lazy loading in middleware that runs on every request.

**Solution:** Use direct queries or eager loading.

```php
// Before
$tenant = $user->tenant; // N+1 query

// After
$tenant = Tenant::find($user->tenant_id); // Direct query
```

### Mistake 12: Performance Tests in Test Suites

**Problem:** Including time-sensitive performance tests in regular test suites.

**Solution:** Remove performance tests or make them optional/environment-aware.

```php
// Before
$this->assertLessThan(10, $duration); // Fails in CI

// After - Option 1: Remove the test
// After - Option 2: Skip in CI
if (env('CI')) {
    $this->markTestSkipped('Performance test skipped in CI');
}
```

### Mistake 13: Unit Tests with Database Dependencies

**Problem:** Placing tests that use database in Unit test directory.

**Solution:** Move to Feature tests or use mocking.

```php
// Before: tests/Unit/MyTest.php
use RefreshDatabase;

// After: tests/Feature/MyTest.php
use RefreshDatabase;
// OR use mocking to keep in Unit tests
```

### Mistake 14: Incomplete Middleware Documentation

**Problem:** Not documenting all error responses and dependencies in middleware PHPDoc.

**Solution:** Document all return conditions, error codes, and middleware dependencies.

```php
/**
 * Returns error responses for different failure modes:
 * - 401 Unauthenticated: if no user is authenticated
 * - 403 Forbidden: if user has no tenant_id
 * - 404 Not Found: if tenant cannot be resolved
 *
 * This middleware should be applied after authentication middleware.
 */
```

---

## Code Review Checklist (Updated)

Before submitting code for review, ensure:

- [ ] All PHP files have `declare(strict_types=1);`
- [ ] All method parameters have type hints
- [ ] All methods declare return types
- [ ] All public/protected methods have complete PHPDoc blocks with `@return` tags
- [ ] PHPDoc documents all error responses and conditions
- [ ] All migrations use anonymous class format
- [ ] Data access uses repository pattern (no direct `Model::create()` or `Model::find()` in services)
- [ ] Authentication checks before using `auth()->user()`
- [ ] Authorization checks for privileged operations
- [ ] Complete validation rules for all fillable fields
- [ ] Accurate `@throws` tags in PHPDoc matching actual exceptions
- [ ] **No N+1 queries in middleware or frequently-called code**
- [ ] **Middleware dependencies and ordering documented**
- [ ] **Unit tests do not use database (or are in Feature directory)**
- [ ] **No hard-coded IDs or values in tests**
- [ ] **No performance tests with hard time limits in regular test suites**
- [ ] **Performance claims in documentation are qualified**
- [ ] Code passes Laravel Pint formatting (`./vendor/bin/pint`)
- [ ] All tests pass (`php artisan test`)
- [ ] No untyped variables or parameters remain

---

## Questions or Suggestions

If you have questions about these guidelines or suggestions for improvements, please:
1. Open an issue in the repository
2. Discuss in the team chat
3. Propose changes via pull request

**Last Updated:** November 9, 2025
