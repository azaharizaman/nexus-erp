---
goal: Implement Office Management System (Package Integration)
version: 1.0
date_created: 2025-11-09
last_updated: 2025-11-09
owner: Backoffice Domain Team
status: 'Planned'
tags: [feature, backoffice, office, package-integration, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan details the integration of the Office Management system for the Laravel ERP using the `azaharizaman/laravel-backoffice` package. The Office Management module enables physical location management across the organization, supporting office types (HQ, Branch, Warehouse), hierarchical relationships, and contact information. This module provides the physical infrastructure foundation for the ERP system and links organizational structure to physical locations.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Integrate `azaharizaman/laravel-backoffice` package for Office model
- **REQ-002**: Support office types: HQ, Branch, Warehouse, Remote, Retail, Manufacturing
- **REQ-003**: Implement parent-child office relationships for multi-level hierarchy
- **REQ-004**: Store office address details (street, city, state, country, postal code)
- **REQ-005**: Link offices to companies from Company Management module
- **REQ-006**: Store office contact information (phone, email, fax)
- **REQ-007**: Support office status management (Active, Inactive, Closed)
- **REQ-008**: Enable office operating hours storage
- **REQ-009**: Create RESTful API endpoints for office CRUD operations
- **REQ-010**: Implement CLI commands for office management
- **REQ-011**: Apply tenant isolation to all office records

**Security Requirements:**
- **SEC-001**: Apply tenant scope to all office queries
- **SEC-002**: Implement authorization policies for office management operations
- **SEC-003**: Restrict office modifications to authorized users with manage-offices permission
- **SEC-004**: Log all office changes for audit trail
- **SEC-005**: Validate office-company relationships within same tenant

**Performance Constraints:**
- **CON-001**: Office queries with relationships must complete in under 50ms
- **CON-002**: Support minimum 100 offices per tenant
- **CON-003**: Office listing with pagination must handle 1000+ offices efficiently

**Integration Guidelines:**
- **GUD-001**: Use package's Office model as base, extend for ERP-specific features
- **GUD-002**: Apply BelongsToTenant trait for multi-tenancy support
- **GUD-003**: Integrate with Company Management for company assignment
- **GUD-004**: Integrate with Warehouse Management for warehouse location linking
- **GUD-005**: Follow package documentation for model configuration

**Design Patterns:**
- **PAT-001**: Use repository pattern for office data access
- **PAT-002**: Implement action pattern for office operations
- **PAT-003**: Use resource classes for API transformation
- **PAT-004**: Apply service layer for complex office operations

## 2. Implementation Steps

> **Note:** This implementation has been condensed from 12 phases to 6 phases for better project management. All 95 original tasks are preserved with their full details. Tasks are grouped by architectural layers for logical flow and reduced context switching.

### Implementation Phase 1: Package Setup & Database Schema

**Objective:** Configure laravel-backoffice package and extend office schema for ERP requirements

#### Package Configuration
- GOAL-001: Configure laravel-backoffice package for office management

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Verify `azaharizaman/laravel-backoffice` package installation from PRD-06 | | |
| TASK-002 | Publish office-related migrations if separate from company migrations | | |
| TASK-003 | Review published office migration structure | | |
| TASK-004 | Verify package configuration for office model | | |

#### Database Schema Extension
- GOAL-002: Extend office schema for tenant isolation and ERP requirements

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-005 | Create migration to add tenant_id column to offices table with foreign key | | |
| TASK-006 | Create migration to add company_id foreign key linking to companies table | | |
| TASK-007 | Create indexes on offices(tenant_id, status) and offices(company_id) | | |
| TASK-008 | Create migration to add office_type enum column (HQ, Branch, Warehouse, Remote, Retail, Manufacturing) | | |
| TASK-009 | Add address columns: street_address, city, state, country, postal_code | | |
| TASK-010 | Add contact columns: phone, email, fax, contact_person | | |
| TASK-011 | Add operational columns: operating_hours (JSON), timezone, is_active | | |
| TASK-012 | Add latitude and longitude columns for mapping (decimal, nullable) | | |
| TASK-013 | Run migrations to update database schema | | |

---

### Implementation Phase 2: Model & Repository Layer

**Objective:** Extend package Office model and implement repository pattern for data access

#### Model Extension
- GOAL-003: Extend package Office model with ERP functionality

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | Create Office model extending package Office in app/Domains/Backoffice/Models/Office.php | | |
| TASK-015 | Add BelongsToTenant trait to Office model | | |
| TASK-016 | Add LogsActivity trait for audit logging | | |
| TASK-017 | Configure $fillable array with new ERP fields | | |
| TASK-018 | Configure $casts array: operating_hours => array, is_active => boolean | | |
| TASK-019 | Add company() BelongsTo relationship | | |
| TASK-020 | Add parent() BelongsTo self-referencing relationship | | |
| TASK-021 | Add children() HasMany self-referencing relationship | | |
| TASK-022 | Add warehouses() HasMany relationship for future integration | | |
| TASK-023 | Create OfficeType enum in app/Domains/Backoffice/Enums/OfficeType.php | | |
| TASK-024 | Add scopeActive() query scope | | |
| TASK-025 | Add scopeByType() query scope | | |
| TASK-026 | Add getFullAddressAttribute() accessor returning formatted address | | |

#### Repository Layer
- GOAL-004: Implement repository for office data access

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create OfficeRepositoryInterface in app/Domains/Backoffice/Contracts/OfficeRepositoryInterface.php | | |
| TASK-028 | Define methods: findById(), findByCompany(), create(), update(), delete(), getByType() | | |
| TASK-029 | Create OfficeRepository in app/Domains/Backoffice/Repositories/OfficeRepository.php | | |
| TASK-030 | Implement findById() with eager loading of company and parent | | |
| TASK-031 | Implement findByCompany() to retrieve offices for a company | | |
| TASK-032 | Implement create() with validation and tenant assignment | | |
| TASK-033 | Implement update() with validation | | |
| TASK-034 | Implement delete() with soft delete support | | |
| TASK-035 | Implement getByType() to filter offices by type | | |
| TASK-036 | Implement getHierarchy() to build office tree structure | | |
| TASK-037 | Bind OfficeRepositoryInterface to OfficeRepository in BackofficeServiceProvider | | |

---

### Implementation Phase 3: Business Logic Layer

**Objective:** Create action classes and event system for office operations

#### Action Classes
- GOAL-005: Create action classes for office operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-038 | Create CreateOfficeAction in app/Domains/Backoffice/Actions/CreateOfficeAction.php | | |
| TASK-039 | Implement handle() method with validation, company verification, audit logging | | |
| TASK-040 | Add asController() and asJob() methods | | |
| TASK-041 | Create UpdateOfficeAction in app/Domains/Backoffice/Actions/UpdateOfficeAction.php | | |
| TASK-042 | Implement handle() with validation and relationship verification | | |
| TASK-043 | Create DeleteOfficeAction in app/Domains/Backoffice/Actions/DeleteOfficeAction.php | | |
| TASK-044 | Implement handle() with dependency checking (child offices, warehouses, staff) | | |
| TASK-045 | Create GetOfficesByCompanyAction in app/Domains/Backoffice/Actions/GetOfficesByCompanyAction.php | | |
| TASK-046 | Implement handle() to retrieve offices for a specific company | | |

#### Events & Listeners
- GOAL-011: Implement event system for office operations

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-086 | Create OfficeCreatedEvent in app/Domains/Backoffice/Events/OfficeCreatedEvent.php | | |
| TASK-087 | Create OfficeUpdatedEvent in app/Domains/Backoffice/Events/OfficeUpdatedEvent.php | | |
| TASK-088 | Create OfficeDeletedEvent in app/Domains/Backoffice/Events/OfficeDeletedEvent.php | | |
| TASK-089 | Dispatch events from action classes | | |
| TASK-090 | Create LogOfficeActivityListener for audit trail | | |
| TASK-091 | Register events in EventServiceProvider | | |

---

### Implementation Phase 4: API Layer

**Objective:** Build RESTful API with controllers, validation, resources, and authorization

#### API Controllers & Routes
- GOAL-006: Implement RESTful API endpoints

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-047 | Create OfficeController in app/Http/Controllers/Api/V1/Backoffice/OfficeController.php | | |
| TASK-048 | Implement index() with filtering by company, type, status | | |
| TASK-049 | Implement store() using CreateOfficeAction | | |
| TASK-050 | Implement show() to retrieve single office with relationships | | |
| TASK-051 | Implement update() using UpdateOfficeAction | | |
| TASK-052 | Implement destroy() using DeleteOfficeAction | | |
| TASK-053 | Add routes in routes/api.php under /api/v1/backoffice/offices | | |
| TASK-054 | Apply auth:sanctum middleware to all routes | | |
| TASK-055 | Apply can:manage-offices middleware to modification routes | | |

#### Request Validation
- GOAL-007: Implement form requests for input validation

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-056 | Create StoreOfficeRequest in app/Http/Requests/Backoffice/StoreOfficeRequest.php | | |
| TASK-057 | Define validation rules: name (required), company_id (required, exists), office_type (required, enum) | | |
| TASK-058 | Add address validation rules (street_address, city, country required) | | |
| TASK-059 | Add contact validation rules (email format, phone format) | | |
| TASK-060 | Implement authorize() with policy check | | |
| TASK-061 | Create UpdateOfficeRequest in app/Http/Requests/Backoffice/UpdateOfficeRequest.php | | |
| TASK-062 | Define validation rules for partial updates | | |
| TASK-063 | Add custom validation to verify company belongs to same tenant | | |

#### API Resources
- GOAL-008: Create API resource transformers

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-064 | Create OfficeResource in app/Http/Resources/Backoffice/OfficeResource.php | | |
| TASK-065 | Define toArray() returning office attributes | | |
| TASK-066 | Include company data using whenLoaded() | | |
| TASK-067 | Include parent office data using whenLoaded() | | |
| TASK-068 | Include children count | | |
| TASK-069 | Add formatted address field using getFullAddressAttribute() | | |
| TASK-070 | Add HATEOAS links | | |
| TASK-071 | Create OfficeCollection resource | | |

#### Authorization Policies
- GOAL-009: Implement authorization policies

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-072 | Create OfficePolicy in app/Domains/Backoffice/Policies/OfficePolicy.php | | |
| TASK-073 | Implement viewAny() checking manage-offices permission | | |
| TASK-074 | Implement view() checking tenant ownership | | |
| TASK-075 | Implement create() checking permission and company access | | |
| TASK-076 | Implement update() checking permission and tenant ownership | | |
| TASK-077 | Implement delete() with checks for dependent records | | |
| TASK-078 | Register OfficePolicy in AuthServiceProvider | | |

---

### Implementation Phase 5: CLI Commands & Integration

**Objective:** Create CLI commands and set up module integration points

#### CLI Commands
- GOAL-010: Create CLI commands for office management

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-079 | Create CreateOfficeCommand in app/Console/Commands/Backoffice/CreateOfficeCommand.php | | |
| TASK-080 | Implement handle() with interactive prompts | | |
| TASK-081 | Add options: --tenant-id, --company-id, --name, --type, --city | | |
| TASK-082 | Create ListOfficesCommand in app/Console/Commands/Backoffice/ListOfficesCommand.php | | |
| TASK-083 | Implement handle() displaying offices in table format | | |
| TASK-084 | Add filtering options: --tenant, --company, --type | | |
| TASK-085 | Register commands in Console/Kernel.php | | |

#### Integration Points
- GOAL-012: Set up integration with other modules

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-092 | Document office_id foreign key pattern for future modules | | |
| TASK-093 | Create helper method in Office model for warehouse assignment | | |
| TASK-094 | Create helper method for staff assignment | | |
| TASK-095 | Add validation for office-company tenant matching | | |

---

### Implementation Phase 6: Testing & Verification

**Objective:** Comprehensive testing across unit, feature, and integration levels

See **Section 6: Testing** below for complete test specifications (31 tests total).

## 3. Alternatives Considered

**ALT-001: Single Location Model vs Separate Office Model**
- **Approach**: Combine Office and Warehouse into single Location model
- **Rejected**: Separate models provide clearer domain boundaries and responsibilities
- **Rationale**: Office and Warehouse have different attributes and behaviors

**ALT-002: Storing Operating Hours as String vs JSON**
- **Approach**: Store operating hours as simple string (e.g., "9am-5pm")
- **Selected**: Use JSON format for structured data (day-wise hours)
- **Rationale**: JSON provides flexibility for different schedules per day

**ALT-003: Address as Separate Table vs Embedded Columns**
- **Approach**: Create separate addresses table with polymorphic relationship
- **Rejected**: Embedded columns in office table for MVP simplicity
- **Rationale**: Each office typically has one address; separate table adds complexity

## 4. Dependencies

**Internal Dependencies:**
- **DEP-001**: Core.001 - Multi-Tenancy System (tenant isolation)
- **DEP-002**: Core.002 - Authentication & Authorization (permissions, policies)
- **DEP-003**: Core.003 - Audit Logging (activity logging)
- **DEP-004**: Backoffice.001 - Company Management (company relationship)

**External Package Dependencies:**
- **DEP-005**: `azaharizaman/laravel-backoffice: dev-main` - Base Office model
- **DEP-006**: `spatie/laravel-activitylog: ^4.0` - Audit logging
- **DEP-007**: `lorisleiva/laravel-actions: ^2.0` - Action pattern

**Why dev-main stability?**
The `azaharizaman/laravel-backoffice` package is actively developed alongside this ERP. Using `dev-main` during development ensures access to latest features. Production will use stable tagged versions.

## 5. Files

**New Files to Create:**

- **FILE-001**: `app/Domains/Backoffice/Models/Office.php` - Extended Office model
- **FILE-002**: `app/Domains/Backoffice/Enums/OfficeType.php` - Office type enum
- **FILE-003**: `app/Domains/Backoffice/Contracts/OfficeRepositoryInterface.php` - Repository interface
- **FILE-004**: `app/Domains/Backoffice/Repositories/OfficeRepository.php` - Repository implementation
- **FILE-005**: `app/Domains/Backoffice/Actions/CreateOfficeAction.php` - Create action
- **FILE-006**: `app/Domains/Backoffice/Actions/UpdateOfficeAction.php` - Update action
- **FILE-007**: `app/Domains/Backoffice/Actions/DeleteOfficeAction.php` - Delete action
- **FILE-008**: `app/Domains/Backoffice/Actions/GetOfficesByCompanyAction.php` - Query action
- **FILE-009**: `app/Http/Controllers/Api/V1/Backoffice/OfficeController.php` - API controller
- **FILE-010**: `app/Http/Requests/Backoffice/StoreOfficeRequest.php` - Store validation
- **FILE-011**: `app/Http/Requests/Backoffice/UpdateOfficeRequest.php` - Update validation
- **FILE-012**: `app/Http/Resources/Backoffice/OfficeResource.php` - API resource
- **FILE-013**: `app/Http/Resources/Backoffice/OfficeCollection.php` - Collection resource
- **FILE-014**: `app/Domains/Backoffice/Policies/OfficePolicy.php` - Authorization policy
- **FILE-015**: `app/Console/Commands/Backoffice/CreateOfficeCommand.php` - Create CLI
- **FILE-016**: `app/Console/Commands/Backoffice/ListOfficesCommand.php` - List CLI
- **FILE-017**: `app/Domains/Backoffice/Events/OfficeCreatedEvent.php` - Created event
- **FILE-018**: `app/Domains/Backoffice/Events/OfficeUpdatedEvent.php` - Updated event
- **FILE-019**: `app/Domains/Backoffice/Events/OfficeDeletedEvent.php` - Deleted event
- **FILE-020**: `app/Domains/Backoffice/Listeners/LogOfficeActivityListener.php` - Activity listener
- **FILE-021**: `database/migrations/yyyy_mm_dd_hhmmss_add_tenant_to_offices_table.php` - Tenant migration
- **FILE-022**: `database/migrations/yyyy_mm_dd_hhmmss_add_erp_fields_to_offices_table.php` - ERP fields migration

**Modified Files:**

- **FILE-023**: `routes/api.php` - Add office API routes
- **FILE-024**: `app/Providers/AuthServiceProvider.php` - Register OfficePolicy
- **FILE-025**: `app/Providers/BackofficeServiceProvider.php` - Register office repository binding
- **FILE-026**: `app/Providers/EventServiceProvider.php` - Register office events

**Test Files:**

- **FILE-027**: `tests/Unit/Domains/Backoffice/Actions/CreateOfficeActionTest.php`
- **FILE-028**: `tests/Unit/Domains/Backoffice/Actions/UpdateOfficeActionTest.php`
- **FILE-029**: `tests/Unit/Domains/Backoffice/Actions/DeleteOfficeActionTest.php`
- **FILE-030**: `tests/Unit/Domains/Backoffice/Repositories/OfficeRepositoryTest.php`
- **FILE-031**: `tests/Feature/Api/V1/Backoffice/OfficeControllerTest.php`
- **FILE-032**: `tests/Feature/Console/Commands/CreateOfficeCommandTest.php`
- **FILE-033**: `database/factories/OfficeFactory.php` - Factory for testing

## 6. Testing

**Unit Tests:**

- **TEST-001**: CreateOfficeAction creates office with valid data
- **TEST-002**: CreateOfficeAction validates required fields
- **TEST-003**: CreateOfficeAction assigns tenant_id automatically
- **TEST-004**: CreateOfficeAction verifies company belongs to same tenant
- **TEST-005**: CreateOfficeAction validates office type enum values
- **TEST-006**: UpdateOfficeAction updates office correctly
- **TEST-007**: UpdateOfficeAction validates address format
- **TEST-008**: DeleteOfficeAction prevents deletion with child offices
- **TEST-009**: OfficeRepository findByCompany() filters correctly
- **TEST-010**: OfficeRepository getByType() returns correct offices

**Feature Tests:**

- **TEST-011**: POST /api/v1/backoffice/offices creates office returns 201
- **TEST-012**: GET /api/v1/backoffice/offices lists offices with pagination
- **TEST-013**: GET /api/v1/backoffice/offices?company_id=X filters by company
- **TEST-014**: GET /api/v1/backoffice/offices?type=Branch filters by type
- **TEST-015**: GET /api/v1/backoffice/offices/{id} returns single office with 200
- **TEST-016**: PATCH /api/v1/backoffice/offices/{id} updates office
- **TEST-017**: DELETE /api/v1/backoffice/offices/{id} soft deletes office
- **TEST-018**: API enforces authentication returning 401 for guests
- **TEST-019**: API enforces authorization returning 403 without permission
- **TEST-020**: API prevents cross-tenant access returning 404
- **TEST-021**: API validates invalid company_id returning 422
- **TEST-022**: API validates invalid office type returning 422

**Integration Tests:**

- **TEST-023**: Office creation triggers OfficeCreatedEvent
- **TEST-024**: Office update triggers OfficeUpdatedEvent
- **TEST-025**: Office deletion triggers OfficeDeletedEvent
- **TEST-026**: Office changes logged in activity log
- **TEST-027**: CLI command creates office successfully
- **TEST-028**: BelongsToTenant filters offices by tenant automatically
- **TEST-029**: Office with company from different tenant is rejected
- **TEST-030**: Office hierarchy maintains referential integrity
- **TEST-031**: GetFullAddressAttribute returns properly formatted address

## 7. Risks & Assumptions

**Risks:**

- **RISK-001**: Package API changes affecting Office model
  - **Mitigation**: Use integration tests, pin package version
  - **Likelihood**: Medium
  - **Impact**: High

- **RISK-002**: Address validation complexity for international offices
  - **Mitigation**: Use flexible validation, support multiple address formats
  - **Likelihood**: Medium
  - **Impact**: Low

- **RISK-003**: Operating hours JSON structure changes
  - **Mitigation**: Document JSON schema, version the structure
  - **Likelihood**: Low
  - **Impact**: Low

**Assumptions:**

- **ASSUMPTION-001**: Package provides adequate office management features
- **ASSUMPTION-002**: Single address per office is sufficient for MVP
- **ASSUMPTION-003**: Office hierarchy depth of 3 levels is sufficient
- **ASSUMPTION-004**: Average tenant has fewer than 100 offices
- **ASSUMPTION-005**: Operating hours stored as JSON are sufficient for scheduling
- **ASSUMPTION-006**: Offices belong to single company (no shared offices)

## 8. Related Specifications

**Related Implementation Plans:**
- [PRD-01: Multi-Tenancy System](./PRD-01-infrastructure-multitenancy-1.md) - Tenant isolation
- [PRD-02: Authentication & Authorization](./PRD-02-infrastructure-auth-1.md) - Security
- [PRD-03: Audit Logging System](./PRD-03-infrastructure-audit-1.md) - Activity logging
- [PRD-06: Company Management](./PRD-06-feature-company-management-1.md) - Company relationships
- [PRD-08: Department Management](./PRD-08-feature-department-management-1.md) - Department-office links
- [PRD-09: Staff Management](./PRD-09-feature-staff-management-1.md) - Staff-office assignment
- [PRD-11: Warehouse Management](./PRD-11-feature-warehouse-management-1.md) - Warehouse-office integration

**Source Requirements:**
- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md) - Section: Backoffice.002: Office Management

**Development Guidelines:**
- [MODULE-DEVELOPMENT.md](../docs/prd/MODULE-DEVELOPMENT.md) - Development standards
- [GitHub Copilot Instructions](../.github/copilot-instructions.md) - Coding patterns

---

**Version:** 1.0  
**Status:** Planned  
**Last Updated:** 2025-11-09
