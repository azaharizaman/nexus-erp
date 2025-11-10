---
goal: Implement Department Management System (Package Integration)
version: 1.0
date_created: 2025-11-09
last_updated: 2025-11-09
owner: Backoffice Domain Team
status: 'Planned'
tags: [feature, backoffice, department, package-integration, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan details the integration of the Department Management system for the Laravel ERP using the `azaharizaman/laravel-backoffice` package. The Department Management module enables logical organizational structure management, supporting hierarchical departments, department types, and department head assignments. This module provides the organizational framework for staff allocation and workflow routing.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Integrate `azaharizaman/laravel-backoffice` package for Department model
- **REQ-002**: Support parent-child department relationships for hierarchical structure
- **REQ-003**: Store department details (name, code, description)
- **REQ-004**: Support department types (Operations, Sales, Finance, IT, HR, etc.)
- **REQ-005**: Link departments to companies and offices
- **REQ-006**: Support department head assignment (staff member)
- **REQ-007**: Implement department status management (Active, Inactive)
- **REQ-008**: Create RESTful API endpoints for department CRUD operations
- **REQ-009**: Implement CLI commands for department management
- **REQ-010**: Apply tenant isolation to all department records

**Security Requirements:**
- **SEC-001**: Apply tenant scope to all department queries
- **SEC-002**: Implement authorization policies for department operations
- **SEC-003**: Restrict department modifications to authorized users
- **SEC-004**: Log all department changes for audit trail
- **SEC-005**: Validate department-company relationships within tenant

**Performance Constraints:**
- **CON-001**: Department hierarchy queries must complete in under 50ms
- **CON-002**: Support minimum 50 departments per tenant
- **CON-003**: Department listing must handle 500+ departments efficiently

**Integration Guidelines:**
- **GUD-001**: Use package Department model as base
- **GUD-002**: Apply BelongsToTenant trait for multi-tenancy
- **GUD-003**: Integrate with Company and Office Management
- **GUD-004**: Integrate with Staff Management for assignments

**Design Patterns:**
- **PAT-001**: Use repository pattern for data access
- **PAT-002**: Implement action pattern for operations
- **PAT-003**: Use resource classes for API responses
- **PAT-004**: Apply service layer for hierarchy operations

## 2. Implementation Steps

> **Note:** This implementation has been condensed from 10 phases to 6 phases for better project management. All 56 original tasks are preserved with their full details. Tasks are grouped by architectural layers for logical flow and reduced context switching.

### Implementation Phase 1: Package Setup & Database Schema

**Objective:** Configure package and extend schema for ERP requirements

#### Package Configuration
- GOAL-001: Configure package for department management

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Verify laravel-backoffice package from PRD-06 | | |
| TASK-002 | Publish department migrations if separate | | |
| TASK-003 | Review department migration structure | | |

#### Database Schema
- GOAL-002: Extend schema for ERP requirements

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-004 | Create migration adding tenant_id to departments table | | |
| TASK-005 | Add company_id and office_id foreign keys | | |
| TASK-006 | Create indexes on (tenant_id, status), (company_id), (office_id) | | |
| TASK-007 | Add department_type enum column | | |
| TASK-008 | Add department_head_id foreign key to staff table | | |
| TASK-009 | Add cost_center_code column (nullable) | | |
| TASK-010 | Add is_active boolean with default true | | |
| TASK-011 | Run migrations | | |

---

### Implementation Phase 2: Model & Repository Layer

**Objective:** Create Department model with relationships and implement repository pattern

#### Model & Enums
- GOAL-003: Create Department model with relationships

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-012 | Create Department model extending package in app/Domains/Backoffice/Models/Department.php | | |
| TASK-013 | Add BelongsToTenant and LogsActivity traits | | |
| TASK-014 | Configure $fillable, $casts arrays | | |
| TASK-015 | Create DepartmentType enum in app/Domains/Backoffice/Enums/DepartmentType.php | | |
| TASK-016 | Add company() BelongsTo relationship | | |
| TASK-017 | Add office() BelongsTo relationship | | |
| TASK-018 | Add parent() BelongsTo self-reference | | |
| TASK-019 | Add children() HasMany self-reference | | |
| TASK-020 | Add head() BelongsTo Staff relationship | | |
| TASK-021 | Add staff() HasMany relationship | | |
| TASK-022 | Add scopeActive() and scopeByType() | | |

#### Repository Layer
- GOAL-004: Implement repository pattern

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-023 | Create DepartmentRepositoryInterface in app/Domains/Backoffice/Contracts/ | | |
| TASK-024 | Define methods: findById(), findByCompany(), create(), update(), delete() | | |
| TASK-025 | Create DepartmentRepository implementation | | |
| TASK-026 | Implement all interface methods | | |
| TASK-027 | Implement getHierarchy() for tree structure | | |
| TASK-028 | Bind interface in BackofficeServiceProvider | | |

---

### Implementation Phase 3: Business Logic Layer

**Objective:** Create action classes and event system

#### Actions
- GOAL-005: Create action classes

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-029 | Create CreateDepartmentAction | | |
| TASK-030 | Implement handle() with validation | | |
| TASK-031 | Create UpdateDepartmentAction | | |
| TASK-032 | Create DeleteDepartmentAction | | |
| TASK-033 | Create AssignDepartmentHeadAction | | |
| TASK-034 | Implement department head assignment logic | | |

#### Events
- GOAL-010: Implement event system

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-052 | Create DepartmentCreatedEvent | | |
| TASK-053 | Create DepartmentUpdatedEvent | | |
| TASK-054 | Create DepartmentDeletedEvent | | |
| TASK-055 | Create DepartmentHeadAssignedEvent | | |
| TASK-056 | Register events in EventServiceProvider | | |

---

### Implementation Phase 4: API Layer

**Objective:** Build RESTful API with controllers, validation, resources, and authorization

#### API Layer
- GOAL-006: Build REST API endpoints

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-035 | Create DepartmentController | | |
| TASK-036 | Implement index() with filtering | | |
| TASK-037 | Implement store(), show(), update(), destroy() | | |
| TASK-038 | Add assignHead() method | | |
| TASK-039 | Create routes in routes/api.php | | |
| TASK-040 | Apply middleware | | |

#### Validation & Resources
- GOAL-007: Create validation and API resources

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-041 | Create StoreDepartmentRequest | | |
| TASK-042 | Create UpdateDepartmentRequest | | |
| TASK-043 | Add validation rules | | |
| TASK-044 | Create DepartmentResource | | |
| TASK-045 | Create DepartmentCollection | | |

#### Authorization
- GOAL-008: Implement policies

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-046 | Create DepartmentPolicy | | |
| TASK-047 | Implement policy methods | | |
| TASK-048 | Register policy in AuthServiceProvider | | |

---

### Implementation Phase 5: CLI Commands

**Objective:** Create CLI commands for department management

- GOAL-009: Create CLI commands

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-049 | Create CreateDepartmentCommand | | |
| TASK-050 | Create ListDepartmentsCommand | | |
| TASK-051 | Register commands | | |

---

### Implementation Phase 6: Testing & Verification

**Objective:** Comprehensive testing across unit, feature, and integration levels

See **Section 6: Testing** below for complete test specifications (13 tests total).

## 3. Alternatives Considered

**ALT-001: Flat Department Structure vs Hierarchical**
- **Rejected**: Flat structure limits organizational modeling
- **Selected**: Hierarchical structure for realistic org charts

**ALT-002: Single Department Head vs Multiple**
- **Selected**: Single head for MVP simplicity
- **Future**: Support co-heads or acting heads

## 4. Dependencies

**Internal Dependencies:**
- **DEP-001**: Core.001 - Multi-Tenancy System
- **DEP-002**: Core.002 - Authentication & Authorization
- **DEP-003**: Core.003 - Audit Logging
- **DEP-004**: Backoffice.001 - Company Management
- **DEP-005**: Backoffice.002 - Office Management
- **DEP-006**: Backoffice.004 - Staff Management (for department head)

**External Package Dependencies:**
- **DEP-007**: `azaharizaman/laravel-backoffice: dev-main`
- **DEP-008**: `spatie/laravel-activitylog: ^4.0`
- **DEP-009**: `lorisleiva/laravel-actions: ^2.0`

## 5. Files

**New Files:**
- **FILE-001**: `app/Domains/Backoffice/Models/Department.php`
- **FILE-002**: `app/Domains/Backoffice/Enums/DepartmentType.php`
- **FILE-003**: `app/Domains/Backoffice/Contracts/DepartmentRepositoryInterface.php`
- **FILE-004**: `app/Domains/Backoffice/Repositories/DepartmentRepository.php`
- **FILE-005**: `app/Domains/Backoffice/Actions/CreateDepartmentAction.php`
- **FILE-006**: `app/Domains/Backoffice/Actions/UpdateDepartmentAction.php`
- **FILE-007**: `app/Domains/Backoffice/Actions/DeleteDepartmentAction.php`
- **FILE-008**: `app/Domains/Backoffice/Actions/AssignDepartmentHeadAction.php`
- **FILE-009**: `app/Http/Controllers/Api/V1/Backoffice/DepartmentController.php`
- **FILE-010**: `app/Http/Requests/Backoffice/StoreDepartmentRequest.php`
- **FILE-011**: `app/Http/Requests/Backoffice/UpdateDepartmentRequest.php`
- **FILE-012**: `app/Http/Resources/Backoffice/DepartmentResource.php`
- **FILE-013**: `app/Domains/Backoffice/Policies/DepartmentPolicy.php`
- **FILE-014**: `app/Console/Commands/Backoffice/CreateDepartmentCommand.php`
- **FILE-015**: `app/Domains/Backoffice/Events/DepartmentCreatedEvent.php`
- **FILE-016**: `app/Domains/Backoffice/Events/DepartmentHeadAssignedEvent.php`
- **FILE-017**: `database/migrations/yyyy_mm_dd_add_tenant_to_departments_table.php`

**Modified Files:**
- **FILE-018**: `routes/api.php`
- **FILE-019**: `app/Providers/AuthServiceProvider.php`
- **FILE-020**: `app/Providers/BackofficeServiceProvider.php`

**Test Files:**
- **FILE-021**: `tests/Unit/Domains/Backoffice/Actions/CreateDepartmentActionTest.php`
- **FILE-022**: `tests/Feature/Api/V1/Backoffice/DepartmentControllerTest.php`
- **FILE-023**: `database/factories/DepartmentFactory.php`

## 6. Testing

**Unit Tests:**
- **TEST-001**: CreateDepartmentAction creates department successfully
- **TEST-002**: Department validates required fields
- **TEST-003**: Department hierarchy prevents circular references
- **TEST-004**: AssignDepartmentHeadAction assigns head correctly
- **TEST-005**: Department filtering by company works

**Feature Tests:**
- **TEST-006**: POST /api/v1/backoffice/departments creates department
- **TEST-007**: GET /api/v1/backoffice/departments lists departments
- **TEST-008**: API filters departments by company
- **TEST-009**: API enforces tenant isolation
- **TEST-010**: API validates department type enum

**Integration Tests:**
- **TEST-011**: Department events fire correctly
- **TEST-012**: Activity logging works
- **TEST-013**: Department head assignment updates relationships

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Package API changes
- **RISK-002**: Complex department hierarchies affecting performance

**Assumptions:**
- **ASSUMPTION-001**: Single department head sufficient for MVP
- **ASSUMPTION-002**: Department hierarchy depth of 4 levels sufficient
- **ASSUMPTION-003**: Departments belong to single company

## 8. Related Specifications

- [PRD-01: Multi-Tenancy](./PRD-01-infrastructure-multitenancy-1.md)
- [PRD-06: Company Management](./PRD-06-feature-company-management-1.md)
- [PRD-07: Office Management](./PRD-07-feature-office-management-1.md)
- [PRD-09: Staff Management](./PRD-09-feature-staff-management-1.md)
- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md)

---

**Version:** 1.0  
**Status:** Planned  
**Last Updated:** 2025-11-09
