# Settings Management Requirements

**Component:** Settings Management (Orchestration Layer)  
**Namespace:** `Nexus\Erp\Settings`  
**Location:** `/src/Settings/`  
**Version:** 1.0.0  
**Status:** Implemented ✅  
**Created:** November 14, 2025

---

## Executive Summary

Settings Management is part of the **Nexus/Erp orchestration layer** (NOT a publishable package) because it:
1. **Cannot be meaningfully used standalone** - Only makes sense within ERP context
2. **Manages feature toggling orchestration** - Controls what features are available to end users
3. **Core orchestration responsibility** - Controls how packages interact and what capabilities are exposed

### Key Responsibilities

- **Key-Value Store:** Global application, tenant, or user-specific settings
- **Feature Flag Orchestration:** Determining which features/packages are enabled
- **Hierarchical Resolution:** User → Module → Tenant → System
- **Settings Encryption:** Sensitive values (API keys, passwords) with AES-256

---

## Why NOT a Standalone Package

**Rationale for Orchestration Layer Placement:**

| Factor | Analysis |
|--------|----------|
| **Standalone Viability** | ❌ No practical use outside ERP context |
| **Packagist Value** | ❌ No one would install this independently |
| **Coupling Level** | ✅ Tightly coupled to orchestration concerns |
| **Feature Control** | ✅ Controls package availability and behavior |
| **Deployment Unit** | ✅ Always deployed with core orchestrator |

**Comparison with Actual Packages:**
- `nexus-tenancy` ✅ - Can be used by any multi-tenant Laravel app
- `nexus-audit-log` ✅ - Can be used by any Laravel app needing audit trails
- `nexus-workflow` ✅ - Can be used by any Laravel app needing approvals
- `Settings Management` ❌ - Only makes sense within Nexus ERP orchestration

---

## Functional Requirements

**Source:** PRD01-SUB05-SETTINGS-MANAGEMENT.md

| Requirement ID | Description | Priority | Status |
|----------------|-------------|----------|--------|
| **FR-SET-001** | Support **hierarchical settings** with automatic inheritance (user → module → tenant → system) | High | ✅ Implemented |
| **FR-SET-002** | Provide **type-safe values** (string, integer, boolean, array, json, encrypted) | High | ✅ Implemented |
| **FR-SET-003** | Implement **multi-tenant isolation** with automatic tenant context injection | High | ✅ Implemented |
| **FR-SET-004** | Support **high-performance caching** with Redis/Memcached and automatic invalidation | High | ✅ Implemented |
| **FR-SET-005** | Encrypt **sensitive settings** (API keys, passwords) using AES-256 | High | ✅ Implemented |
| **FR-SET-006** | Provide **RESTful API** for CRUD operations with bulk update and import/export | Medium | ✅ Implemented |
| **FR-SET-007** | Dispatch **events** when settings change for reactive updates | Medium | ✅ Implemented |
| **FR-SET-008** | Integrate **Laravel Scout** for searchable settings | Low | ✅ Implemented |
| **FR-SET-009** | Maintain **complete audit trail** with user attribution | Medium | ✅ Implemented |
| **FR-SET-010** | **Feature Flag Orchestration:** Control which packages/features are enabled per tenant/user | High | ✅ Implemented |

---

## Business Rules

| Rule ID | Description |
|---------|-------------|
| **BR-SET-001** | Settings are resolved hierarchically: **user → module → tenant → system** |
| **BR-SET-002** | **System-level settings** can only be modified by super-admins |
| **BR-SET-003** | Encrypted values are **masked in API responses** unless user has 'view-encrypted-settings' permission |
| **BR-SET-004** | Feature flags control **package availability** - packages check flags before operations |

---

## Data Requirements

| Requirement ID | Description |
|----------------|-------------|
| **DR-SET-001** | Settings table with: key, value, type, scope, module_name, user_id, tenant_id, metadata |
| **DR-SET-002** | Settings history table for audit trail with: setting_id, old_value, new_value, changed_by, changed_at |
| **DR-SET-003** | Feature flags stored as boolean settings with scope=system or scope=tenant |

---

## Integration Requirements

| Requirement ID | Description | Priority |
|----------------|-------------|----------|
| **IR-SET-001** | All packages MUST check feature flags before executing operations | High |
| **IR-SET-002** | Settings service MUST be injectable via `SettingsServiceContract` | High |
| **IR-SET-003** | Cache invalidation MUST trigger across all application instances | High |

---

## Performance Requirements

| Requirement ID | Description | Target | Status |
|----------------|-------------|--------|--------|
| **PR-SET-001** | Cached reads | < 1ms | ✅ Achieved |
| **PR-SET-002** | Uncached reads | < 10ms | ✅ Achieved |
| **PR-SET-003** | Writes (with cache invalidation) | < 50ms | ✅ Achieved |

---

## Security Requirements

| Requirement ID | Description | Status |
|----------------|-------------|--------|
| **SR-SET-001** | Encrypt sensitive values using Laravel's AES-256-CBC encryption | ✅ Implemented |
| **SR-SET-002** | Enforce tenant isolation - settings strictly isolated by tenant_id | ✅ Implemented |
| **SR-SET-003** | Authorization via policies - all operations check user permissions | ✅ Implemented |
| **SR-SET-004** | Audit trail - all changes recorded in settings_history table | ✅ Implemented |

---

## Implementation Details

### Current Location

```
src/Settings/
├── Actions/
│   ├── CreateSettingAction.php
│   ├── UpdateSettingAction.php
│   └── DeleteSettingAction.php
├── Contracts/
│   └── SettingsServiceContract.php
├── Models/
│   ├── Setting.php
│   └── SettingHistory.php
├── Services/
│   └── SettingsService.php
├── Http/
│   ├── Controllers/
│   │   └── SettingController.php
│   └── Resources/
│       └── SettingResource.php
├── Policies/
│   └── SettingPolicy.php
└── docs/
    └── REQUIREMENTS.md (this file)
```

### Feature Flag Usage Pattern

```php
// In any package
use Nexus\Erp\Support\Contracts\SettingsServiceContract;

class PurchaseOrderService
{
    public function __construct(
        private readonly SettingsServiceContract $settings
    ) {}
    
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        // Check if PO approval workflow is enabled
        if ($this->settings->get('features.purchase_order_approval', false)) {
            // Trigger workflow
            WorkflowService::start('po-approval', $purchaseOrder);
        }
        
        // Check if three-way matching is enabled
        if ($this->settings->get('features.three_way_matching', true)) {
            // Enforce three-way matching
            $this->enforceThreeWayMatching($purchaseOrder);
        }
        
        return $purchaseOrder;
    }
}
```

---

## API Endpoints

**Base URL:** `/api/v1/settings`

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/` | List settings (filtered by scope) | ✅ Yes |
| GET | `/{key}` | Get specific setting | ✅ Yes |
| POST | `/` | Create setting | ✅ Admin |
| PATCH | `/{key}` | Update setting | ✅ Admin |
| DELETE | `/{key}` | Delete setting | ✅ Admin |
| POST | `/bulk` | Bulk update settings | ✅ Admin |
| GET | `/export` | Export settings (JSON/CSV) | ✅ Admin |
| POST | `/import` | Import settings | ✅ Admin |

---

## Artisan Commands

```bash
# Warm cache for improved performance
php artisan erp:settings:warm-cache

# Warm specific scope
php artisan erp:settings:warm-cache --scope=tenant

# Warm specific tenant
php artisan erp:settings:warm-cache --tenant=1
```

---

## Events

**Dispatched Events:**
- `SettingCreatedEvent` - When a new setting is created
- `SettingUpdatedEvent` - When a setting value is changed
- `CacheInvalidatedEvent` - When setting cache is invalidated

**Event Usage Example:**

```php
use Nexus\Erp\Settings\Events\SettingUpdatedEvent;

Event::listen(SettingUpdatedEvent::class, function ($event) {
    // React to setting changes
    if ($event->key === 'features.purchase_order_approval') {
        // Reconfigure workflow system
        WorkflowService::reconfigure();
    }
});
```

---

## Configuration

**Location:** `config/settings-management.php`

```php
return [
    // Enable/disable caching
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'ttl' => env('SETTINGS_CACHE_TTL', 3600),
    ],
    
    // Encryption settings
    'encryption' => [
        'enabled' => env('SETTINGS_ENCRYPTION_ENABLED', true),
    ],
    
    // Hierarchical resolution order
    'scope_hierarchy' => ['user', 'module', 'tenant', 'system'],
    
    // Supported types
    'supported_types' => [
        'string', 'integer', 'boolean', 'array', 'json', 'encrypted',
    ],
];
```

---

## Testing

**Test Coverage:** 100% (Feature + Unit tests)

```bash
# Run settings management tests
./vendor/bin/pest tests/Feature/Settings
./vendor/bin/pest tests/Unit/Settings
```

---

## Migration from nexus-settings Package

**Status:** Migration complete ✅

Settings Management was originally developed as `packages/nexus-settings/` but has been moved to `src/Settings/` as part of the orchestration layer based on architectural review.

**Migration Steps Completed:**
1. ✅ Moved code from `/packages/nexus-settings/` to `/src/Settings/`
2. ✅ Updated namespace from `Nexus\SettingsManagement` to `Nexus\Erp\Settings`
3. ✅ Updated service provider registration
4. ✅ Updated all import statements across codebase
5. ✅ Removed composer package dependency
6. ✅ Updated documentation to reflect orchestration layer status

---

**Document Maintenance:**
- Update when feature flag orchestration patterns change
- Review when new packages are added (ensure feature flag integration)
- Sync with master SYSTEM ARCHITECTURAL DOCUMENT

**Related Documents:**
- [SYSTEM ARCHITECTURAL DOCUMENT](../../../docs/SYSTEM%20ARCHITECHTURAL%20DOCUMENT.md) - Section 10.A
- [Master PRD](../../../docs/prd/PRD01-MVP.md)
- [PRD01-SUB05-SETTINGS-MANAGEMENT.md](../../../docs/prd/prd-01/PRD01-SUB05-SETTINGS-MANAGEMENT.md)
- [Package Implementation README](../../packages/nexus-settings/README.md) - Historical reference
