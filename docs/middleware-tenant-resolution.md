# Tenant Resolution Middleware

This document describes the implementation of the `IdentifyTenant` middleware for Phase 4 of the multi-tenancy infrastructure.

## Overview

The `IdentifyTenant` middleware resolves the current tenant from the authenticated user and injects it into the request lifecycle, making tenant context available throughout the application.

## Components

### 1. IdentifyTenant Middleware

**Location:** `app/Domains/Core/Middleware/IdentifyTenant.php`

**Purpose:** Resolves tenant from authenticated user and sets context in TenantManager

**Features:**
- Validates user authentication (401 if not authenticated)
- Verifies user has tenant_id (403 if missing)
- Resolves tenant from database (404 if not found)
- Sets tenant context via TenantManager for request lifecycle
- Graceful error handling with proper HTTP status codes

**Usage:** Automatically applied to all API routes via `bootstrap/app.php`

### 2. tenant() Helper Function

**Location:** `app/Support/Helpers/tenant.php`

**Purpose:** Convenience helper to access current tenant context

**Usage:**
```php
// Get current tenant anywhere in the application
$currentTenant = tenant();

if ($currentTenant) {
    echo "Current tenant: {$currentTenant->name}";
}
```

**Returns:** `Tenant|null` - Current tenant or null if not set

## Registration

### Middleware Registration

The middleware is registered in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    // Register IdentifyTenant middleware for API routes
    $middleware->api(append: [
        IdentifyTenant::class,
    ]);
})
```

### Helper Registration

The helper function is registered in `composer.json`:

```json
"autoload": {
    "files": [
        "app/Support/Helpers/tenant.php"
    ]
}
```

## Request Flow

1. **Request arrives** at API endpoint
2. **Authentication check** - Middleware verifies user is authenticated
3. **Tenant ID check** - Middleware verifies user has tenant_id
4. **Tenant resolution** - Middleware loads tenant from database
5. **Context injection** - Middleware sets tenant in TenantManager
6. **Request processing** - Controller/action executes with tenant context
7. **Helper access** - Any code can call `tenant()` to get current tenant

## Error Handling

### 401 Unauthenticated
```json
{
    "message": "Unauthenticated."
}
```
Returned when no authenticated user in request.

### 403 Forbidden
```json
{
    "message": "User does not belong to any tenant."
}
```
Returned when user has no tenant_id.

### 404 Not Found
```json
{
    "message": "Tenant not found."
}
```
Returned when tenant_id exists but tenant not in database.

## Testing

### Feature Tests
**Location:** `tests/Feature/Domains/Core/Middleware/IdentifyTenantTest.php`

Tests cover:
- ✓ Middleware resolves tenant from authenticated user
- ✓ Returns 401 for unauthenticated requests
- ✓ Returns 403 for users without tenant
- ✓ Returns 404 for non-existent tenants
- ✓ Tenant context persists through request lifecycle
- ✓ Different users get different tenant contexts
- ✓ Integration with API routes
- ✓ Performance under 10ms per request

### Unit Tests
**Location:** `tests/Unit/Support/Helpers/TenantHelperTest.php`

Tests cover:
- ✓ Helper returns current tenant
- ✓ Helper returns null when no tenant set
- ✓ Helper reflects tenant changes
- ✓ Helper works with impersonation
- ✓ Helper can access tenant properties
- ✓ Performance under 5ms per call
- ✓ Consistency across multiple calls

## Integration Points

### With TenantManager
The middleware uses `TenantManagerContract` to set the active tenant:

```php
$this->tenantManager->setActive($tenant);
```

### With Authentication
Uses Laravel's built-in authentication:

```php
auth()->check()  // Check if authenticated
auth()->user()   // Get authenticated user
```

### With User Model
Accesses tenant relationship:

```php
$user->tenant_id  // Get tenant ID
$user->tenant     // Load tenant model
```

## Security Considerations

1. **Authentication Required** - All API routes with middleware require authentication
2. **Tenant Validation** - Ensures user belongs to a valid tenant
3. **Context Isolation** - Each request has isolated tenant context
4. **Authorization** - Tenant context enables data scoping for authorization

## Performance

- Middleware execution: < 10ms per request
- Helper function: < 5ms per call
- Minimal database queries (user already loaded during auth)
- Tenant cached in application container per request

## Future Enhancements

Potential improvements for future phases:

1. **Caching** - Cache tenant data to reduce database queries
2. **Multi-tenancy Modes** - Support different tenant resolution strategies
3. **Tenant Switching** - Support for users with access to multiple tenants
4. **Audit Logging** - Log tenant context changes for compliance
5. **Metrics** - Track tenant-specific metrics and usage

## Related Documentation

- [PRD-01: Infrastructure & Multi-tenancy](../../../plan/PRD-01-infrastructure-multitenancy-1.md)
- [TenantManager Service](../../app/Domains/Core/Services/TenantManager.php)
- [BelongsToTenant Trait](../../app/Domains/Core/Traits/BelongsToTenant.php)
- [TenantScope](../../app/Domains/Core/Scopes/TenantScope.php)

## Acceptance Criteria

- ✅ Middleware resolves tenant from authenticated user
- ✅ Tenant context set in TenantManager for request lifecycle
- ✅ Missing tenant handled with proper error response
- ✅ Middleware registered in HTTP kernel (bootstrap/app.php)
- ✅ Helper function provides easy access to current tenant
- ✅ Middleware applied to all authenticated routes (API middleware group)
