---
goal: Implement Staff Management System (Package Integration)
version: 1.0
date_created: 2025-11-09
last_updated: 2025-11-09
owner: Backoffice Domain Team
status: 'Planned'
tags: [feature, backoffice, staff, hr, package-integration, phase-1, mvp]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

This implementation plan details the integration of the Staff Management system for the Laravel ERP using the `azaharizaman/laravel-backoffice` package. The Staff Management module enables employee information management, organizational assignment (company, office, department), reporting line tracking, and staff status management. This module provides the foundation for HR operations and links staff to organizational workflow.

## 1. Requirements & Constraints

**Core Requirements:**
- **REQ-001**: Integrate `azaharizaman/laravel-backoffice` package for Staff model
- **REQ-002**: Store staff personal information (name, employee ID, contact details)
- **REQ-003**: Link staff to User accounts for system access
- **REQ-004**: Assign staff to company, office, and department
- **REQ-005**: Implement reporting line management (manager assignment)
- **REQ-006**: Support staff status (Active, Inactive, On Leave, Terminated)
- **REQ-007**: Store staff employment details (hire date, position, job title)
- **REQ-008**: Enable organizational chart generation
- **REQ-009**: Create RESTful API endpoints for staff CRUD operations
- **REQ-010**: Implement CLI commands for staff management
- **REQ-011**: Apply tenant isolation to all staff records

**Security Requirements:**
- **SEC-001**: Apply tenant scope to all staff queries
- **SEC-002**: Implement authorization policies for staff operations
- **SEC-003**: Restrict staff data access based on organizational hierarchy
- **SEC-004**: Log all staff changes for audit trail
- **SEC-005**: Validate user-staff relationship within tenant
- **SEC-006**: Protect sensitive staff information (salary, personal data)

**Performance Constraints:**
- **CON-001**: Staff queries with relationships must complete in under 50ms
- **CON-002**: Support minimum 500 staff per tenant
- **CON-003**: Organizational chart generation must complete in under 200ms

**Integration Guidelines:**
- **GUD-001**: Use package Staff model as base
- **GUD-002**: Apply BelongsToTenant trait for multi-tenancy
- **GUD-003**: Link Staff to User model for authentication
- **GUD-004**: Integrate with Company, Office, Department models
- **GUD-005**: Support future HR module integration (payroll, attendance, leave)

**Design Patterns:**
- **PAT-001**: Use repository pattern for data access
- **PAT-002**: Implement action pattern for operations
- **PAT-003**: Use resource classes for API transformation
- **PAT-004**: Apply service layer for organizational chart building

## 2. Implementation Steps

> **Note:** This implementation has been condensed from 12 phases to 6 phases for better project management. All 100 original tasks are preserved with their full details. Tasks are grouped by architectural layers for logical flow and reduced context switching.

### Implementation Phase 1: Package Setup & Database Schema

**Objective:** Configure package and extend schema for ERP requirements

#### Package Configuration
- GOAL-001: Configure package for staff management

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Verify laravel-backoffice package installation | | |
| TASK-002 | Publish staff migrations if separate | | |
| TASK-003 | Review staff table structure | | |

#### Database Schema
- GOAL-002: Extend schema for ERP requirements

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-004 | Create migration adding tenant_id to staff table | | |
| TASK-005 | Add user_id foreign key linking to users table (nullable) | | |
| TASK-006 | Add company_id, office_id, department_id foreign keys | | |
| TASK-007 | Add manager_id self-referencing foreign key | | |
| TASK-008 | Create indexes on (tenant_id, status), (user_id), (manager_id) | | |
| TASK-009 | Add staff_status enum column (Active, Inactive, On Leave, Terminated) | | |
| TASK-010 | Add employment columns: employee_id, hire_date, termination_date, job_title, position_level | | |
| TASK-011 | Add contact columns: work_email, work_phone, emergency_contact_name, emergency_contact_phone | | |
| TASK-012 | Add is_active boolean with default true | | |
| TASK-013 | Run migrations | | |

---

### Implementation Phase 2: Model & Repository Layer

**Objective:** Create Staff model with relationships and implement repository pattern

#### Model & Enums
- GOAL-003: Create Staff model with relationships

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-014 | Create Staff model extending package in app/Domains/Backoffice/Models/Staff.php | | |
| TASK-015 | Add BelongsToTenant and LogsActivity traits | | |
| TASK-016 | Configure $fillable, $casts, $dates arrays | | |
| TASK-017 | Create StaffStatus enum in app/Domains/Backoffice/Enums/StaffStatus.php | | |
| TASK-018 | Add user() BelongsTo relationship | | |
| TASK-019 | Add company() BelongsTo relationship | | |
| TASK-020 | Add office() BelongsTo relationship | | |
| TASK-021 | Add department() BelongsTo relationship | | |
| TASK-022 | Add manager() BelongsTo self-reference | | |
| TASK-023 | Add directReports() HasMany self-reference | | |
| TASK-024 | Add scopeActive(), scopeByDepartment(), scopeByOffice() | | |
| TASK-025 | Add getFullNameAttribute() accessor | | |
| TASK-026 | Add getIsManagerAttribute() accessor checking if has direct reports | | |

#### Repository Layer
- GOAL-004: Implement repository pattern

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-027 | Create StaffRepositoryInterface in app/Domains/Backoffice/Contracts/ | | |
| TASK-028 | Define methods: findById(), findByUser(), findByDepartment(), create(), update(), delete() | | |
| TASK-029 | Create StaffRepository implementation | | |
| TASK-030 | Implement findById() with eager loading | | |
| TASK-031 | Implement findByUser() to get staff by user ID | | |
| TASK-032 | Implement findByDepartment() and findByOffice() | | |
| TASK-033 | Implement getReportingChain() to build manager hierarchy | | |
| TASK-034 | Implement getOrganizationalChart() for full org chart | | |
| TASK-035 | Bind interface in BackofficeServiceProvider | | |

---

### Implementation Phase 3: Business Logic Layer

**Objective:** Create services, actions, and event system

#### Services
- GOAL-005: Create organizational services

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-036 | Create OrganizationalChartService in app/Domains/Backoffice/Services/ | | |
| TASK-037 | Implement buildChart() method returning tree structure | | |
| TASK-038 | Implement getManagerChain() for reporting line | | |
| TASK-039 | Implement getDirectReports() for subordinates | | |
| TASK-040 | Optimize queries with eager loading | | |

#### Actions
- GOAL-006: Create action classes

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-041 | Create CreateStaffAction | | |
| TASK-042 | Implement handle() with validation and user linking | | |
| TASK-043 | Create UpdateStaffAction | | |
| TASK-044 | Implement handle() with relationship validation | | |
| TASK-045 | Create DeleteStaffAction | | |
| TASK-046 | Implement handle() with dependency checking | | |
| TASK-047 | Create AssignManagerAction | | |
| TASK-048 | Implement manager assignment with circular reference prevention | | |
| TASK-049 | Create TerminateStaffAction | | |
| TASK-050 | Implement termination with date recording and status update | | |

#### Events
- GOAL-011: Implement event system

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-090 | Create StaffCreatedEvent | | |
| TASK-091 | Create StaffUpdatedEvent | | |
| TASK-092 | Create StaffDeletedEvent | | |
| TASK-093 | Create StaffTerminatedEvent | | |
| TASK-094 | Create ManagerAssignedEvent | | |
| TASK-095 | Dispatch events from actions | | |
| TASK-096 | Register events in EventServiceProvider | | |

---

### Implementation Phase 4: API Layer

**Objective:** Build RESTful API with controllers, validation, resources, and authorization

#### API Layer
- GOAL-007: Build REST API endpoints

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-051 | Create StaffController in app/Http/Controllers/Api/V1/Backoffice/ | | |
| TASK-052 | Implement index() with filtering by office, department, status | | |
| TASK-053 | Implement store() using CreateStaffAction | | |
| TASK-054 | Implement show() returning single staff with relationships | | |
| TASK-055 | Implement update() using UpdateStaffAction | | |
| TASK-056 | Implement destroy() using DeleteStaffAction | | |
| TASK-057 | Implement assignManager() method | | |
| TASK-058 | Implement orgChart() method returning organizational chart | | |
| TASK-059 | Implement directReports() method | | |
| TASK-060 | Create routes in routes/api.php under /api/v1/backoffice/staff | | |
| TASK-061 | Apply auth:sanctum middleware | | |
| TASK-062 | Apply can:manage-staff middleware to modification routes | | |

#### Validation & Resources
- GOAL-008: Create validation and API resources

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-063 | Create StoreStaffRequest in app/Http/Requests/Backoffice/ | | |
| TASK-064 | Define validation rules for required fields | | |
| TASK-065 | Add validation for user_id, company_id, office_id, department_id | | |
| TASK-066 | Add custom rule preventing manager circular references | | |
| TASK-067 | Create UpdateStaffRequest | | |
| TASK-068 | Define validation rules for partial updates | | |
| TASK-069 | Create StaffResource in app/Http/Resources/Backoffice/ | | |
| TASK-070 | Define toArray() with staff attributes | | |
| TASK-071 | Include related data using whenLoaded() | | |
| TASK-072 | Add computed fields (full_name, is_manager) | | |
| TASK-073 | Create StaffCollection resource | | |

#### Authorization
- GOAL-009: Implement policies

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-074 | Create StaffPolicy in app/Domains/Backoffice/Policies/ | | |
| TASK-075 | Implement viewAny() checking manage-staff permission | | |
| TASK-076 | Implement view() with tenant and hierarchical checks | | |
| TASK-077 | Implement create() checking permission and company access | | |
| TASK-078 | Implement update() with ownership and hierarchy checks | | |
| TASK-079 | Implement delete() with dependency validation | | |
| TASK-080 | Register StaffPolicy in AuthServiceProvider | | |

---

### Implementation Phase 5: CLI Commands & Integration

**Objective:** Create CLI commands and set up cross-module integration

#### CLI Commands
- GOAL-010: Create CLI commands

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-081 | Create CreateStaffCommand in app/Console/Commands/Backoffice/ | | |
| TASK-082 | Implement handle() with interactive prompts | | |
| TASK-083 | Add options for staff details and assignments | | |
| TASK-084 | Create ListStaffCommand | | |
| TASK-085 | Implement handle() with table output | | |
| TASK-086 | Add filtering options | | |
| TASK-087 | Create OrgChartCommand | | |
| TASK-088 | Implement handle() displaying tree structure | | |
| TASK-089 | Register commands in Console/Kernel.php | | |

#### Integration
- GOAL-012: Set up cross-module integration

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-097 | Link Staff to User in authentication flow | | |
| TASK-098 | Create helper to get current user's staff record | | |
| TASK-099 | Document staff_id foreign key pattern for future modules | | |
| TASK-100 | Create staff assignment helpers for workflow routing | | |

---

### Implementation Phase 6: Testing & Verification

**Objective:** Comprehensive testing across unit, feature, and integration levels

See **Section 6: Testing** below for complete test specifications (14 tests total).

## 3. Alternatives Considered

**ALT-001: Staff as Extension of User vs Separate Model**
- **Selected**: Separate Staff model linked to User
- **Rationale**: Separation of concerns, not all users are staff

**ALT-002: Single vs Multiple Manager Assignment**
- **Selected**: Single direct manager for MVP
- **Future**: Matrix organization support

**ALT-003: Embedded vs Separate Employment History**
- **Selected**: Embedded in staff table for MVP
- **Future**: Separate employment history table

## 4. Dependencies

**Internal Dependencies:**
- **DEP-001**: Core.001 - Multi-Tenancy System
- **DEP-002**: Core.002 - Authentication & Authorization
- **DEP-003**: Core.003 - Audit Logging
- **DEP-004**: Backoffice.001 - Company Management
- **DEP-005**: Backoffice.002 - Office Management
- **DEP-006**: Backoffice.003 - Department Management

**External Package Dependencies:**
- **DEP-007**: `azaharizaman/laravel-backoffice: dev-main`
- **DEP-008**: `spatie/laravel-activitylog: ^4.0`
- **DEP-009**: `lorisleiva/laravel-actions: ^2.0`

## 5. Files

**New Files:**
- **FILE-001**: `app/Domains/Backoffice/Models/Staff.php`
- **FILE-002**: `app/Domains/Backoffice/Enums/StaffStatus.php`
- **FILE-003**: `app/Domains/Backoffice/Contracts/StaffRepositoryInterface.php`
- **FILE-004**: `app/Domains/Backoffice/Repositories/StaffRepository.php`
- **FILE-005**: `app/Domains/Backoffice/Services/OrganizationalChartService.php`
- **FILE-006**: `app/Domains/Backoffice/Actions/CreateStaffAction.php`
- **FILE-007**: `app/Domains/Backoffice/Actions/UpdateStaffAction.php`
- **FILE-008**: `app/Domains/Backoffice/Actions/DeleteStaffAction.php`
- **FILE-009**: `app/Domains/Backoffice/Actions/AssignManagerAction.php`
- **FILE-010**: `app/Domains/Backoffice/Actions/TerminateStaffAction.php`
- **FILE-011**: `app/Http/Controllers/Api/V1/Backoffice/StaffController.php`
- **FILE-012**: `app/Http/Requests/Backoffice/StoreStaffRequest.php`
- **FILE-013**: `app/Http/Requests/Backoffice/UpdateStaffRequest.php`
- **FILE-014**: `app/Http/Resources/Backoffice/StaffResource.php`
- **FILE-015**: `app/Domains/Backoffice/Policies/StaffPolicy.php`
- **FILE-016**: `app/Console/Commands/Backoffice/CreateStaffCommand.php`
- **FILE-017**: `app/Console/Commands/Backoffice/OrgChartCommand.php`
- **FILE-018**: `app/Domains/Backoffice/Events/StaffTerminatedEvent.php`
- **FILE-019**: `database/migrations/yyyy_mm_dd_add_tenant_to_staff_table.php`

**Modified Files:**
- **FILE-020**: `routes/api.php`
- **FILE-021**: `app/Providers/AuthServiceProvider.php`
- **FILE-022**: `app/Providers/BackofficeServiceProvider.php`

**Test Files:**
- **FILE-023**: `tests/Unit/Domains/Backoffice/Actions/CreateStaffActionTest.php`
- **FILE-024**: `tests/Unit/Domains/Backoffice/Services/OrganizationalChartServiceTest.php`
- **FILE-025**: `tests/Feature/Api/V1/Backoffice/StaffControllerTest.php`
- **FILE-026**: `database/factories/StaffFactory.php`

## 6. Testing

**Unit Tests:**
- **TEST-001**: CreateStaffAction creates staff successfully
- **TEST-002**: Staff validates required organizational assignments
- **TEST-003**: AssignManagerAction prevents circular references
- **TEST-004**: TerminateStaffAction updates status and date correctly
- **TEST-005**: OrganizationalChartService builds correct hierarchy

**Feature Tests:**
- **TEST-006**: POST /api/v1/backoffice/staff creates staff
- **TEST-007**: GET /api/v1/backoffice/staff lists staff with pagination
- **TEST-008**: API filters staff by office and department
- **TEST-009**: GET /api/v1/backoffice/staff/{id}/org-chart returns chart
- **TEST-010**: API enforces tenant isolation

**Integration Tests:**
- **TEST-011**: Staff creation links to user account
- **TEST-012**: Manager assignment updates relationships correctly
- **TEST-013**: Staff termination triggers event and logging
- **TEST-014**: Organizational chart query performance meets requirement

## 7. Risks & Assumptions

**Risks:**
- **RISK-001**: Complex organizational hierarchies affecting performance
  - **Mitigation**: Optimize queries, cache org charts
- **RISK-002**: Circular manager references
  - **Mitigation**: Validation logic in actions
- **RISK-003**: Staff-User synchronization issues
  - **Mitigation**: Use transactions, clear ownership rules

**Assumptions:**
- **ASSUMPTION-001**: Single manager per staff sufficient for MVP
- **ASSUMPTION-002**: Organizational hierarchy depth of 5 levels sufficient
- **ASSUMPTION-003**: Staff records linked to single company
- **ASSUMPTION-004**: Average tenant has fewer than 500 staff

## 8. Related Specifications

- [PRD-01: Multi-Tenancy](./PRD-01-infrastructure-multitenancy-1.md)
- [PRD-02: Authentication & Authorization](./PRD-02-infrastructure-auth-1.md)
- [PRD-06: Company Management](./PRD-06-feature-company-management-1.md)
- [PRD-07: Office Management](./PRD-07-feature-office-management-1.md)
- [PRD-08: Department Management](./PRD-08-feature-department-management-1.md)
- [PHASE-1-MVP.md](../docs/prd/PHASE-1-MVP.md)

---

**Version:** 1.0  
**Status:** Planned  
**Last Updated:** 2025-11-09
