---
goal: Implement Unit of Measure (UOM) Management System (Package Integration)
version: 1.0
date_created: 2025-11-09
last_updated: 2025-11-09
owner: Inventory Domain Team
status: 'Planned'
tags: [infrastructure, inventory, uom, package-integration, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan details the integration of the Unit of Measure (UOM) management system using the `azaharizaman/laravel-uom-management` package. The UOM system provides standardized measurement units, conversion factors, and multi-unit support essential for inventory, sales, and purchasing operations.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Integrate `azaharizaman/laravel-uom-management` package
- **REQ-002**: Support UOM types: Length, Weight, Volume, Area, Quantity, Time
- **REQ-003**: Enable custom UOM types per tenant
- **REQ-004**: Implement UOM conversion factors between units
- **REQ-005**: Support base unit per UOM type
- **REQ-006**: Enable compound units (e.g., kg/m³, $/hour)
- **REQ-007**: Link UOMs to items with base and alternate UOMs
- **REQ-008**: Support purchase/sales UOM defaults per item
- **REQ-009**: Create RESTful API endpoints for UOM management
- **REQ-010**: Implement CLI commands for UOM operations
- **REQ-011**: Apply tenant isolation to custom UOMs

**Security Requirements:**
- **SEC-001**: Apply tenant scope to custom UOM queries
- **SEC-002**: Implement authorization for UOM management
- **SEC-003**: Prevent modification of system-defined UOMs
- **SEC-004**: Log UOM conversions for audit trail
- **SEC-005**: Validate conversion accuracy and precision

**Performance Constraints:**
- **CON-001**: UOM conversion must complete in under 10ms
- **CON-002**: Support minimum 100 UOMs per tenant
- **CON-003**: UOM lookup queries must be cached

**Integration Guidelines:**
- **GUD-001**: Use package DefaultUnitConverter for conversions
- **GUD-002**: Apply BelongsToTenant trait to custom UOMs
- **GUD-003**: Follow package naming conventions
- **GUD-004**: Leverage package validation rules

**Design Patterns:**
- **PAT-001**: Use repository pattern for UOM data access
- **PAT-002**: Implement service layer for conversion logic
- **PAT-003**: Use resource classes for API responses
- **PAT-004**: Apply strategy pattern for different UOM types

## 2. Implementation Steps

### Phase 1: Package Installation (6 tasks)
- GOAL-001: Install and configure UOM package
- TASK-001: Add package to composer.json
- TASK-002: Run composer update
- TASK-003: Publish package migrations
- TASK-004: Publish package configuration
- TASK-005: Review published files
- TASK-006: Run migrations

### Phase 2: Database Extension (5 tasks)
- GOAL-002: Extend schema for tenant isolation
- TASK-007: Add tenant_id to custom_uoms table
- TASK-008: Create indexes
- TASK-009: Add is_system_defined flag
- TASK-010: Add tenant-specific columns
- TASK-011: Run migrations

### Phase 3: Model Extension (8 tasks)
- GOAL-003: Extend package models
- TASK-012: Create Uom model extending package
- TASK-013: Add BelongsToTenant trait
- TASK-014: Add LogsActivity trait
- TASK-015: Configure $fillable
- TASK-016: Add relationships
- TASK-017: Add query scopes
- TASK-018: Add conversion methods
- TASK-019: Create UomType enum

### Phase 4: Service Layer (8 tasks)
- GOAL-004: Create UOM conversion services
- TASK-020: Create UomConversionService
- TASK-021: Inject DefaultUnitConverter
- TASK-022: Implement convertQuantity() method
- TASK-023: Implement validateConversion()
- TASK-024: Implement getConversionFactor()
- TASK-025: Add precision handling
- TASK-026: Add rounding strategies
- TASK-027: Implement batch conversion support

### Phase 5: Repository (7 tasks)
- GOAL-005: Implement repository pattern
- TASK-028: Create UomRepositoryInterface
- TASK-029: Create UomRepository
- TASK-030: Implement CRUD methods
- TASK-031: Implement conversion lookup
- TASK-032: Implement type filtering
- TASK-033: Implement caching layer
- TASK-034: Bind interface in ServiceProvider

### Phase 6: Actions (6 tasks)
- GOAL-006: Create action classes
- TASK-035: Create CreateUomAction
- TASK-036: Create UpdateUomAction
- TASK-037: Create DeleteUomAction
- TASK-038: Create ConvertQuantityAction
- TASK-039: Create AddConversionFactorAction
- TASK-040: Implement validation logic

### Phase 7: API Layer (8 tasks)
- GOAL-007: Build REST API
- TASK-041: Create UomController
- TASK-042: Implement index() with filtering
- TASK-043: Implement store(), show(), update(), destroy()
- TASK-044: Add convert() endpoint for quantity conversion
- TASK-045: Add conversions() endpoint listing factors
- TASK-046: Create routes
- TASK-047: Apply middleware
- TASK-048: Add rate limiting for conversions

### Phase 8: Validation & Resources (6 tasks)
- GOAL-008: Create validation and resources
- TASK-049: Create StoreUomRequest
- TASK-050: Create UpdateUomRequest
- TASK-051: Add custom validation rules
- TASK-052: Create UomResource
- TASK-053: Create ConversionResource
- TASK-054: Add conversion examples

### Phase 9: Authorization (4 tasks)
- GOAL-009: Implement policies
- TASK-055: Create UomPolicy
- TASK-056: Implement policy methods
- TASK-057: Prevent system UOM modifications
- TASK-058: Register policy

### Phase 10: CLI Commands (5 tasks)
- GOAL-010: Create CLI commands
- TASK-059: Create SeedUomsCommand
- TASK-060: Create ConvertQuantityCommand
- TASK-061: Create ListUomsCommand
- TASK-062: Add conversion testing command
- TASK-063: Register commands

### Phase 11: Seeding (6 tasks)
- GOAL-011: Seed standard UOMs
- TASK-064: Create UomSeeder
- TASK-065: Seed length units (mm, cm, m, km, in, ft, yd, mi)
- TASK-066: Seed weight units (mg, g, kg, ton, oz, lb)
- TASK-067: Seed volume units (ml, l, gal, qt, pt)
- TASK-068: Seed quantity units (ea, dozen, gross)
- TASK-069: Seed conversion factors

### Phase 12: Integration (5 tasks)
- GOAL-012: Prepare for item integration
- TASK-070: Document UOM foreign key pattern
- TASK-071: Create item-uom relationship helpers
- TASK-072: Add conversion validation for items
- TASK-073: Create UOM selection helpers
- TASK-074: Document conversion best practices

## 3. Alternatives Considered

**ALT-001**: Custom UOM vs Package Integration
- **Selected**: Package integration for proven conversion logic

**ALT-002**: Database Storage of Conversions vs Calculated
- **Selected**: Database storage with calculation fallback

## 4. Dependencies

**Internal Dependencies:**
- **DEP-001**: Core.001 - Multi-Tenancy System
- **DEP-002**: Core.002 - Authentication & Authorization
- **DEP-003**: Core.003 - Audit Logging

**External Packages:**
- **DEP-004**: `azaharizaman/laravel-uom-management: dev-main`
- **DEP-005**: `brick/math: ^0.12` - Precise decimal calculations

## 5. Files

**New Files:** (18 files)
- `app/Domains/Inventory/Models/Uom.php`
- `app/Domains/Inventory/Enums/UomType.php`
- `app/Domains/Inventory/Services/UomConversionService.php`
- `app/Domains/Inventory/Repositories/UomRepository.php`
- `app/Domains/Inventory/Actions/CreateUomAction.php`
- `app/Http/Controllers/Api/V1/Inventory/UomController.php`
- `app/Http/Requests/Inventory/StoreUomRequest.php`
- `app/Http/Resources/Inventory/UomResource.php`
- `app/Domains/Inventory/Policies/UomPolicy.php`
- `app/Console/Commands/Inventory/SeedUomsCommand.php`
- `database/seeders/UomSeeder.php`
- 7 test files

**Modified Files:** (3 files)
- `composer.json` - Add package
- `routes/api.php` - Add routes
- `app/Providers/AuthServiceProvider.php`

## 6. Testing

**Unit Tests:** (8 tests)
- TEST-001: UOM conversion accuracy
- TEST-002: Conversion factor validation
- TEST-003: Invalid conversion handling
- TEST-004: Precision and rounding
- TEST-005: Compound unit parsing

**Feature Tests:** (10 tests)
- TEST-006: API CRUD operations
- TEST-007: Conversion endpoint accuracy
- TEST-008: Tenant isolation
- TEST-009: System UOM protection
- TEST-010: Conversion caching

**Integration Tests:** (5 tests)
- TEST-011: Package integration working
- TEST-012: Seeded UOMs correct
- TEST-013: Conversion performance
- TEST-014: Multi-step conversions
- TEST-015: Item-UOM integration

## 7. Risks & Assumptions

**Risks:**
- RISK-001: Conversion inaccuracy with floating point
  - Mitigation: Use brick/math for decimal precision
- RISK-002: Performance degradation with complex conversions
  - Mitigation: Implement caching

**Assumptions:**
- ASSUMPTION-001: Package provides adequate conversion logic
- ASSUMPTION-002: Standard UOMs sufficient for most tenants
- ASSUMPTION-003: Two-step maximum conversion sufficient

## 8. Related Specifications

- [PRD-01: Multi-Tenancy](./PRD-01-infrastructure-multitenancy-1.md)
- [PRD-10: Item Master](./PRD-10-feature-item-master-1.md)
- [PRD-12: Stock Management](./PRD-12-feature-stock-management-1.md)
- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md)

---

**Version:** 1.0  
**Status:** Planned  
**Last Updated:** 2025-11-09
