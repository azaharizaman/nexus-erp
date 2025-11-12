---
plan: Vendor Management & Purchase Requisition Foundation
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, purchasing, vendor-management, purchase-requisition, approval-workflow, business-logic, procurement]
---

# PRD01-SUB16-PLAN01: Implement Vendor Management & Purchase Requisition Foundation

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan covers vendor master data management and purchase requisition workflow with approval routing. This plan establishes the foundation for the procurement module by managing vendor relationships and initiating the procure-to-pay cycle through requisition workflows.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-PO-001**: Support purchase requisition workflow with approvals before PO creation
- **FR-PO-003**: Manage vendor master data including contact info, payment terms, and ratings

### Business Rules
- **BR-PO-004**: Vendors with active POs cannot be deleted

### Data Requirements
- **DR-PO-003**: Record vendor evaluation data for performance analysis

### Integration Requirements
- **IR-PO-003**: Integrate with Backoffice for approval workflow enforcement

### Security Requirements
- **SR-PO-001**: Implement authorization matrix for purchase approval limits
- **SR-PO-002**: Log all PR/vendor modifications with user and timestamp

### Performance Requirements
- **PR-PO-002**: Vendor search must return results in < 100ms

### Event Requirements
- **EV-PO-001**: PurchaseOrderCreatedEvent (preparation for PLAN02)

### Constraints
- **CON-001**: Depends on SUB01 (Multi-Tenancy) for tenant isolation
- **CON-002**: Depends on SUB02 (Authentication) for user access control
- **CON-003**: Depends on SUB03 (Audit Logging) for activity tracking
- **CON-004**: Depends on SUB15 (Backoffice) for approval workflow

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Generate unique vendor codes automatically
- **GUD-004**: Log all vendor and requisition changes

### Patterns
- **PAT-001**: State machine pattern for requisition status transitions
- **PAT-002**: Observer pattern for automatic calculations
- **PAT-003**: Strategy pattern for approval routing rules
- **PAT-004**: Repository pattern with contracts for data access

## 2. Implementation Steps

### GOAL-001: Vendor Master Data Management

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-003 | Implement vendor master data with contact info, payment terms, and ratings | | |
| BR-PO-004 | Prevent deletion of vendors with active POs | | |
| DR-PO-003 | Store vendor evaluation data | | |
| PR-PO-002 | Vendor search < 100ms | | |
| SR-PO-002 | Log all vendor modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000001_create_vendors_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), vendor_code (VARCHAR 50 unique per tenant), vendor_name (VARCHAR 255), contact_person (VARCHAR 255 nullable), email (VARCHAR 255 nullable), phone (VARCHAR 50 nullable), address (TEXT nullable), city (VARCHAR 100 nullable), state (VARCHAR 100 nullable), postal_code (VARCHAR 20 nullable), country (VARCHAR 100 nullable), tax_id (VARCHAR 100 nullable), payment_terms (VARCHAR 100 nullable: NET30/NET60/NET90/COD), credit_limit (DECIMAL 15,2 nullable), rating (VARCHAR 20 nullable: excellent/good/average/poor), is_active (BOOLEAN default true), timestamps, soft deletes; indexes: tenant_id, vendor_code, is_active, rating; full-text search index on vendor_name for performance (PR-PO-002) | | |
| TASK-002 | Create enum `VendorPaymentTerms` with values: NET30, NET60, NET90, COD, PREPAID | | |
| TASK-003 | Create enum `VendorRating` with values: EXCELLENT, GOOD, AVERAGE, POOR, UNRATED | | |
| TASK-004 | Create model `Vendor.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: vendor_code, vendor_name, contact_person, email, phone, address, city, state, postal_code, country, tax_id, payment_terms, credit_limit, rating, is_active; casts: payment_terms → VendorPaymentTerms enum, rating → VendorRating enum, credit_limit → float, is_active → boolean; relationships: purchaseOrders (hasMany), performanceRecords (hasMany VendorPerformance), tenant (belongsTo); scopes: active(), byRating(VendorRating $rating), search(string $query); computed: has_active_purchase_orders (check active POs), can_be_deleted (!has_active_purchase_orders per BR-PO-004) | | |
| TASK-005 | Create factory `VendorFactory.php` with states: active(), inactive(), withGoodRating(), withPoorRating(), withCreditLimit(float $amount) | | |
| TASK-006 | Create contract `VendorRepositoryContract.php` with methods: findById(int $id): ?Vendor, findByCode(string $code, string $tenantId): ?Vendor, create(array $data): Vendor, update(Vendor $vendor, array $data): Vendor, delete(Vendor $vendor): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, search(string $query): Collection (optimized for PR-PO-002 < 100ms), getActive(): Collection, hasActivePurchaseOrders(Vendor $vendor): bool | | |
| TASK-007 | Implement `VendorRepository.php` with optimized search using full-text indexes or Laravel Scout integration; implement filters: is_active, rating, payment_terms, city, country; cache frequently accessed vendors with 15-minute TTL | | |
| TASK-008 | Create action `CreateVendorAction.php` using AsAction; inject VendorRepositoryContract, ActivityLoggerContract; validate vendor_name unique per tenant; generate vendor_code automatically (format: VND-{sequence}); validate email format; set default rating to UNRATED; log activity "Vendor created"; dispatch VendorCreatedEvent; return Vendor | | |
| TASK-009 | Create action `UpdateVendorAction.php`; validate vendor exists; validate email format; validate credit_limit >= 0; log activity "Vendor updated" with changes; dispatch VendorUpdatedEvent | | |
| TASK-010 | Create action `DeleteVendorAction.php`; check !has_active_purchase_orders (BR-PO-004); soft delete vendor; log activity "Vendor deleted"; dispatch VendorDeletedEvent | | |
| TASK-011 | Create action `SearchVendorsAction.php`; use repository search method; implement caching for common searches; measure and optimize for < 100ms (PR-PO-002); return Collection of Vendors | | |
| TASK-012 | Create event `VendorCreatedEvent` with properties: Vendor $vendor, User $createdBy | | |
| TASK-013 | Create event `VendorUpdatedEvent` with properties: Vendor $vendor, array $changes, User $updatedBy | | |
| TASK-014 | Create event `VendorDeletedEvent` with properties: Vendor $vendor, User $deletedBy | | |
| TASK-015 | Create observer `VendorObserver.php` with creating() to generate vendor_code; updating() to validate changes; deleting() to enforce BR-PO-004 (prevent deletion with active POs) | | |
| TASK-016 | Create policy `VendorPolicy.php` requiring 'manage-vendors' permission for CRUD operations; enforce tenant scope; validate active status for updates | | |
| TASK-017 | Create API controller `VendorController.php` with routes: index (GET /purchasing/vendors), store (POST /purchasing/vendors), show (GET /purchasing/vendors/{id}), update (PATCH /purchasing/vendors/{id}), destroy (DELETE /purchasing/vendors/{id}), search (GET /purchasing/vendors/search?q={query}); authorize actions; inject VendorRepositoryContract | | |
| TASK-018 | Create form request `StoreVendorRequest.php` with validation: vendor_name (required, string, max:255, unique per tenant), email (nullable, email), phone (nullable, string, max:50), payment_terms (required, in:VendorPaymentTerms values), credit_limit (nullable, numeric, min:0), address, city, state, postal_code, country (all nullable, string) | | |
| TASK-019 | Create form request `UpdateVendorRequest.php` extending StoreVendorRequest; make vendor_name and payment_terms not required for partial updates | | |
| TASK-020 | Create API resource `VendorResource.php` with fields: id, vendor_code, vendor_name, contact_person, email, phone, address, city, state, postal_code, country, tax_id, payment_terms, credit_limit, rating, is_active, has_active_purchase_orders, can_be_deleted, created_at | | |
| TASK-021 | Write unit tests for Vendor model: test vendor_code generation, test can_be_deleted logic (BR-PO-004), test search scope, test rating enum values | | |
| TASK-022 | Write unit tests for VendorRepository: test search performance < 100ms (PR-PO-002), test filtering capabilities | | |
| TASK-023 | Write feature tests for VendorController: test create vendor via API, test cannot delete vendor with active POs (BR-PO-004), test search vendors, test authorization checks | | |

### GOAL-002: Purchase Requisition Foundation & Creation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-001 | Implement purchase requisition creation and management | | |
| SR-PO-002 | Log all PR modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-024 | Create migration `2025_01_01_000002_create_purchase_requisitions_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), requisition_number (VARCHAR 100 unique per tenant), requisition_date (DATE), requested_by (BIGINT FK users), department_id (BIGINT nullable FK organizations from SUB15), required_date (DATE nullable), status (VARCHAR 20 default 'draft': draft/pending/approved/rejected/converted), approved_by (BIGINT nullable FK users), approved_at (TIMESTAMP nullable), rejection_reason (TEXT nullable), notes (TEXT nullable), timestamps; indexes: tenant_id, requisition_number, requested_by, status, requisition_date, department_id | | |
| TASK-025 | Create migration `2025_01_01_000003_create_purchase_requisition_lines_table.php` with columns: id (BIGSERIAL), requisition_id (BIGINT FK purchase_requisitions cascade), line_number (INT), item_id (BIGINT nullable FK inventory_items), item_description (TEXT), quantity (DECIMAL 15,4), uom_id (BIGINT FK uoms), estimated_price (DECIMAL 15,2 nullable), line_total (DECIMAL 15,2 nullable), required_date (DATE nullable), justification (TEXT nullable), timestamps; indexes: requisition_id, item_id, uom_id | | |
| TASK-026 | Create enum `PurchaseRequisitionStatus` with values: DRAFT, PENDING, APPROVED, REJECTED, CONVERTED; define allowed transitions: DRAFT→PENDING, PENDING→APPROVED, PENDING→REJECTED, APPROVED→CONVERTED, REJECTED→DRAFT | | |
| TASK-027 | Create model `PurchaseRequisition.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: requisition_number, requisition_date, requested_by, department_id, required_date, notes; casts: requisition_date → date, required_date → date, status → PurchaseRequisitionStatus enum, approved_at → datetime; relationships: requestedBy (belongsTo User), approvedBy (belongsTo User), department (belongsTo Organization), lines (hasMany PurchaseRequisitionLine), purchaseOrders (hasMany); scopes: byStatus(PurchaseRequisitionStatus $status), pending(), approved(), byDepartment(int $departmentId), byRequester(int $userId); computed: total_estimated_amount (sum of line totals), can_be_modified (status === DRAFT), requires_approval (always true per FR-PO-001) | | |
| TASK-028 | Create model `PurchaseRequisitionLine.php` with fillable: line_number, item_id, item_description, quantity, uom_id, estimated_price, required_date, justification; casts: quantity → float, estimated_price → float, line_total → float, required_date → date; relationships: requisition (belongsTo), item (belongsTo InventoryItem), uom (belongsTo); computed: calculated_line_total (quantity * estimated_price) | | |
| TASK-029 | Create factory `PurchaseRequisitionFactory.php` with states: draft(), pending(), approved(), rejected(), converted(), withLines(int $count = 3), withDepartment() | | |
| TASK-030 | Create factory `PurchaseRequisitionLineFactory.php` with states: withItem(), withEstimatedPrice() | | |
| TASK-031 | Create contract `PurchaseRequisitionRepositoryContract.php` with methods: findById(int $id): ?PurchaseRequisition, findByNumber(string $number, string $tenantId): ?PurchaseRequisition, create(array $data): PurchaseRequisition, update(PurchaseRequisition $pr, array $data): PurchaseRequisition, delete(PurchaseRequisition $pr): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getPending(): Collection, getApproved(): Collection, getByRequester(int $userId): Collection | | |
| TASK-032 | Implement `PurchaseRequisitionRepository.php` with eager loading for requestedBy, lines, items; implement filters: status, requested_by, department_id, date_range, requires_approval | | |
| TASK-033 | Create service `RequisitionCalculationService.php` with methods: calculateLineTotal(array $lineData): float, calculateTotalEstimatedAmount(PurchaseRequisition $pr): float, recalculateRequisition(PurchaseRequisition $pr): void (updates all line totals and PR total) | | |
| TASK-034 | Create action `CreatePurchaseRequisitionAction.php` using AsAction; inject PurchaseRequisitionRepositoryContract, RequisitionCalculationService, ActivityLoggerContract; validate requested_by user exists; generate requisition_number (format: PR-{sequence}); create requisition header; create requisition lines; calculate line totals; set status to DRAFT; log activity "Purchase requisition created"; dispatch PurchaseRequisitionCreatedEvent; return PurchaseRequisition | | |
| TASK-035 | Create action `UpdatePurchaseRequisitionAction.php`; validate status is DRAFT (only draft can be modified); validate requested_by; recalculate totals via RequisitionCalculationService; log activity "Purchase requisition updated" with changes; dispatch PurchaseRequisitionUpdatedEvent | | |
| TASK-036 | Create action `DeletePurchaseRequisitionAction.php`; validate status is DRAFT or REJECTED (cannot delete approved/converted); soft delete requisition; log activity "Purchase requisition deleted"; dispatch PurchaseRequisitionDeletedEvent | | |
| TASK-037 | Create event `PurchaseRequisitionCreatedEvent` with properties: PurchaseRequisition $requisition, User $createdBy | | |
| TASK-038 | Create event `PurchaseRequisitionUpdatedEvent` with properties: PurchaseRequisition $requisition, array $changes, User $updatedBy | | |
| TASK-039 | Create event `PurchaseRequisitionDeletedEvent` with properties: PurchaseRequisition $requisition, User $deletedBy | | |
| TASK-040 | Create observer `PurchaseRequisitionObserver.php` with creating() to generate requisition_number; updating() to recalculate totals when lines change; deleting() to prevent deletion of approved/converted requisitions | | |
| TASK-041 | Create policy `PurchaseRequisitionPolicy.php` requiring 'create-requisitions' permission for creation, 'manage-requisitions' for updates; users can view/edit own requisitions; department managers can view department requisitions; enforce tenant scope | | |
| TASK-042 | Create API controller `PurchaseRequisitionController.php` with routes: index (GET /purchasing/requisitions), store (POST /purchasing/requisitions), show (GET /purchasing/requisitions/{id}), update (PATCH /purchasing/requisitions/{id}), destroy (DELETE /purchasing/requisitions/{id}); authorize actions; inject PurchaseRequisitionRepositoryContract | | |
| TASK-043 | Create form request `StorePurchaseRequisitionRequest.php` with validation: requisition_date (required, date), department_id (nullable, exists:organizations), required_date (nullable, date, after_or_equal:requisition_date), lines (required, array, min:1), lines.*.item_id (nullable, exists:inventory_items), lines.*.item_description (required, string), lines.*.quantity (required, numeric, min:0.0001), lines.*.uom_id (required, exists:uoms), lines.*.estimated_price (nullable, numeric, min:0), notes (nullable, string) | | |
| TASK-044 | Create form request `UpdatePurchaseRequisitionRequest.php` extending StorePurchaseRequisitionRequest; add status validation (must be DRAFT) | | |
| TASK-045 | Create API resource `PurchaseRequisitionResource.php` with fields: id, requisition_number, requisition_date, requested_by (nested UserResource), department (nested), lines (nested collection), status, total_estimated_amount, required_date, can_be_modified, requires_approval, approved_by, approved_at, rejection_reason, created_at | | |
| TASK-046 | Create API resource `PurchaseRequisitionLineResource.php` with fields: line_number, item (nested), item_description, quantity, uom, estimated_price, line_total, required_date, justification | | |

### GOAL-003: Purchase Requisition Approval Workflow

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-001 | Implement approval workflow before PO creation | | |
| IR-PO-003 | Integrate with Backoffice for approval enforcement | | |
| SR-PO-001 | Implement authorization matrix for approval limits | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-047 | Create migration `2025_01_01_000004_create_requisition_approvals_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), requisition_id (BIGINT FK purchase_requisitions), approval_level (INT), approver_id (BIGINT FK users), amount_threshold (DECIMAL 15,2 nullable), status (VARCHAR 20: pending/approved/rejected), comments (TEXT nullable), approved_at (TIMESTAMP nullable), timestamps; indexes: tenant_id, requisition_id, approver_id, status | | |
| TASK-048 | Create enum `ApprovalStatus` with values: PENDING, APPROVED, REJECTED | | |
| TASK-049 | Create model `RequisitionApproval.php` with traits: BelongsToTenant; fillable: requisition_id, approval_level, approver_id, amount_threshold, status, comments; casts: status → ApprovalStatus enum, amount_threshold → float, approved_at → datetime; relationships: requisition (belongsTo), approver (belongsTo User); scopes: pending(), approved(), forRequisition(int $reqId) | | |
| TASK-050 | Create factory `RequisitionApprovalFactory.php` with states: pending(), approved(), rejected() | | |
| TASK-051 | Create service `RequisitionApprovalService.php` with methods: requiresApproval(PurchaseRequisition $pr): bool (always true per FR-PO-001), getApprovers(PurchaseRequisition $pr): Collection (get approvers from approval matrix in SUB15), createApprovalRequest(PurchaseRequisition $pr): void (create approval records), canApprove(User $user, PurchaseRequisition $pr): bool (check approval authority), isPendingApproval(PurchaseRequisition $pr): bool, isFullyApproved(PurchaseRequisition $pr): bool | | |
| TASK-052 | Create action `SubmitRequisitionForApprovalAction.php`; validate requisition status is DRAFT; validate requisition has lines; validate line items and quantities valid; create approval requests via RequisitionApprovalService; update status to PENDING; log activity "Requisition submitted for approval"; dispatch RequisitionSubmittedForApprovalEvent; return PurchaseRequisition | | |
| TASK-053 | Create action `ApproveRequisitionAction.php`; validate user is assigned approver; validate approval status PENDING; validate requisition still in PENDING status; update approval status to APPROVED with timestamp; if all approvals complete: update requisition status to APPROVED; log activity "Requisition approved by {user}"; dispatch RequisitionApprovedEvent | | |
| TASK-054 | Create action `RejectRequisitionAction.php`; validate user is approver; validate rejection reason provided; update approval status to REJECTED with comments; update requisition status to REJECTED; log activity "Requisition rejected by {user}: {reason}"; dispatch RequisitionRejectedEvent; notify requester | | |
| TASK-055 | Create event `RequisitionSubmittedForApprovalEvent` with properties: PurchaseRequisition $requisition, Collection $approvers | | |
| TASK-056 | Create event `RequisitionApprovedEvent` with properties: PurchaseRequisition $requisition, RequisitionApproval $approval, User $approver | | |
| TASK-057 | Create event `RequisitionRejectedEvent` with properties: PurchaseRequisition $requisition, RequisitionApproval $approval, User $rejector, string $reason | | |
| TASK-058 | Create event `RequisitionFullyApprovedEvent` with properties: PurchaseRequisition $requisition, Collection $approvals | | |
| TASK-059 | Create listener `NotifyApproversListener.php` listening to RequisitionSubmittedForApprovalEvent; send notification to all approvers; create activity log entries | | |
| TASK-060 | Create listener `NotifyRequesterOnApprovalListener.php` listening to RequisitionApprovedEvent and RequisitionRejectedEvent; send notification to requester with approval decision | | |
| TASK-061 | Create API controller routes in PurchaseRequisitionController: submitForApproval (POST /requisitions/{id}/submit), approve (POST /requisitions/{id}/approve), reject (POST /requisitions/{id}/reject) | | |
| TASK-062 | Create form request `ApproveRequisitionRequest.php` with validation: comments (nullable, string, max:500) | | |
| TASK-063 | Create form request `RejectRequisitionRequest.php` with validation: reason (required, string, max:500) | | |
| TASK-064 | Create API resource `RequisitionApprovalResource.php` with fields: approval_level, approver (nested UserResource), amount_threshold, status, comments, approved_at | | |
| TASK-065 | Write unit tests for RequisitionApprovalService: test requiresApproval always returns true (FR-PO-001), test getApprovers integration with SUB15, test isPendingApproval logic | | |
| TASK-066 | Write feature tests for approval workflow: test submit→approve cycle, test multi-level approval, test rejection returns to draft, test cannot approve without permission (SR-PO-001) | | |

### GOAL-004: Service Provider & Infrastructure

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| CON-001 to CON-004 | Set up package infrastructure and dependencies | | |
| SR-PO-002 | Enable audit logging | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-067 | Create `PurchasingServiceProvider.php` with register() binding contracts: VendorRepositoryContract → VendorRepository, PurchaseRequisitionRepositoryContract → PurchaseRequisitionRepository | | |
| TASK-068 | In PurchasingServiceProvider boot() method: register routes (loadRoutesFrom), register migrations (loadMigrationsFrom), register observers (Vendor::observe, PurchaseRequisition::observe), publish config, register event listeners | | |
| TASK-069 | Create configuration file `config/purchasing.php` with settings: vendor_code_prefix (default 'VND'), requisition_number_prefix (default 'PR'), require_approval_for_all_requisitions (default true), vendor_search_cache_ttl (default 900 seconds = 15 minutes), default_payment_terms (default 'NET30') | | |
| TASK-070 | Create base service class `BasePurchasingService.php` with common methods: validateTenantScope(Model $model): void, logActivity(string $description, Model $subject, ?Model $causer = null): void, getCacheKey(string $prefix, ...$params): string | | |
| TASK-071 | Create middleware `ValidatePurchasingAccess.php` to check user has 'access-purchasing' permission; apply to all purchasing routes | | |
| TASK-072 | Register API routes in `routes/api.php` with prefix '/purchasing', middleware: ['auth:sanctum', 'tenant', 'validate-purchasing-access']; group routes for vendors, requisitions | | |
| TASK-073 | Create seeder `PurchasingSeeder.php` for development environment with sample vendors (10 vendors), sample requisitions (5 requisitions), sample approval workflows | | |
| TASK-074 | Update package `composer.json` with dependencies: azaharizaman/erp-core, azaharizaman/laravel-backoffice, azaharizaman/erp-inventory-management, lorisleiva/laravel-actions; autoload PSR-4 namespace | | |
| TASK-075 | Create package README.md with installation instructions, configuration options, usage examples, API documentation links | | |

### GOAL-005: Testing, Documentation & Deployment

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-PO-002 | Vendor search < 100ms | | |
| SR-PO-001, SR-PO-002 | Security and audit requirements | | |
| All FRs | Complete test coverage for vendor and requisition management | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-076 | Write comprehensive unit tests for all models: test Vendor model can_be_deleted logic (BR-PO-004), test PurchaseRequisition status transitions, test RequisitionApproval workflow states | | |
| TASK-077 | Write comprehensive unit tests for all services: test RequisitionCalculationService total calculations, test RequisitionApprovalService approval routing, test vendor search performance | | |
| TASK-078 | Write comprehensive unit tests for all actions: test CreateVendorAction with validation, test CreatePurchaseRequisitionAction with lines, test approval workflow actions | | |
| TASK-079 | Write feature tests for complete requisition workflows: test requisition creation→submission→approval→converted; verify status changes, verify events dispatched | | |
| TASK-080 | Write integration tests: test approval workflow integration with SUB15 Backoffice (mocked), test audit logging integration with SUB03 (verify activity logs created) | | |
| TASK-081 | Write performance test: create 1000 vendors, test search completes in < 100ms (PR-PO-002); test with various search terms; measure and optimize | | |
| TASK-082 | Write security tests: test users can only view own requisitions (unless manager), test approval authorization enforced (SR-PO-001), test tenant isolation for vendors | | |
| TASK-083 | Write acceptance tests: test vendor creation functional, test vendor deletion blocked with active POs (BR-PO-004), test requisition approval workflow complete | | |
| TASK-084 | Set up Pest configuration for purchasing tests; configure database transactions, tenant seeding, user factory | | |
| TASK-085 | Achieve minimum 80% code coverage for vendor and requisition modules; run `./vendor/bin/pest --coverage`; add tests for uncovered paths | | |
| TASK-086 | Create API documentation using OpenAPI 3.0 spec: document all vendor endpoints with request/response examples, document requisition endpoints, document approval workflow | | |
| TASK-087 | Create user guide: vendor management procedures, requisition creation workflow, approval process documentation, common troubleshooting scenarios | | |
| TASK-088 | Create technical documentation: approval routing architecture, integration with SUB15 Backoffice, vendor search optimization techniques | | |
| TASK-089 | Update package README with complete feature list: vendor management, purchase requisition workflow, approval routing, search capabilities | | |
| TASK-090 | Validate all acceptance criteria: vendor CRUD functional, requisition workflow operational, approval routing works correctly, search performance meets requirements (PR-PO-002) | | |
| TASK-091 | Conduct code review: verify all business rules implemented (BR-PO-004), verify performance requirements met (PR-PO-002), verify security controls enforced (SR-PO-001, SR-PO-002) | | |
| TASK-092 | Run full test suite for vendor and requisition modules; verify all tests pass; fix flaky tests; ensure consistent results | | |
| TASK-093 | Deploy to staging environment; test complete requisition workflow end-to-end; test approval routing with various user roles; verify performance with production-like data volume | | |

## 3. Alternatives

- **ALT-001**: Auto-approve requisitions below threshold - rejected to maintain audit trail per FR-PO-001 (all requisitions require approval)
- **ALT-002**: Single-level approval for all requisitions - rejected; multi-level approval implemented for flexibility (will be used in PLAN02)
- **ALT-003**: Manual vendor code entry - rejected; auto-generation ensures uniqueness and consistency
- **ALT-004**: Store approval matrix in purchasing module - rejected; use SUB15 Backoffice for centralized approval management
- **ALT-005**: Real-time vendor search without caching - rejected; caching implemented for PR-PO-002 performance requirement

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model and isolation
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, permission system
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking
- **DEP-004**: SUB15 (Backoffice) - Organization model (for departments), approval workflow matrix
- **DEP-005**: SUB14 (Inventory Management) - InventoryItem model (for requisition lines)
- **DEP-006**: SUB06 (UOM) - UOM model for unit conversions
- **DEP-007**: SUB04 (Serial Numbering) - Vendor code and requisition number generation

### Optional Dependencies
- **DEP-008**: SUB22 (Notifications) - Email notifications for approval requests

### Package Dependencies
- **DEP-009**: lorisleiva/laravel-actions ^2.0 - Action and job pattern
- **DEP-010**: Laravel Queue system - Async notification processing
- **DEP-011**: Laravel Scout (if using for vendor search) - Full-text search optimization

## 5. Files

### Models & Enums
- `packages/purchasing/src/Models/Vendor.php` - Vendor master data model
- `packages/purchasing/src/Models/PurchaseRequisition.php` - Purchase requisition header model
- `packages/purchasing/src/Models/PurchaseRequisitionLine.php` - Requisition line items model
- `packages/purchasing/src/Models/RequisitionApproval.php` - Approval workflow model
- `packages/purchasing/src/Enums/VendorPaymentTerms.php` - Payment terms enumeration
- `packages/purchasing/src/Enums/VendorRating.php` - Vendor rating enumeration
- `packages/purchasing/src/Enums/PurchaseRequisitionStatus.php` - Requisition status enumeration
- `packages/purchasing/src/Enums/ApprovalStatus.php` - Approval status enumeration

### Repositories & Contracts
- `packages/purchasing/src/Contracts/VendorRepositoryContract.php` - Vendor repository interface
- `packages/purchasing/src/Repositories/VendorRepository.php` - Vendor repository implementation
- `packages/purchasing/src/Contracts/PurchaseRequisitionRepositoryContract.php` - Requisition repository interface
- `packages/purchasing/src/Repositories/PurchaseRequisitionRepository.php` - Requisition repository implementation

### Services
- `packages/purchasing/src/Services/RequisitionCalculationService.php` - Requisition total calculations
- `packages/purchasing/src/Services/RequisitionApprovalService.php` - Approval workflow logic
- `packages/purchasing/src/Services/BasePurchasingService.php` - Base service class

### Actions
- `packages/purchasing/src/Actions/CreateVendorAction.php` - Create vendor
- `packages/purchasing/src/Actions/UpdateVendorAction.php` - Update vendor
- `packages/purchasing/src/Actions/DeleteVendorAction.php` - Delete vendor
- `packages/purchasing/src/Actions/SearchVendorsAction.php` - Search vendors
- `packages/purchasing/src/Actions/CreatePurchaseRequisitionAction.php` - Create requisition
- `packages/purchasing/src/Actions/UpdatePurchaseRequisitionAction.php` - Update requisition
- `packages/purchasing/src/Actions/DeletePurchaseRequisitionAction.php` - Delete requisition
- `packages/purchasing/src/Actions/SubmitRequisitionForApprovalAction.php` - Submit for approval
- `packages/purchasing/src/Actions/ApproveRequisitionAction.php` - Approve requisition
- `packages/purchasing/src/Actions/RejectRequisitionAction.php` - Reject requisition

### Controllers & Requests
- `packages/purchasing/src/Http/Controllers/VendorController.php` - Vendor API controller
- `packages/purchasing/src/Http/Controllers/PurchaseRequisitionController.php` - Requisition API controller
- `packages/purchasing/src/Http/Requests/StoreVendorRequest.php` - Vendor validation
- `packages/purchasing/src/Http/Requests/UpdateVendorRequest.php` - Vendor update validation
- `packages/purchasing/src/Http/Requests/StorePurchaseRequisitionRequest.php` - Requisition validation
- `packages/purchasing/src/Http/Requests/UpdatePurchaseRequisitionRequest.php` - Requisition update validation
- `packages/purchasing/src/Http/Requests/ApproveRequisitionRequest.php` - Approval validation
- `packages/purchasing/src/Http/Requests/RejectRequisitionRequest.php` - Rejection validation

### Resources
- `packages/purchasing/src/Http/Resources/VendorResource.php` - Vendor transformation
- `packages/purchasing/src/Http/Resources/PurchaseRequisitionResource.php` - Requisition transformation
- `packages/purchasing/src/Http/Resources/PurchaseRequisitionLineResource.php` - Line transformation
- `packages/purchasing/src/Http/Resources/RequisitionApprovalResource.php` - Approval transformation

### Events & Listeners
- `packages/purchasing/src/Events/VendorCreatedEvent.php`
- `packages/purchasing/src/Events/VendorUpdatedEvent.php`
- `packages/purchasing/src/Events/VendorDeletedEvent.php`
- `packages/purchasing/src/Events/PurchaseRequisitionCreatedEvent.php`
- `packages/purchasing/src/Events/PurchaseRequisitionUpdatedEvent.php`
- `packages/purchasing/src/Events/RequisitionSubmittedForApprovalEvent.php`
- `packages/purchasing/src/Events/RequisitionApprovedEvent.php`
- `packages/purchasing/src/Events/RequisitionRejectedEvent.php`
- `packages/purchasing/src/Events/RequisitionFullyApprovedEvent.php`
- `packages/purchasing/src/Listeners/NotifyApproversListener.php`
- `packages/purchasing/src/Listeners/NotifyRequesterOnApprovalListener.php`

### Observers, Policies & Middleware
- `packages/purchasing/src/Observers/VendorObserver.php` - Vendor model observer
- `packages/purchasing/src/Observers/PurchaseRequisitionObserver.php` - Requisition observer
- `packages/purchasing/src/Policies/VendorPolicy.php` - Vendor authorization
- `packages/purchasing/src/Policies/PurchaseRequisitionPolicy.php` - Requisition authorization
- `packages/purchasing/src/Http/Middleware/ValidatePurchasingAccess.php` - Access control

### Database
- `packages/purchasing/database/migrations/2025_01_01_000001_create_vendors_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000002_create_purchase_requisitions_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000003_create_purchase_requisition_lines_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000004_create_requisition_approvals_table.php`
- `packages/purchasing/database/factories/VendorFactory.php`
- `packages/purchasing/database/factories/PurchaseRequisitionFactory.php`
- `packages/purchasing/database/factories/RequisitionApprovalFactory.php`
- `packages/purchasing/database/seeders/PurchasingSeeder.php`

### Configuration & Routes
- `packages/purchasing/config/purchasing.php` - Package configuration
- `packages/purchasing/routes/api.php` - API route definitions
- `packages/purchasing/src/PurchasingServiceProvider.php` - Service provider

### Tests (Total: 93 tasks with testing components)
- `packages/purchasing/tests/Unit/Models/VendorTest.php`
- `packages/purchasing/tests/Unit/Models/PurchaseRequisitionTest.php`
- `packages/purchasing/tests/Unit/Services/RequisitionApprovalServiceTest.php`
- `packages/purchasing/tests/Feature/VendorManagementTest.php`
- `packages/purchasing/tests/Feature/PurchaseRequisitionWorkflowTest.php`
- `packages/purchasing/tests/Feature/RequisitionApprovalTest.php`
- `packages/purchasing/tests/Integration/BackofficeIntegrationTest.php`
- `packages/purchasing/tests/Performance/VendorSearchPerformanceTest.php`

## 6. Testing

### Unit Tests (30 tests)
- **TEST-001**: Vendor model can_be_deleted logic (BR-PO-004)
- **TEST-002**: Vendor rating enum values
- **TEST-003**: PurchaseRequisition status transitions
- **TEST-004**: PurchaseRequisitionLine total calculation
- **TEST-005**: RequisitionApproval workflow states
- **TEST-006**: VendorRepository search optimization
- **TEST-007**: RequisitionCalculationService total calculations
- **TEST-008**: RequisitionApprovalService approval routing logic
- **TEST-009**: All action classes with mocked dependencies

### Feature Tests (35 tests)
- **TEST-010**: Create vendor via API
- **TEST-011**: Cannot delete vendor with active POs (BR-PO-004)
- **TEST-012**: Search vendors with various filters
- **TEST-013**: Vendor authorization checks
- **TEST-014**: Create requisition with lines
- **TEST-015**: Cannot modify non-draft requisition
- **TEST-016**: Delete requisition only if draft/rejected
- **TEST-017**: Submit requisition for approval workflow
- **TEST-018**: Multi-level approval process
- **TEST-019**: Reject requisition with reason
- **TEST-020**: Requisition approval authorization (SR-PO-001)
- **TEST-021**: Complete requisition lifecycle (create→submit→approve→converted)

### Integration Tests (10 tests)
- **TEST-022**: Approval workflow integration with SUB15 Backoffice (mocked)
- **TEST-023**: Audit logging integration with SUB03
- **TEST-024**: Requisition line item integration with SUB14 Inventory

### Performance Tests (3 tests)
- **TEST-025**: Vendor search < 100ms with 1000 vendors (PR-PO-002)
- **TEST-026**: Vendor search with caching effectiveness
- **TEST-027**: Requisition creation with multiple lines

### Security Tests (10 tests)
- **TEST-028**: Users can only view own requisitions (unless manager)
- **TEST-029**: Approval authorization enforced (SR-PO-001)
- **TEST-030**: Tenant isolation for vendors
- **TEST-031**: Tenant isolation for requisitions
- **TEST-032**: Cannot approve own requisition

### Acceptance Tests (5 tests)
- **TEST-033**: Vendor creation functional
- **TEST-034**: Vendor deletion blocked correctly (BR-PO-004)
- **TEST-035**: Requisition approval workflow complete
- **TEST-036**: Vendor search meets performance (PR-PO-002)
- **TEST-037**: Audit trail complete (SR-PO-002)

**Total Test Coverage:** 93 tests (30 unit + 35 feature + 10 integration + 3 performance + 10 security + 5 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Vendor search performance may degrade with large datasets - Mitigation: implement full-text indexes, caching, and Laravel Scout integration
- **RISK-002**: Approval routing complexity may cause workflow delays - Mitigation: clear approval matrix configuration in SUB15, notification system
- **RISK-003**: Integration with SUB15 Backoffice approval matrix may have implementation gaps - Mitigation: define clear contract, provide fallback to simple approval if SUB15 not available
- **RISK-004**: Requisition approval bottlenecks may delay procurement - Mitigation: implement delegation and escalation features (future)
- **RISK-005**: Vendor data quality issues may affect PO creation - Mitigation: strict validation on vendor creation/updates

### Assumptions
- **ASSUMPTION-001**: SUB15 Backoffice provides approval workflow matrix API
- **ASSUMPTION-002**: All requisitions require approval (no auto-approval for low amounts in PLAN01)
- **ASSUMPTION-003**: Vendor master data is maintained by dedicated procurement team
- **ASSUMPTION-004**: Department assignment for requisitions comes from SUB15 Organizations
- **ASSUMPTION-005**: Vendor search using simple database LIKE queries is sufficient initially; Scout integration as enhancement
- **ASSUMPTION-006**: Single-tenant vendor master (vendors not shared across tenants)

## 8. KIV for Future Implementations

- **KIV-001**: Vendor onboarding portal (self-registration)
- **KIV-002**: Vendor document management (certificates, insurance)
- **KIV-003**: Automatic vendor code generation with custom formats per tenant
- **KIV-004**: Approval delegation and escalation workflows
- **KIV-005**: Requisition templates for recurring purchases
- **KIV-006**: Budget checking integration with financial module
- **KIV-007**: Vendor blacklist/whitelist management
- **KIV-008**: Multi-currency vendor support
- **KIV-009**: Vendor portal for viewing POs and updating delivery status
- **KIV-010**: Advanced vendor analytics and spend reporting
- **KIV-011**: Requisition merge/split functionality
- **KIV-012**: Email-to-requisition functionality (create PR from email)

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB16-PURCHASING.md](../prd/prd-01/PRD01-SUB16-PURCHASING.md)
- **Related Plans**:
  - PRD01-SUB16-PLAN02 (Purchase Order Management & Approval) - Next plan, converts requisitions to POs
  - PRD01-SUB16-PLAN03 (Goods Receipt & Three-Way Matching) - Completes procure-to-pay cycle
  - PRD01-SUB16-PLAN04 (Vendor Performance & Reporting) - Analytics and metrics
- **Integration Documentation**:
  - SUB15 (Backoffice) - Approval workflow integration
  - SUB14 (Inventory Management) - Item master data
  - SUB11 (Accounts Payable) - Three-way matching (PLAN03)
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
