# **NEXUS ERP ARCHITECTURAL REFACTORING DRY RUN**

**Version:** 1.0  
**Created:** November 13, 2025  
**Status:** 🔄 Planning Phase  
**Purpose:** Step-by-step refactoring plan to align codebase with the NEXUS ERP System Architecture Document

---

## **Executive Summary**

This document outlines the comprehensive refactoring strategy to transform the current Laravel ERP codebase into a fully compliant **Nexus ERP** system following the **Maximum Atomicity** principle. The refactoring involves:

1. **Retiring all external `azaharizaman/*` packages** and consolidating them into the monorepo
2. **Renaming packages** to follow the standardized `nexus-*` convention
3. **Restructuring directories** to match the architectural mandate
4. **Moving code** from first-party packages into the monorepo structure
5. **Updating all imports and namespaces** to reflect new structure
6. **Enforcing architectural boundaries** between packages and core

---

## **Table of Contents**

1. [Current State Analysis](#1-current-state-analysis)
2. [Target Architecture](#2-target-architecture)
3. [Package Naming Migration Map](#3-package-naming-migration-map)
4. [Phase 1: Preparation and Backup](#phase-1-preparation-and-backup)
5. [Phase 2: External Package Internalization](#phase-2-external-package-internalization)
6. [Phase 3: Package Renaming and Restructuring](#phase-3-package-renaming-and-restructuring)
7. [Phase 4: Namespace Migration](#phase-4-namespace-migration)
8. [Phase 5: Core Orchestrator Restructuring](#phase-5-core-orchestrator-restructuring)
9. [Phase 6: Architectural Boundary Enforcement](#phase-6-architectural-boundary-enforcement)
10. [Phase 7: Testing and Validation](#phase-7-testing-and-validation)
11. [Phase 8: Documentation Updates](#phase-8-documentation-updates)
12. [Rollback Strategy](#rollback-strategy)
13. [Risk Assessment](#risk-assessment)

---

## **1. Current State Analysis**

### **1.1 Existing Package Structure**

```
nexus-erp/
├── apps/
│   └── headless-erp-app/           # Main ERP application (Orchestrator)
│       └── app/
│           ├── Actions/
│           ├── Contracts/
│           ├── Http/
│           ├── Models/
│           ├── Providers/
│           └── Support/
│
├── packages/
│   ├── audit-logging/              # nexus-audit-log (planned)
│   ├── core/                       # azaharizaman/erp-core
│   ├── serial-numbering/           # nexus-sequencing-management (rename)
│   └── settings-management/        # nexus-settings-management (rename)
│
└── External Dependencies (to internalize):
    ├── azaharizaman/laravel-uom-management
    ├── azaharizaman/laravel-inventory-management
    ├── azaharizaman/laravel-backoffice
    └── azaharizaman/laravel-serial-numbering (duplicate?)
```

### **1.2 Current Namespace Convention**

| Package | Current Namespace | Current Composer Name |
|---------|-------------------|----------------------|
| Core | `Nexus\Erp\Core` | `azaharizaman/erp-core` |
| Audit Logging | `App\AuditLogging` | Not packaged |
| Serial Numbering | Mixed | `packages/serial-numbering` |
| Settings | Mixed | `packages/settings-management` |

### **1.3 Identified Issues**

1. **Inconsistent Naming:** Mix of `azaharizaman/`, `erp-`, and `nexus-` prefixes
2. **External Dependencies:** Four packages hosted externally need internalization
3. **Namespace Inconsistency:** Some packages use `Nexus\Erp\*`, others use `App\*`
4. **Architectural Boundaries:** Code in main app should be in packages
5. **Duplicate Serial Numbering:** Both external package and internal package exist
6. **Missing Package Structure:** Some features not properly packaged

---

## **2. Target Architecture**

### **2.1 Final Package Structure**

```
nexus-erp/
├── apps/
│   └── headless-erp-app/           # Nexus ERP Core (Orchestrator ONLY)
│       └── app/
│           ├── Http/               # Public API routes & controllers only
│           │   ├── Controllers/
│           │   └── Middleware/
│           ├── Providers/          # Service bindings & orchestration
│           │   ├── AppServiceProvider.php
│           │   ├── RouteServiceProvider.php
│           │   └── EventServiceProvider.php
│           └── Console/            # Artisan commands (orchestration)
│               └── Kernel.php
│
└── packages/                       # ALL business logic here
    ├── nexus-contracts/            # NEW: All inter-package interfaces
    ├── nexus-tenancy-management/   # FROM: erp-core (multi-tenancy only)
    ├── nexus-identity-management/  # FROM: erp-core (auth/RBAC only)
    ├── nexus-audit-log/            # FROM: audit-logging package
    ├── nexus-sequencing-management/# RENAME: serial-numbering
    ├── nexus-settings-management/  # RENAME: settings-management
    ├── nexus-uom-management/       # MIGRATE: laravel-uom-management
    ├── nexus-inventory-management/ # MIGRATE: laravel-inventory-management
    ├── nexus-organization-master/  # MIGRATE: laravel-backoffice
    └── nexus-feature-toggling-management/ # NEW: Future implementation
```

### **2.2 Target Namespace Convention**

| Package | Target Namespace | Target Composer Name |
|---------|------------------|----------------------|
| Contracts | `Nexus\Contracts` | `nexus/contracts` |
| Tenancy | `Nexus\TenancyManagement` | `nexus/tenancy-management` |
| Identity | `Nexus\IdentityManagement` | `nexus/identity-management` |
| Audit Log | `Nexus\AuditLog` | `nexus/audit-log` |
| Sequencing | `Nexus\SequencingManagement` | `nexus/sequencing-management` |
| Settings | `Nexus\SettingsManagement` | `nexus/settings-management` |
| UOM | `Nexus\UomManagement` | `nexus/uom-management` |
| Inventory | `Nexus\InventoryManagement` | `nexus/inventory-management` |
| Organization | `Nexus\OrganizationMaster` | `nexus/organization-master` |

**Note:** Using `nexus/` vendor prefix instead of `azaharizaman/` to align with the Nexus ERP brand and architecture document.

---

## **3. Package Naming Migration Map**

### **3.1 Package Consolidation and Renaming**

| Current Package | Action | Target Package | Rationale |
|----------------|--------|----------------|-----------|
| `azaharizaman/erp-core` | **SPLIT** | `nexus-tenancy-management` + `nexus-identity-management` | Core contains multiple domains (tenancy + auth). Must be atomic. |
| `packages/audit-logging` | **RENAME** | `nexus-audit-log` | Align with architecture naming (no "ing" suffix) |
| `packages/serial-numbering` | **RENAME** | `nexus-sequencing-management` | Architecture doc specifies "sequencing-management" |
| `packages/settings-management` | **RENAME** | `nexus-settings-management` | Already compliant, just prefix change |
| `azaharizaman/laravel-uom-management` | **MIGRATE & RENAME** | `nexus-uom-management` | Internalize external package |
| `azaharizaman/laravel-inventory-management` | **MIGRATE & RENAME** | `nexus-inventory-management` | Internalize external package |
| `azaharizaman/laravel-backoffice` | **MIGRATE & RENAME** | `nexus-organization-master` | Rename to match master data role |
| `azaharizaman/laravel-serial-numbering` | **CONSOLIDATE** | `nexus-sequencing-management` | Merge with existing serial-numbering |

### **3.2 New Packages to Create**

| Package Name | Purpose | Priority |
|--------------|---------|----------|
| `nexus-contracts` | Decoupling layer for all inter-package communication | **HIGH** - Required before refactoring |
| `nexus-feature-toggling-management` | Feature flag management | **MEDIUM** - Architecture doc specifies |
| `nexus-party-management` | Customer/Vendor/Contact management | **LOW** - Future implementation |
| `nexus-item-master` | Product/Service master data | **LOW** - Future implementation |

---

## **Phase 1: Preparation and Backup**

### **Step 1.1: Create Backup Branch**

```bash
# Create backup of current state
git checkout -b backup/pre-architectural-refactor
git push origin backup/pre-architectural-refactor

# Create working branch for refactoring
git checkout main
git checkout -b refactor/architectural-alignment
```

### **Step 1.2: Document Current Dependencies**

```bash
# In project root
cd /home/conrad/Dev/azaharizaman/nexus-erp

# Export current dependency tree
composer show --tree > docs/refactor/pre-refactor-dependencies.txt

# Export current namespace usage
find apps/headless-erp-app/app -name "*.php" | xargs grep -h "^namespace " | sort -u > docs/refactor/current-namespaces-app.txt
find packages -name "*.php" | xargs grep -h "^namespace " | sort -u > docs/refactor/current-namespaces-packages.txt
```

### **Step 1.3: Run Full Test Suite Baseline**

```bash
cd apps/headless-erp-app
composer test > ../../docs/refactor/pre-refactor-test-results.txt
```

**Checkpoint:** All tests must pass before proceeding.

### **Step 1.4: Create Refactoring Tracking Directory**

```bash
mkdir -p docs/refactor
touch docs/refactor/migration-log.md
touch docs/refactor/namespace-mapping.json
touch docs/refactor/file-moves.log
```

---

## **Phase 2: External Package Internalization**

This phase brings all external `azaharizaman/*` packages into the monorepo.

### **Step 2.1: Clone External Packages**

```bash
cd /tmp/nexus-migration

# Clone external packages
git clone https://github.com/azaharizaman/laravel-uom-management.git
git clone https://github.com/azaharizaman/laravel-inventory-management.git
git clone https://github.com/azaharizaman/laravel-backoffice.git
git clone https://github.com/azaharizaman/laravel-serial-numbering.git
```

### **Step 2.2: Analyze External Package Structure**

For each external package, document:
- Current namespace
- Dependencies
- Service providers
- Configuration files
- Migration files
- Routes
- Tests

```bash
# Example for UOM package
cd /tmp/nexus-migration/laravel-uom-management
find . -name "*.php" | xargs grep "^namespace " | sort -u > namespace-list.txt
cat composer.json | jq '.autoload.psr-4' > autoload-structure.json
```

### **Step 2.3: Migrate UOM Management Package**

```bash
cd /home/conrad/Dev/azaharizaman/nexus-erp

# Create new package directory
mkdir -p packages/nexus-uom-management/src
mkdir -p packages/nexus-uom-management/database/migrations
mkdir -p packages/nexus-uom-management/tests
mkdir -p packages/nexus-uom-management/config

# Copy source files
cp -r /tmp/nexus-migration/laravel-uom-management/src/* packages/nexus-uom-management/src/
cp -r /tmp/nexus-migration/laravel-uom-management/database/migrations/* packages/nexus-uom-management/database/migrations/
cp -r /tmp/nexus-migration/laravel-uom-management/tests/* packages/nexus-uom-management/tests/
cp -r /tmp/nexus-migration/laravel-uom-management/config/* packages/nexus-uom-management/config/
```

**Create composer.json:**

```json
{
    "name": "nexus/uom-management",
    "description": "Unit of Measure management for Nexus ERP",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\UomManagement\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Nexus\\UomManagement\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Nexus\\UomManagement\\UomManagementServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

**Update Namespace in All Files:**

```bash
cd packages/nexus-uom-management

# Update namespace declarations
find src -name "*.php" -exec sed -i 's/namespace Azaharizaman\\LaravelUomManagement/namespace Nexus\\UomManagement/g' {} \;

# Update use statements
find src -name "*.php" -exec sed -i 's/use Azaharizaman\\LaravelUomManagement/use Nexus\\UomManagement/g' {} \;

# Update test namespaces
find tests -name "*.php" -exec sed -i 's/namespace Azaharizaman\\LaravelUomManagement/namespace Nexus\\UomManagement/g' {} \;
find tests -name "*.php" -exec sed -i 's/use Azaharizaman\\LaravelUomManagement/use Nexus\\UomManagement/g' {} \;
```

**Update Service Provider:**

Rename and update: `src/UomManagementServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace Nexus\UomManagement;

use Illuminate\Support\ServiceProvider;

class UomManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/uom-management.php', 'uom-management'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        $this->publishes([
            __DIR__.'/../config/uom-management.php' => config_path('uom-management.php'),
        ], 'uom-config');
    }
}
```

### **Step 2.4: Migrate Inventory Management Package**

```bash
# Create package structure
mkdir -p packages/nexus-inventory-management/{src,database/migrations,tests,config}

# Copy files
cp -r /tmp/nexus-migration/laravel-inventory-management/src/* packages/nexus-inventory-management/src/
cp -r /tmp/nexus-migration/laravel-inventory-management/database/migrations/* packages/nexus-inventory-management/database/migrations/
cp -r /tmp/nexus-migration/laravel-inventory-management/tests/* packages/nexus-inventory-management/tests/
cp -r /tmp/nexus-migration/laravel-inventory-management/config/* packages/nexus-inventory-management/config/

# Update namespaces
cd packages/nexus-inventory-management
find . -name "*.php" -exec sed -i 's/namespace Azaharizaman\\LaravelInventoryManagement/namespace Nexus\\InventoryManagement/g' {} \;
find . -name "*.php" -exec sed -i 's/use Azaharizaman\\LaravelInventoryManagement/use Nexus\\InventoryManagement/g' {} \;
```

**Create composer.json:**

```json
{
    "name": "nexus/inventory-management",
    "description": "Inventory management for Nexus ERP",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "nexus/uom-management": "dev-main"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\InventoryManagement\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Nexus\\InventoryManagement\\InventoryManagementServiceProvider"
            ]
        }
    }
}
```

### **Step 2.5: Migrate Backoffice Package to Organization Master**

```bash
# Create package structure
mkdir -p packages/nexus-organization-master/{src,database/migrations,tests,config}

# Copy files
cp -r /tmp/nexus-migration/laravel-backoffice/src/* packages/nexus-organization-master/src/
cp -r /tmp/nexus-migration/laravel-backoffice/database/migrations/* packages/nexus-organization-master/database/migrations/
cp -r /tmp/nexus-migration/laravel-backoffice/tests/* packages/nexus-organization-master/tests/
cp -r /tmp/nexus-migration/laravel-backoffice/config/* packages/nexus-organization-master/config/

# Update namespaces (more significant rename)
cd packages/nexus-organization-master
find . -name "*.php" -exec sed -i 's/namespace Azaharizaman\\LaravelBackoffice/namespace Nexus\\OrganizationMaster/g' {} \;
find . -name "*.php" -exec sed -i 's/use Azaharizaman\\LaravelBackoffice/use Nexus\\OrganizationMaster/g' {} \;
```

**Create composer.json:**

```json
{
    "name": "nexus/organization-master",
    "description": "Organizational structure master data for Nexus ERP",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\OrganizationMaster\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Nexus\\OrganizationMaster\\OrganizationMasterServiceProvider"
            ]
        }
    }
}
```

### **Step 2.6: Consolidate Serial Numbering Packages**

This requires merging the external `laravel-serial-numbering` with the internal `packages/serial-numbering`:

```bash
# Create temporary merge directory
mkdir -p /tmp/nexus-serial-merge

# Copy both sources
cp -r /tmp/nexus-migration/laravel-serial-numbering/* /tmp/nexus-serial-merge/external/
cp -r packages/serial-numbering/* /tmp/nexus-serial-merge/internal/

# Manual merge required - analyze differences
diff -r /tmp/nexus-serial-merge/external/src /tmp/nexus-serial-merge/internal/src > /tmp/serial-diff.txt

# After manual resolution, create final package
mkdir -p packages/nexus-sequencing-management/{src,database/migrations,tests,config}
# Copy merged files (manual process based on diff analysis)
```

**Note:** This step requires careful manual review to merge functionality from both packages.

---

## **Phase 3: Package Renaming and Restructuring**

### **Step 3.1: Rename Audit Logging Package**

```bash
cd packages

# Rename directory
mv audit-logging nexus-audit-log

cd nexus-audit-log

# Update composer.json name
sed -i 's/"name": ".*"/"name": "nexus\/audit-log"/g' composer.json

# Update namespaces
find . -name "*.php" -exec sed -i 's/namespace App\\AuditLogging/namespace Nexus\\AuditLog/g' {} \;
find . -name "*.php" -exec sed -i 's/use App\\AuditLogging/use Nexus\\AuditLog/g' {} \;
```

### **Step 3.2: Rename Settings Management Package**

```bash
cd packages

# Rename directory
mv settings-management nexus-settings-management

cd nexus-settings-management

# Update composer.json
sed -i 's/"name": ".*"/"name": "nexus\/settings-management"/g' composer.json

# Update namespaces if needed
find . -name "*.php" -exec sed -i 's/namespace App\\SettingsManagement/namespace Nexus\\SettingsManagement/g' {} \;
```

### **Step 3.3: Finalize Serial Numbering as Sequencing Management**

After consolidation in Step 2.6:

```bash
cd packages/nexus-sequencing-management

# Ensure all namespaces are correct
find . -name "*.php" -exec sed -i 's/namespace .*SerialNumbering/namespace Nexus\\SequencingManagement/g' {} \;
find . -name "*.php" -exec sed -i 's/use .*SerialNumbering/use Nexus\\SequencingManagement/g' {} \;
```

### **Step 3.4: Split Core Package into Tenancy and Identity**

This is the most complex operation.

**Analyze Core Package Structure:**

```bash
cd packages/core/src

# List all files by domain
find . -name "*.php" | xargs grep -l "Tenant\|Multi.*Tenant\|Scoped" > /tmp/tenancy-files.txt
find . -name "*.php" | xargs grep -l "Auth\|User\|Role\|Permission" > /tmp/identity-files.txt
```

**Create New Package Structures:**

```bash
cd packages

mkdir -p nexus-tenancy-management/{src,database/migrations,tests,config}
mkdir -p nexus-identity-management/{src,database/migrations,tests,config}
```

**Move Tenancy-Related Files:**

```bash
# Move Tenant model and related
cp core/src/Models/Tenant.php nexus-tenancy-management/src/Models/
cp core/src/Traits/BelongsToTenant.php nexus-tenancy-management/src/Traits/
cp core/src/Scopes/TenantScope.php nexus-tenancy-management/src/Scopes/
cp core/src/Middleware/IdentifyTenant.php nexus-tenancy-management/src/Middleware/

# Move tenancy services
cp core/src/Services/TenantManager.php nexus-tenancy-management/src/Services/
cp core/src/Repositories/TenantRepository.php nexus-tenancy-management/src/Repositories/
cp core/src/Contracts/TenantRepositoryContract.php nexus-tenancy-management/src/Contracts/

# Move migrations
cp core/database/migrations/*create_tenants_table.php nexus-tenancy-management/database/migrations/
```

**Update Tenancy Namespaces:**

```bash
cd nexus-tenancy-management
find . -name "*.php" -exec sed -i 's/namespace Nexus\\Erp\\Core/namespace Nexus\\TenancyManagement/g' {} \;
find . -name "*.php" -exec sed -i 's/use Nexus\\Erp\\Core/use Nexus\\TenancyManagement/g' {} \;
```

**Move Identity-Related Files:**

```bash
# Move User model and auth
cp core/src/Models/User.php nexus-identity-management/src/Models/
cp core/src/Policies/*.php nexus-identity-management/src/Policies/
cp core/src/Services/*Auth*.php nexus-identity-management/src/Services/

# Move RBAC related (if using Spatie Permission wrapper)
cp -r core/src/Traits/HasPermissions.php nexus-identity-management/src/Traits/ 2>/dev/null || true

# Move migrations
cp core/database/migrations/*users*.php nexus-identity-management/database/migrations/
cp core/database/migrations/*permissions*.php nexus-identity-management/database/migrations/
cp core/database/migrations/*roles*.php nexus-identity-management/database/migrations/
```

**Update Identity Namespaces:**

```bash
cd nexus-identity-management
find . -name "*.php" -exec sed -i 's/namespace Nexus\\Erp\\Core/namespace Nexus\\IdentityManagement/g' {} \;
find . -name "*.php" -exec sed -i 's/use Nexus\\Erp\\Core/use Nexus\\IdentityManagement/g' {} \;
```

**Create composer.json for both:**

`packages/nexus-tenancy-management/composer.json`:

```json
{
    "name": "nexus/tenancy-management",
    "description": "Multi-tenancy management for Nexus ERP",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\TenancyManagement\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Nexus\\TenancyManagement\\TenancyManagementServiceProvider"
            ]
        }
    }
}
```

`packages/nexus-identity-management/composer.json`:

```json
{
    "name": "nexus/identity-management",
    "description": "Identity, authentication, and authorization management for Nexus ERP",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.2",
        "spatie/laravel-permission": "^6.0",
        "nexus/tenancy-management": "dev-main"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\IdentityManagement\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Nexus\\IdentityManagement\\IdentityManagementServiceProvider"
            ]
        }
    }
}
```

**Archive Old Core Package:**

```bash
# Move to archive after successful split
mv packages/core packages/_archived_core
```

---

## **Phase 4: Namespace Migration**

### **Step 4.1: Create Contracts Package**

This is the foundation for package decoupling.

```bash
mkdir -p packages/nexus-contracts/src
cd packages/nexus-contracts
```

**Create composer.json:**

```json
{
    "name": "nexus/contracts",
    "description": "Decoupling layer - All inter-package contracts for Nexus ERP",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "Nexus\\Contracts\\": "src/"
        }
    }
}
```

**Identify and Move Contracts:**

```bash
# Find all interfaces across packages
find ../nexus-* -name "*Contract.php" -o -name "*Interface.php" > /tmp/contracts-list.txt

# Create directory structure
mkdir -p src/{Tenancy,Identity,Audit,Sequencing,Settings,Uom,Inventory,Organization}

# Copy contracts (preserve subdirectory structure)
# Example for Tenancy
cp ../nexus-tenancy-management/src/Contracts/* src/Tenancy/ 2>/dev/null || true
```

**Update Contract Namespaces:**

```bash
cd packages/nexus-contracts
find src -name "*.php" -exec sed -i 's/namespace Nexus\\[^\\]*\\Contracts/namespace Nexus\\Contracts/g' {} \;
```

**Update Package References to Contracts:**

```bash
# For each package, update contract imports
for pkg in nexus-tenancy-management nexus-identity-management nexus-audit-log \
           nexus-sequencing-management nexus-settings-management nexus-uom-management \
           nexus-inventory-management nexus-organization-master; do
    cd packages/$pkg
    find . -name "*.php" -exec sed -i 's/use Nexus\\[^\\]*\\Contracts\\/use Nexus\\Contracts\\/g' {} \;
done
```

### **Step 4.2: Update Main Application Dependencies**

Update `apps/headless-erp-app/composer.json`:

```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.2",
        "laravel/scout": "^10.0",
        "laravel/tinker": "^2.10.1",
        "lorisleiva/laravel-actions": "^2.0",
        "predis/predis": "^3.2",
        "spatie/laravel-activitylog": "^4.0",
        "spatie/laravel-permission": "^6.0",
        
        "nexus/contracts": "dev-main",
        "nexus/tenancy-management": "dev-main",
        "nexus/identity-management": "dev-main",
        "nexus/audit-log": "dev-main",
        "nexus/sequencing-management": "dev-main",
        "nexus/settings-management": "dev-main",
        "nexus/uom-management": "dev-main",
        "nexus/inventory-management": "dev-main",
        "nexus/organization-master": "dev-main"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../packages/*"
        }
    ]
}
```

**Remove old dependencies:**

```bash
cd apps/headless-erp-app
composer remove azaharizaman/erp-core
composer remove azaharizaman/laravel-uom-management
composer remove azaharizaman/laravel-inventory-management
composer remove azaharizaman/laravel-backoffice
composer remove azaharizaman/laravel-serial-numbering

# Install new packages
composer install
```

### **Step 4.3: Update Application Imports**

```bash
cd apps/headless-erp-app

# Update all use statements in app code
find app -name "*.php" -exec sed -i 's/use Nexus\\Erp\\Core/use Nexus\\TenancyManagement/g' {} \;
find app -name "*.php" -exec sed -i 's/use Azaharizaman\\Laravel/use Nexus\\/g' {} \;

# Specifically update UOM references
find app -name "*.php" -exec sed -i 's/use Azaharizaman\\LaravelUomManagement/use Nexus\\UomManagement/g' {} \;

# Update Inventory references
find app -name "*.php" -exec sed -i 's/use Azaharizaman\\LaravelInventoryManagement/use Nexus\\InventoryManagement/g' {} \;

# Update Backoffice to Organization
find app -name "*.php" -exec sed -i 's/use Azaharizaman\\LaravelBackoffice/use Nexus\\OrganizationMaster/g' {} \;
```

### **Step 4.4: Update Configuration Files**

```bash
cd apps/headless-erp-app/config

# Update any configuration files referencing old packages
find . -name "*.php" -exec sed -i 's/Nexus\\\\Erp\\\\Core/Nexus\\\\TenancyManagement/g' {} \;
find . -name "*.php" -exec sed -i 's/Azaharizaman\\\\Laravel/Nexus\\\\/g' {} \;
```

---

## **Phase 5: Core Orchestrator Restructuring**

The main application should only contain orchestration logic, not business logic.

### **Step 5.1: Identify Business Logic in Main App**

```bash
cd apps/headless-erp-app/app

# Find Actions that should be in packages
find Actions -name "*.php" > /tmp/actions-audit.txt

# Find Models that should be in packages
find Models -name "*.php" > /tmp/models-audit.txt

# Find Services that should be in packages  
find Services -name "*.php" > /tmp/services-audit.txt
```

**Review each file and categorize:**

1. **Move to Package:** Single-domain business logic
2. **Keep in Core:** Cross-package orchestration logic
3. **Delete:** Duplicates of package functionality

### **Step 5.2: Move Single-Domain Actions to Packages**

Example: Move `CreateTenantAction` if it only handles tenant creation:

```bash
# If Action is purely tenant-focused
cp apps/headless-erp-app/app/Actions/CreateTenantAction.php \
   packages/nexus-tenancy-management/src/Actions/

# Update namespace
sed -i 's/namespace App\\Actions/namespace Nexus\\TenancyManagement\\Actions/' \
   packages/nexus-tenancy-management/src/Actions/CreateTenantAction.php

# Remove from app
rm apps/headless-erp-app/app/Actions/CreateTenantAction.php
```

### **Step 5.3: Keep Orchestration Actions in Core**

Example: `CreatePurchaseOrderAction` that coordinates UOM, Inventory, and Sequencing:

```bash
# This stays in apps/headless-erp-app/app/Actions/
# But update imports to use new packages
```

**Update the action:**

```php
<?php

namespace App\Actions;

use Nexus\Contracts\Inventory\InventoryRepositoryContract;
use Nexus\Contracts\Sequencing\SequenceGeneratorContract;
use Nexus\Contracts\Uom\UomConverterContract;
use Lorisleiva\Actions\Concerns\AsAction;

class CreatePurchaseOrderAction
{
    use AsAction;

    public function __construct(
        private readonly InventoryRepositoryContract $inventory,
        private readonly SequenceGeneratorContract $sequencer,
        private readonly UomConverterContract $uomConverter
    ) {}

    public function handle(array $data): PurchaseOrder
    {
        // Orchestration logic that uses multiple packages
        $poNumber = $this->sequencer->generate('PO');
        $items = $this->inventory->findByIds($data['item_ids']);
        // ... coordinate across packages
    }
}
```

### **Step 5.4: Move Models to Packages**

Models should live in their domain packages, not the core app.

```bash
# Example: User model
mv apps/headless-erp-app/app/Models/User.php \
   packages/nexus-identity-management/src/Models/

# Example: Tenant model  
mv apps/headless-erp-app/app/Models/Tenant.php \
   packages/nexus-tenancy-management/src/Models/
```

**Update namespaces:**

```bash
sed -i 's/namespace App\\Models/namespace Nexus\\IdentityManagement\\Models/' \
   packages/nexus-identity-management/src/Models/User.php

sed -i 's/namespace App\\Models/namespace Nexus\\TenancyManagement\\Models/' \
   packages/nexus-tenancy-management/src/Models/Tenant.php
```

**Update model references in app:**

```bash
cd apps/headless-erp-app
find . -name "*.php" -exec sed -i 's/use App\\Models\\User/use Nexus\\IdentityManagement\\Models\\User/g' {} \;
find . -name "*.php" -exec sed -i 's/use App\\Models\\Tenant/use Nexus\\TenancyManagement\\Models\\Tenant/g' {} \;
```

### **Step 5.5: Restructure App Directory**

**Target structure for apps/headless-erp-app/app:**

```
app/
├── Console/
│   └── Kernel.php              # Keep: Artisan command registration
│
├── Exceptions/
│   └── Handler.php             # Keep: Global exception handling
│
├── Http/
│   ├── Controllers/            # Keep: ONLY API presentation controllers
│   │   └── Api/
│   │       └── V1/
│   │           ├── TenantController.php
│   │           ├── UserController.php
│   │           └── ...
│   │
│   ├── Middleware/             # Keep: ONLY orchestration middleware
│   │   ├── Authenticate.php
│   │   └── TenantContextMiddleware.php
│   │
│   └── Requests/               # Keep: API request validation
│       └── Api/
│           └── V1/
│
├── Providers/                  # Keep: Service binding & orchestration
│   ├── AppServiceProvider.php
│   ├── RouteServiceProvider.php
│   └── EventServiceProvider.php
│
└── Actions/                    # Keep ONLY cross-package orchestration
    ├── CreatePurchaseOrderAction.php
    └── GenerateMonthlyReportAction.php
```

**Delete directories that should be empty:**

```bash
# After moving all domain logic to packages
rm -rf app/Repositories  # All repos should be in packages
rm -rf app/Services      # All single-domain services in packages
rm -rf app/Contracts     # Moved to nexus-contracts package
```

---

## **Phase 6: Architectural Boundary Enforcement**

### **Step 6.1: Implement Runtime Vetting Guardrail**

Create `apps/headless-erp-app/app/Providers/ArchitectureGuardServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class ArchitectureGuardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // No registration needed
    }

    /**
     * Bootstrap services - enforce architectural boundaries at runtime.
     */
    public function boot(): void
    {
        // Hook into container resolution
        $this->app->resolving(function ($object, $app) {
            $this->vetCaller($object);
        });
    }

    /**
     * Vet the caller to ensure no direct concrete class access between packages.
     */
    protected function vetCaller($object): void
    {
        // Only check for Nexus package classes
        $targetClass = get_class($object);
        
        if (!str_starts_with($targetClass, 'Nexus\\')) {
            return; // Not a Nexus package class, skip
        }

        // Get call stack
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        
        // Find first non-framework caller
        $callerClass = null;
        foreach ($trace as $frame) {
            if (!isset($frame['class'])) {
                continue;
            }
            
            // Skip framework classes
            if (str_starts_with($frame['class'], 'Illuminate\\') || 
                str_starts_with($frame['class'], 'Laravel\\')) {
                continue;
            }
            
            $callerClass = $frame['class'];
            break;
        }

        if ($callerClass === null) {
            return; // No identifiable caller
        }

        // Extract package namespaces
        $targetPackage = $this->extractPackage($targetClass);
        $callerPackage = $this->extractPackage($callerClass);

        // Check if this is a violation
        if ($this->isViolation($callerPackage, $targetPackage, $targetClass)) {
            throw new RuntimeException(sprintf(
                'Architectural Violation: Direct call to Concrete Service [%s] from Package [%s] is forbidden. ' .
                'You MUST only consume this service via its PHP Contract (Interface) bound in the Nexus ERP Core.',
                $targetClass,
                $callerClass
            ));
        }
    }

    /**
     * Extract package name from class namespace.
     */
    protected function extractPackage(string $class): ?string
    {
        if (!str_starts_with($class, 'Nexus\\')) {
            return null;
        }

        $parts = explode('\\', $class);
        return $parts[1] ?? null; // e.g., "TenancyManagement" from "Nexus\TenancyManagement\..."
    }

    /**
     * Determine if this is an architectural violation.
     */
    protected function isViolation(?string $callerPackage, ?string $targetPackage, string $targetClass): bool
    {
        // Allow if caller is from App (Core Orchestrator)
        if ($callerPackage === null) {
            return false;
        }

        // Allow if same package
        if ($callerPackage === $targetPackage) {
            return false;
        }

        // Allow if target is a Contract
        if (str_contains($targetClass, '\\Contracts\\')) {
            return false;
        }

        // Different packages calling concrete classes = VIOLATION
        return true;
    }
}
```

**Register in config/app.php:**

```php
'providers' => [
    // ... other providers
    App\Providers\ArchitectureGuardServiceProvider::class,
],
```

### **Step 6.2: Implement Static Analysis Rules**

Create `phpstan.neon` in project root:

```neon
parameters:
    level: 8
    paths:
        - apps/headless-erp-app/app
        - packages
    
    # Custom rules for architectural boundaries
    ignoreErrors:
        # Allow framework classes
        - '#Call to method .* on .*Illuminate\\.*#'
    
    # Enforce contract usage
    checkGenericClassInNonGenericObjectType: false
    
    # Custom architectural rules
    rules:
        - PHPStan\Rules\Nexus\NoDirectPackageReference
```

Create custom PHPStan rule (requires PHPStan extension development - optional for phase 1):

```php
// phpstan-rules/NoDirectPackageReference.php
namespace PHPStan\Rules\Nexus;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class NoDirectPackageReference implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Check if instantiating a concrete class from another Nexus package
        // Return error if violation detected
    }
}
```

### **Step 6.3: Create Package Boundary Tests**

Create `tests/Architecture/PackageBoundaryTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

class PackageBoundaryTest extends TestCase
{
    /**
     * Test that packages only depend on contracts, not concrete implementations.
     */
    public function test_packages_only_use_contracts(): void
    {
        $packages = [
            'nexus-tenancy-management',
            'nexus-identity-management',
            'nexus-audit-log',
            'nexus-sequencing-management',
            'nexus-settings-management',
            'nexus-uom-management',
            'nexus-inventory-management',
            'nexus-organization-master',
        ];

        foreach ($packages as $package) {
            $this->assertPackageUsesOnlyContracts($package);
        }
    }

    protected function assertPackageUsesOnlyContracts(string $package): void
    {
        $packagePath = base_path("packages/{$package}/src");
        
        if (!is_dir($packagePath)) {
            $this->markTestSkipped("Package {$package} not found");
        }

        $files = $this->getPhpFiles($packagePath);
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // Check for forbidden direct package references
            $this->assertDoesNotMatchRegex(
                '/use Nexus\\\\(?!Contracts)\\w+\\\\(?!.*Contract)/',
                $content,
                "File {$file} contains direct reference to another package's concrete class"
            );
        }
    }

    protected function getPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
```

---

## **Phase 7: Testing and Validation**

### **Step 7.1: Run Package-Level Tests**

```bash
# Test each package individually
for pkg in packages/nexus-*; do
    echo "Testing $pkg..."
    cd $pkg
    if [ -f "composer.json" ]; then
        composer install
        composer test || echo "⚠️  Tests failed for $pkg"
    fi
    cd ../..
done
```

### **Step 7.2: Run Application Tests**

```bash
cd apps/headless-erp-app

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate:fresh --seed

# Run test suite
composer test
```

### **Step 7.3: Test Package Boundaries**

```bash
# Run architectural tests
php artisan test --filter=PackageBoundaryTest

# Run architecture guard in development
# Should throw exceptions for violations
php artisan tinker
>>> app(\Nexus\TenancyManagement\Services\TenantManager::class)
// Should work (Core orchestrator calling package)

>>> // From within a package test
>>> new \Nexus\InventoryManagement\Services\InventoryService()
// Should throw RuntimeException if called from another package
```

### **Step 7.4: Validate API Endpoints**

```bash
# Test all API routes still work
php artisan route:list --json > /tmp/routes-after-refactor.json

# Compare with pre-refactor routes
diff /tmp/routes-before-refactor.json /tmp/routes-after-refactor.json

# Manual API testing
curl -X GET http://localhost:8000/api/v1/tenants \
     -H "Authorization: Bearer {token}"

# Expected: Same responses as before refactor
```

### **Step 7.5: Performance Benchmarking**

```bash
# Run performance tests to ensure no regression
php artisan benchmark:api --runs=100

# Compare with baseline
diff docs/refactor/pre-refactor-benchmark.txt \
     docs/refactor/post-refactor-benchmark.txt
```

---

## **Phase 8: Documentation Updates**

### **Step 8.1: Update README Files**

Update main `README.md`:

```markdown
# Nexus ERP

A modular, atomic ERP system built on Laravel 12.

## Architecture

Nexus ERP follows the **Maximum Atomicity** principle:
- All business logic resides in atomic packages
- Main application is purely an orchestrator
- Packages communicate via contracts, never directly

## Package Structure

```
packages/
├── nexus-contracts/             # Inter-package interfaces
├── nexus-tenancy-management/    # Multi-tenancy
├── nexus-identity-management/   # Auth & RBAC
├── nexus-audit-log/             # Audit logging
├── nexus-sequencing-management/ # Document numbering
├── nexus-settings-management/   # Application settings
├── nexus-uom-management/        # Unit of measure
├── nexus-inventory-management/  # Inventory control
└── nexus-organization-master/   # Organizational structure
```

## Installation

...
```

### **Step 8.2: Update Package Documentation**

For each package, create/update `README.md`:

```markdown
# Nexus Tenancy Management

Multi-tenant data isolation for Nexus ERP.

## Installation

```bash
composer require nexus/tenancy-management
```

## Usage

```php
use Nexus\TenancyManagement\Models\Tenant;
use Nexus\Contracts\Tenancy\TenantManagerContract;

$tenantManager = app(TenantManagerContract::class);
$tenant = $tenantManager->setActive($tenantId);
```

## Architecture

This package provides:
- `Tenant` model with UUID primary keys
- Global scope for tenant filtering
- `BelongsToTenant` trait for models
- Tenant context management

...
```

### **Step 8.3: Update API Documentation**

```bash
# Generate OpenAPI documentation
php artisan l5-swagger:generate

# Update API version in documentation
# All endpoints should still work with new package structure
```

### **Step 8.4: Update Architectural Documents**

Update references in:
- `docs/ARCHITECTURAL_DIGEST.md` - Mark as IMPLEMENTED
- `.github/copilot-instructions.md` - Update package names
- `docs/prd/*.md` - Update package references
- Create `docs/PACKAGE_MIGRATION_GUIDE.md` for developers

### **Step 8.5: Create Migration Guide**

Create `docs/PACKAGE_MIGRATION_GUIDE.md`:

```markdown
# Package Migration Guide

## For Developers

### Old vs New Package Names

| Old Name | New Name | Namespace Change |
|----------|----------|------------------|
| `azaharizaman/erp-core` | `nexus/tenancy-management` + `nexus/identity-management` | `Nexus\Erp\Core` → `Nexus\TenancyManagement` + `Nexus\IdentityManagement` |
| `azaharizaman/laravel-uom-management` | `nexus/uom-management` | `Azaharizaman\LaravelUomManagement` → `Nexus\UomManagement` |
| ... | ... | ... |

### Import Updates

```php
// Old
use Nexus\Erp\Core\Models\Tenant;
use Azaharizaman\LaravelUomManagement\Services\UomConverter;

// New
use Nexus\TenancyManagement\Models\Tenant;
use Nexus\Contracts\Uom\UomConverterContract;
```

### Service Container Bindings

All concrete implementations are now bound in the Core Orchestrator:

```php
// apps/headless-erp-app/app/Providers/AppServiceProvider.php

$this->app->bind(
    \Nexus\Contracts\Uom\UomConverterContract::class,
    \Nexus\UomManagement\Services\UomConverter::class
);
```

...
```

---

## **Rollback Strategy**

### **Emergency Rollback Procedure**

If critical issues are discovered:

```bash
# 1. Checkout backup branch
git checkout backup/pre-architectural-refactor

# 2. Force push to main (USE WITH EXTREME CAUTION)
git push origin backup/pre-architectural-refactor:main --force

# 3. Notify team
echo "ROLLBACK EXECUTED - All developers must pull latest main"
```

### **Partial Rollback Options**

If only specific packages are problematic:

```bash
# Revert specific package changes
git checkout backup/pre-architectural-refactor -- packages/nexus-inventory-management

# Keep other refactored packages
git add packages/nexus-inventory-management
git commit -m "Partial rollback: Revert inventory package"
```

### **Rollback Testing**

Before executing rollback:

```bash
# Test backup branch
git checkout backup/pre-architectural-refactor
composer install
php artisan migrate:fresh --seed
php artisan test
```

---

## **Risk Assessment**

### **High-Risk Areas**

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| **Breaking API Contracts** | CRITICAL | MEDIUM | Comprehensive API testing after each phase |
| **Namespace Conflicts** | HIGH | MEDIUM | Automated namespace scanning before merge |
| **Database Migration Issues** | CRITICAL | LOW | Backup database before migrations |
| **Service Provider Conflicts** | HIGH | MEDIUM | Test service provider boot order |
| **Performance Degradation** | MEDIUM | LOW | Benchmark before/after each phase |
| **Package Circular Dependencies** | HIGH | LOW | Dependency graph analysis before restructuring |

### **Critical Success Factors**

1. ✅ **All tests pass** after each phase
2. ✅ **No API contract breakage** - existing clients continue to work
3. ✅ **Package boundaries enforced** - runtime guard catches violations
4. ✅ **Documentation complete** - developers can navigate new structure
5. ✅ **Performance maintained** - no significant regression in benchmarks

### **Validation Checkpoints**

After each phase, verify:

```bash
# Checklist
□ All unit tests pass
□ All feature tests pass
□ All architectural boundary tests pass
□ API endpoints return expected responses
□ Database migrations run without errors
□ No broken imports or namespaces
□ Service providers boot successfully
□ Queue jobs process correctly
□ Package interdependencies resolve
□ Documentation updated
```

---

## **Execution Timeline**

### **Recommended Schedule**

| Phase | Duration | Dependencies | Can Start After |
|-------|----------|--------------|-----------------|
| Phase 1: Preparation | 1 day | None | Immediately |
| Phase 2: External Package Internalization | 3-5 days | Phase 1 | Phase 1 complete |
| Phase 3: Package Renaming | 2 days | Phase 2 | Phase 2 complete |
| Phase 4: Namespace Migration | 3 days | Phase 3 | Phase 3 complete |
| Phase 5: Core Restructuring | 4-5 days | Phase 4 | Phase 4 complete |
| Phase 6: Boundary Enforcement | 2 days | Phase 5 | Phase 5 complete |
| Phase 7: Testing | 3-4 days | Phase 6 | Phase 6 complete |
| Phase 8: Documentation | 2 days | Phase 7 | Can overlap with Phase 7 |

**Total Estimated Time:** 20-25 working days (4-5 weeks)

### **Parallel Execution Opportunities**

Some tasks can be parallelized:
- **Documentation (Phase 8)** can start during Phase 7 testing
- **Multiple package migrations (Phase 2)** can be done in parallel by different developers
- **Namespace updates (Phase 4)** can be scripted and run in parallel

---

## **Post-Refactoring Actions**

### **Immediate Actions (Week 1 after completion)**

1. **Retire Old Packages**
   ```bash
   # Archive old GitHub repositories
   gh repo archive azaharizaman/laravel-uom-management
   gh repo archive azaharizaman/laravel-inventory-management
   gh repo archive azaharizaman/laravel-backoffice
   gh repo archive azaharizaman/laravel-serial-numbering
   ```

2. **Update Packagist**
   - Mark old packages as abandoned on Packagist
   - Publish new `nexus/*` packages to Packagist (if public distribution desired)

3. **Team Training**
   - Conduct architecture overview session
   - Share package migration guide
   - Update onboarding documentation

### **Ongoing Actions**

1. **Monitoring**
   - Watch for architectural violations in logs
   - Monitor performance metrics
   - Track package interdependency changes

2. **Continuous Improvement**
   - Identify additional packages that should be created
   - Refine contract interfaces based on usage patterns
   - Optimize package boundaries based on change patterns

3. **Documentation Maintenance**
   - Keep package READMEs updated
   - Update architecture diagrams as packages evolve
   - Maintain migration guides for new features

---

## **Appendix A: Command Reference**

### **Quick Commands**

```bash
# Run all package tests
for pkg in packages/nexus-*; do (cd $pkg && composer test); done

# Update all package dependencies
for pkg in packages/nexus-*; do (cd $pkg && composer update); done

# Find all namespace usages
grep -r "namespace Nexus" packages/

# Find all contract usages
grep -r "use Nexus\\\\Contracts" packages/

# Validate composer.json files
for pkg in packages/nexus-*/composer.json; do composer validate $pkg; done

# Generate dependency graph
composer show --tree > dependency-tree.txt
```

### **Automated Refactoring Scripts**

Create `scripts/refactor-helper.sh`:

```bash
#!/bin/bash

# Helper script for architectural refactoring

case "$1" in
    "rename-namespace")
        # Usage: ./scripts/refactor-helper.sh rename-namespace OldNamespace NewNamespace
        find packages -name "*.php" -exec sed -i "s/$2/$3/g" {} \;
        ;;
    
    "validate-boundaries")
        # Check for architectural violations
        php artisan test --filter=PackageBoundaryTest
        ;;
    
    "update-imports")
        # Update all imports in main app
        find apps/headless-erp-app -name "*.php" -exec sed -i 's/Nexus\\Erp\\Core/Nexus\\TenancyManagement/g' {} \;
        ;;
    
    *)
        echo "Usage: $0 {rename-namespace|validate-boundaries|update-imports}"
        exit 1
        ;;
esac
```

---

## **Appendix B: Package Dependency Graph**

### **Target Dependencies**

```
nexus-contracts (no dependencies)
  ↑
  ├── nexus-tenancy-management
  ├── nexus-identity-management → depends on: tenancy-management
  ├── nexus-audit-log → depends on: tenancy-management
  ├── nexus-sequencing-management → depends on: tenancy-management
  ├── nexus-settings-management → depends on: tenancy-management
  ├── nexus-uom-management → depends on: tenancy-management
  ├── nexus-inventory-management → depends on: tenancy-management, uom-management
  └── nexus-organization-master → depends on: tenancy-management

headless-erp-app (orchestrator)
  → depends on: ALL packages above
```

### **Dependency Rules**

1. **nexus-contracts** has ZERO dependencies (pure interfaces)
2. All packages depend on **nexus-contracts** for inter-package communication
3. Most packages depend on **nexus-tenancy-management** for multi-tenant support
4. **nexus-identity-management** depends on tenancy for tenant-scoped users
5. **nexus-inventory-management** depends on **nexus-uom-management** for unit conversions
6. Packages NEVER depend on each other's concrete classes, only contracts

---

## **Appendix C: File Move Tracking Template**

Create `docs/refactor/file-move-log.csv`:

```csv
Old Path,New Path,Type,Status,Notes
"apps/headless-erp-app/app/Models/Tenant.php","packages/nexus-tenancy-management/src/Models/Tenant.php","Model","✅ Complete","Namespace updated"
"packages/core/src/Services/TenantManager.php","packages/nexus-tenancy-management/src/Services/TenantManager.php","Service","✅ Complete","Moved from split core"
"apps/headless-erp-app/app/Models/User.php","packages/nexus-identity-management/src/Models/User.php","Model","✅ Complete","Updated auth references"
...
```

---

## **Conclusion**

This dry run document provides a comprehensive, step-by-step plan for refactoring the codebase to align with the Nexus ERP architectural principles. The refactoring is complex but necessary to achieve the vision of **Maximum Atomicity** and create a truly modular, scalable ERP system.

**Key Success Factors:**
1. **Follow phases sequentially** - don't skip ahead
2. **Test after every phase** - catch issues early
3. **Maintain backup branch** - enable easy rollback
4. **Document all changes** - help future developers
5. **Communicate with team** - ensure everyone understands the new structure

**Next Steps:**
1. Review this document with the team
2. Get approval from stakeholders
3. Schedule dedicated time for refactoring (4-5 weeks)
4. Begin with Phase 1: Preparation

---

**Document Status:** 📋 Ready for Review  
**Requires Approval From:** Technical Lead, Architecture Review Board  
**Estimated Effort:** 20-25 working days  
**Risk Level:** 🔴 HIGH (but mitigated with comprehensive testing and rollback strategy)

---

**Version:** 1.0  
**Last Updated:** November 13, 2025  
**Maintained By:** Nexus ERP Development Team
