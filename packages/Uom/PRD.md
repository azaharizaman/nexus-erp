Here’s your content reorganized into a **formal, structured Product Requirements Document (PRD)** — following professional software product documentation standards suitable for open-source or enterprise Laravel package development.

---

# **Product Requirements Document (PRD)**

## Laravel UOM Management Package

---

### 🧭 **1. Overview**

**Product Name:** Laravel UOM Management Package
**Purpose:**
To provide a robust, extensible, and testable system for managing Units of Measurement (UOM) in Laravel applications, including conversions, compound units, packaging hierarchies, and user-defined customizations.

**Target Users:**

* Developers integrating complex measurement systems
* ERP, inventory, manufacturing, or logistics software teams
* SaaS platforms requiring flexible and auditable unit conversions

**Core Technologies:**

* Laravel Framework
* Spatie Laravel Package Tools
* Orchestra Testbench for testing

---

### 🧱 **2. Folder & Project Structure**

**Framework:** Laravel package scaffolded via `spatie/laravel-package-tools`
**Testing:** `orchestra/testbench`

```
database/
├── migrations/
├── factories/
├── seeders/
src/
├── Casts/
├── Commands/
├── Models/
├── Services/
├── Enums/
├── Exceptions/
├── Facades/
├── Helpers/
├── Traits/
├── UomManagementServiceProvider.php
config/
└── uom.php
tests/
├── features/
└── unit/
docs/
└── UOM_Usage_Guide.md
```

---

### 📘 **3. Core Data Model**

#### **3.1 Entities**

| #  | Table                     | Purpose                                             |
| -- | ------------------------- | --------------------------------------------------- |
| 1  | `uom_types`               | Defines measurement categories (mass, length, etc.) |
| 2  | `uom_units`               | Stores individual units with conversion factors     |
| 3  | `uom_conversions`         | Defines conversion rules between units              |
| 4  | `uom_aliases`             | Stores alternate names or symbols for units         |
| 5  | `uom_compound_units`      | Defines compound units like `kg/m²`                 |
| 6  | `uom_compound_components` | Maps base units to compound units                   |
| 7  | `uom_unit_groups`         | Groups units into systems (metric, imperial)        |
| 8  | `uom_unit_group_unit`     | Pivot linking units to groups                       |
| 9  | `uom_packagings`          | Defines packaging relationships                     |
| 10 | `uom_items`               | Represents items with a default unit                |
| 11 | `uom_item_packagings`     | Links items to packaging definitions                |
| 12 | `uom_conversion_logs`     | Logs conversion operations for audit                |
| 13 | `uom_custom_units`        | Stores user-defined units                           |
| 14 | `uom_custom_conversions`  | Defines custom conversion rules                     |

---

### 🧩 **4. Entity Relationships**

```
[UOMType]
  └── hasMany → [UOMUnit]
  └── hasMany → [UOMCompoundUnit]
  └── hasMany → [UOMCustomUnit]

[UOMUnit]
  └── belongsTo → [UOMType]
  └── hasMany → [UOMConversion]
  └── hasMany → [UOMAlias]
  └── belongsToMany → [UOMUnitGroup]
  └── hasMany → [UOMPackaging]
  └── hasMany → [UOMConversionLog]

[UOMConversion]
  └── belongsTo → [UOMUnit] (source/target)
...
(see full diagram for all model mappings)
```

---

### 🧰 **5. Model Factories**

#### **Factory Coverage**

| Factory                      | Purpose                            |
| ---------------------------- | ---------------------------------- |
| `UOMTypeFactory`             | Generates measurement categories   |
| `UOMUnitFactory`             | Generates unit records             |
| `UOMConversionFactory`       | Defines conversion paths           |
| `UOMAliasFactory`            | Adds aliases for lookup            |
| `UOMCompoundUnitFactory`     | Creates compound units             |
| `UOMPackagingFactory`        | Defines package-unit hierarchies   |
| `UOMItemFactory`             | Generates sample items             |
| `UOMConversionLogFactory`    | Seeds conversion audit logs        |
| `UOMCustomUnitFactory`       | Creates user-defined units         |
| `UOMCustomConversionFactory` | Defines custom conversion formulas |

---

### ⚙️ **6. Traits & Helpers**

#### **6.1 Core Traits**

| Trait          | Purpose                              |
| -------------- | ------------------------------------ |
| `HasFactory`   | Enables model factories              |
| `HasSlug`      | Auto-generates slugs                 |
| `HasMetadata`  | Adds flexible JSON data              |
| `HasPrecision` | Rounds conversion results            |
| `HasSymbol`    | Manages symbol lookup and formatting |

#### **6.2 Audit & Traceability**

| Trait               | Purpose                     |
| ------------------- | --------------------------- |
| `LogsConversions`   | Auto-logs conversion events |
| `Auditable`         | Tracks user activity        |
| `ImmutableLoggable` | Prevents log modification   |

#### **6.3 Packaging & Hierarchy**

| Trait                   | Purpose                         |
| ----------------------- | ------------------------------- |
| `Packagable`            | Adds methods like `toPackage()` |
| `HasPackagingHierarchy` | Supports multi-level packaging  |
| `BelongsToPackaging`    | Used in `UOMItemPackaging`      |

#### **6.4 Conversion**

| Trait                      | Purpose                              |
| -------------------------- | ------------------------------------ |
| `Convertible`              | Adds `convertTo()` / `convertFrom()` |
| `HasConversionFactor`      | Linear conversion logic              |
| `SupportsCustomConversion` | For user-defined formulas            |
| `CompoundConvertible`      | Compound unit conversions            |

---

### 🧠 **7. Observers**

| Observer                | Purpose                                  |
| ----------------------- | ---------------------------------------- |
| `UOMUnitObserver`       | Enforces base unit rules, cleans aliases |
| `UOMConversionObserver` | Monitors and logs conversion updates     |
| `UOMPackagingObserver`  | Ensures data consistency on updates      |
| `UOMCustomUnitObserver` | Validates user-defined unit integrity    |

---

### 🧪 **8. Testing Requirements**

**Framework:** Orchestra Testbench
**Traits:** `RefreshDatabase`, `TestableConversion`
**Factories:** Used to seed consistent test data

#### **8.1 Test Categories**

| Category       | Example Test                             |
| -------------- | ---------------------------------------- |
| Conversion     | `test_linear_conversion_between_units`   |
| Packaging      | `test_convert_package_to_base_units`     |
| Compound Units | `test_compound_conversion_between_units` |
| Logging        | `test_conversion_log_is_created`         |
| Aliases        | `test_unit_can_be_found_by_alias`        |
| Custom Units   | `test_custom_conversion_applies_formula` |

#### **8.2 Coverage Targets**

* 100% coverage on `UOMUnit`, `UOMConversion`, and `UnitConverter`
* Integration coverage for packaging and audit subsystems

---

### ⚙️ **9. Configuration File: `config/uom.php`**

#### **9.1 Conversion Settings**

```php
'conversion' => [
    'default_precision' => 2,
    'allow_custom_formulas' => true,
    'fallback_to_base_unit' => true,
],
```

#### **9.2 Packaging**

```php
'packaging' => [
    'max_depth' => 3,
    'allow_circular' => false,
],
```

#### **9.3 Audit Logging**

```php
'audit' => [
    'log_conversions' => true,
    'immutable_logs' => true,
    'track_user' => true,
],
```

#### **9.4 Units**

```php
'units' => [
    'enforce_unique_aliases' => true,
    'auto_slug' => true,
    'default_base_unit_per_type' => true,
],
```

#### **9.5 Custom Units**

```php
'custom_units' => [
    'enabled' => true,
    'require_user_ownership' => true,
],
```

#### **9.6 Seeder**

```php
'seeder' => [
    'enabled' => true,
    'default_sets' => ['metric_mass', 'imperial_length'],
],
```

**Publishing Command:**

```bash
php artisan vendor:publish --tag=laravel-uom-management-config
```

---

### 🧩 **10. Service Provider Registration**

```php
use Spatie\LaravelPackageTools\Package;

public function configurePackage(Package $package): void
{
    $package
        ->name('laravel-uom-management')
        ->hasConfigFile()
        ->hasMigrations([
            // migration files
        ]);
}
```

---

### 📋 **11. Deliverables**

| Deliverable     | Description                                |
| --------------- | ------------------------------------------ |
| Core Package    | Fully functional Laravel package           |
| Migrations      | Database schema for all entities           |
| Config File     | Customizable behavior via `config/uom.php` |
| Model Factories | Test data generation                       |
| Documentation   | `docs/UOM_Usage_Guide.md`                  |
| Test Suite      | 100% coverage for core logic               |
| Example Seeder  | Predefined metric & imperial unit sets     |

---

### 🚀 **12. Future Enhancements**

* Support for **multi-language unit names**
* Integration with **Laravel Nova / Filament Admin Panels**
* Add **graph-based conversion resolution**
* Publish official **UOM API endpoint**

---
