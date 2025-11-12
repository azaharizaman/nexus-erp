---
plan: Purchase Order Management & Multi-Level Approval
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, purchasing, purchase-order, approval-workflow, workflow-engine, threshold-based-approval, business-logic, procurement]
---

# PRD01-SUB16-PLAN02: Implement Purchase Order Management & Multi-Level Approval

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan covers purchase order creation, lifecycle management, and multi-level approval workflows with threshold-based routing. This plan enables the core procurement operation by converting approved requisitions into purchase orders and enforcing configurable approval hierarchies based on monetary thresholds.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-PO-002**: Support PO creation with line items, pricing, delivery dates, payment terms
- **FR-PO-004**: Implement multi-level approval based on monetary thresholds

### Business Rules
- **BR-PO-001**: Approval thresholds: < $5,000 (supervisor), < $50,000 (manager), >= $50,000 (director)
- **BR-PO-002**: Closed POs cannot be modified

### Data Requirements
- **DR-PO-001**: Maintain complete PO history with status changes and modifications

### Integration Requirements
- **IR-PO-003**: Integrate with Backoffice for approval workflow enforcement

### Security Requirements
- **SR-PO-001**: Implement authorization matrix for purchase approval limits
- **SR-PO-002**: Log all PO modifications with user and timestamp

### Performance Requirements
- **PR-PO-001**: PO creation must complete in < 1 second

### Scalability Requirements
- **SCR-PO-001**: Support 100,000+ POs per year (2,000+ per week)

### Architecture Requirements
- **ARCH-PO-001**: Use flexible workflow engine for approval routing (not hard-coded rules)

### Event Requirements
- **EV-PO-001**: Emit PurchaseOrderCreatedEvent for downstream processing
- **EV-PO-002**: Emit PurchaseOrderApprovedEvent for inventory and AP integration

### Constraints
- **CON-001**: Depends on SUB01 (Multi-Tenancy) for tenant isolation
- **CON-002**: Depends on SUB02 (Authentication) for user access control
- **CON-003**: Depends on SUB03 (Audit Logging) for activity tracking
- **CON-004**: Depends on SUB15 (Backoffice) for workflow engine and approval matrix
- **CON-005**: Depends on PLAN01 for vendor and requisition data

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Generate unique PO numbers automatically
- **GUD-004**: Log all PO status changes and modifications
- **GUD-005**: Enforce immutability for closed POs (BR-PO-002)

### Patterns
- **PAT-001**: State machine pattern for PO status transitions
- **PAT-002**: Observer pattern for automatic calculations
- **PAT-003**: Strategy pattern for approval routing based on thresholds
- **PAT-004**: Repository pattern with contracts for data access
- **PAT-005**: Workflow engine pattern for flexible approval configuration

## 2. Implementation Steps

### GOAL-001: Purchase Order Foundation & Creation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-002 | Implement PO creation with line items, pricing, and delivery dates | | |
| DR-PO-001 | Maintain complete PO history | | |
| PR-PO-001 | PO creation < 1 second | | |
| SCR-PO-001 | Support 100,000+ POs per year | | |
| SR-PO-002 | Log all PO modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000005_create_purchase_orders_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), po_number (VARCHAR 100 unique per tenant), po_date (DATE), vendor_id (BIGINT FK vendors), requisition_id (BIGINT nullable FK purchase_requisitions), buyer_id (BIGINT FK users), delivery_address (TEXT nullable), delivery_date (DATE nullable), payment_terms (VARCHAR 100 nullable), status (VARCHAR 20 default 'draft': draft/pending/approved/partial/fulfilled/closed), approval_status (VARCHAR 20 nullable: pending/approved/rejected), total_amount (DECIMAL 15,2 default 0), approved_by (BIGINT nullable FK users), approved_at (TIMESTAMP nullable), closed_by (BIGINT nullable FK users), closed_at (TIMESTAMP nullable), notes (TEXT nullable), timestamps, soft deletes; indexes: tenant_id, po_number, vendor_id, buyer_id, status, po_date, requisition_id | | |
| TASK-002 | Create migration `2025_01_01_000006_create_purchase_order_lines_table.php` with columns: id (BIGSERIAL), po_id (BIGINT FK purchase_orders cascade), line_number (INT), item_id (BIGINT nullable FK inventory_items), item_description (TEXT), quantity (DECIMAL 15,4), uom_id (BIGINT FK uoms), unit_price (DECIMAL 15,2), line_total (DECIMAL 15,2), received_quantity (DECIMAL 15,4 default 0), delivery_date (DATE nullable), notes (TEXT nullable), timestamps; indexes: po_id, item_id, uom_id; computed: remaining_quantity (quantity - received_quantity) | | |
| TASK-003 | Create enum `PurchaseOrderStatus` with values: DRAFT, PENDING, APPROVED, PARTIAL, FULFILLED, CLOSED; define allowed transitions: DRAFT→PENDING, PENDING→APPROVED, PENDING→REJECTED, APPROVED→PARTIAL (on first GRN), PARTIAL→FULFILLED (when fully received), APPROVED/PARTIAL/FULFILLED→CLOSED (manual closure) | | |
| TASK-004 | Create enum `POApprovalStatus` with values: PENDING, APPROVED, REJECTED | | |
| TASK-005 | Create model `PurchaseOrder.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: po_number, po_date, vendor_id, requisition_id, buyer_id, delivery_address, delivery_date, payment_terms, notes; casts: po_date → date, delivery_date → date, status → PurchaseOrderStatus enum, approval_status → POApprovalStatus enum, total_amount → float, approved_at → datetime, closed_at → datetime; relationships: vendor (belongsTo), requisition (belongsTo), buyer (belongsTo User), lines (hasMany PurchaseOrderLine), approvals (hasMany POApproval), goodsReceipts (hasMany GoodsReceiptNote); scopes: byStatus(PurchaseOrderStatus $status), pending(), approved(), byVendor(int $vendorId), byBuyer(int $userId), byDateRange(Carbon $from, Carbon $to); computed: is_draft (status === DRAFT), is_closed (status === CLOSED), can_be_modified (!is_closed per BR-PO-002), fulfillment_percentage (received/ordered * 100), is_fully_received (fulfillment_percentage === 100), requires_approval (total_amount > 0) | | |
| TASK-006 | Create model `PurchaseOrderLine.php` with fillable: line_number, item_id, item_description, quantity, uom_id, unit_price, delivery_date, notes; casts: quantity → float, unit_price → float, line_total → float, received_quantity → float, delivery_date → date; relationships: purchaseOrder (belongsTo), item (belongsTo InventoryItem), uom (belongsTo); computed: remaining_quantity (quantity - received_quantity), is_fully_received (remaining_quantity <= 0), calculated_line_total (quantity * unit_price) | | |
| TASK-007 | Create factory `PurchaseOrderFactory.php` with states: draft(), pending(), approved(), partial(), fulfilled(), closed(), withVendor(), withRequisition(), withLines(int $count = 3), withBuyer() | | |
| TASK-008 | Create factory `PurchaseOrderLineFactory.php` with states: withItem(), withUnitPrice(), partiallyReceived(float $quantity) | | |
| TASK-009 | Create contract `PurchaseOrderRepositoryContract.php` with methods: findById(int $id): ?PurchaseOrder, findByNumber(string $number, string $tenantId): ?PurchaseOrder, create(array $data): PurchaseOrder, update(PurchaseOrder $po, array $data): PurchaseOrder, delete(PurchaseOrder $po): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getPending(): Collection, getApproved(): Collection, getByVendor(int $vendorId): Collection, getByBuyer(int $userId): Collection, getByDateRange(Carbon $from, Carbon $to): Collection, getTotalSpendByVendor(int $vendorId, Carbon $from, Carbon $to): float | | |
| TASK-010 | Implement `PurchaseOrderRepository.php` with eager loading for vendor, buyer, lines, items; implement filters: status, vendor_id, buyer_id, date_range, approval_status, requisition_id; cache PO lookup by po_number with 15-minute TTL; optimize for SCR-PO-001 (100,000+ POs/year) | | |
| TASK-011 | Create service `POCalculationService.php` with methods: calculateLineTotal(array $lineData): float, calculateTotalAmount(PurchaseOrder $po): float, recalculatePO(PurchaseOrder $po): void (updates all line totals and PO total), calculateFulfillmentPercentage(PurchaseOrder $po): float, getRemainingAmount(PurchaseOrder $po): float | | |
| TASK-012 | Create action `CreatePurchaseOrderAction.php` using AsAction; inject PurchaseOrderRepositoryContract, VendorRepositoryContract, POCalculationService, ActivityLoggerContract; validate vendor exists and is_active; generate po_number (format: PO-{sequence}); validate lines data (min 1 line, quantities > 0, unit_prices >= 0); create PO header; create PO lines; calculate line totals and total_amount via POCalculationService; set status to DRAFT; copy payment_terms from vendor if not provided; log activity "Purchase order created"; dispatch PurchaseOrderCreatedEvent (EV-PO-001); measure and optimize for < 1 second (PR-PO-001); return PurchaseOrder | | |
| TASK-013 | Create action `CreatePOFromRequisitionAction.php`; validate requisition status is APPROVED; validate requisition not already converted; retrieve vendor from requisition or require vendor_id; copy requisition lines to PO lines; set requisition_id link; update requisition status to CONVERTED; log activity "PO created from requisition {requisition_number}"; dispatch POCreatedFromRequisitionEvent; return PurchaseOrder | | |
| TASK-014 | Create action `UpdatePurchaseOrderAction.php`; validate PO can_be_modified (not closed per BR-PO-002); validate status is DRAFT (only draft can be modified); recalculate totals via POCalculationService; log activity "Purchase order updated" with changes; dispatch PurchaseOrderUpdatedEvent; return PurchaseOrder | | |
| TASK-015 | Create action `DeletePurchaseOrderAction.php`; validate status is DRAFT (cannot delete submitted/approved POs); validate no goods receipts exist; soft delete PO; log activity "Purchase order deleted"; dispatch PurchaseOrderDeletedEvent | | |
| TASK-016 | Create event `PurchaseOrderCreatedEvent` with properties: PurchaseOrder $po, User $createdBy (EV-PO-001) | | |
| TASK-017 | Create event `PurchaseOrderUpdatedEvent` with properties: PurchaseOrder $po, array $changes, User $updatedBy | | |
| TASK-018 | Create event `PurchaseOrderDeletedEvent` with properties: PurchaseOrder $po, User $deletedBy | | |
| TASK-019 | Create event `POCreatedFromRequisitionEvent` with properties: PurchaseOrder $po, PurchaseRequisition $requisition, User $createdBy | | |
| TASK-020 | Create observer `PurchaseOrderObserver.php` with creating() to generate po_number and set po_date; updating() to recalculate totals when lines change, enforce immutability of closed POs (BR-PO-002), validate status transitions; deleting() to prevent deletion of non-draft POs | | |

### GOAL-002: Multi-Level Approval Workflow

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-004 | Implement multi-level approval based on thresholds | | |
| BR-PO-001 | Approval thresholds: < $5K (supervisor), < $50K (manager), >= $50K (director) | | |
| ARCH-PO-001 | Use workflow engine for flexible approval routing | | |
| IR-PO-003 | Integrate with Backoffice for approval enforcement | | |
| SR-PO-001 | Implement authorization matrix | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-021 | Create migration `2025_01_01_000007_create_po_approvals_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), po_id (BIGINT FK purchase_orders), approval_level (INT), approver_id (BIGINT FK users), amount_threshold (DECIMAL 15,2 nullable), status (VARCHAR 20: pending/approved/rejected), comments (TEXT nullable), approved_at (TIMESTAMP nullable), timestamps; indexes: tenant_id, po_id, approver_id, status | | |
| TASK-022 | Create migration `2025_01_01_000008_create_approval_thresholds_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), role_name (VARCHAR 100: supervisor/manager/director/executive), min_amount (DECIMAL 15,2), max_amount (DECIMAL 15,2 nullable), approval_level (INT), is_active (BOOLEAN default true), timestamps; indexes: tenant_id, role_name, is_active; seed with BR-PO-001 defaults: supervisor ($0-$5K level 1), manager ($5K-$50K level 2), director ($50K+ level 3) | | |
| TASK-023 | Create enum `ApprovalLevel` with values: SUPERVISOR (1), MANAGER (2), DIRECTOR (3), EXECUTIVE (4) | | |
| TASK-024 | Create model `POApproval.php` with traits: BelongsToTenant; fillable: po_id, approval_level, approver_id, amount_threshold, status, comments; casts: status → ApprovalStatus enum (from PLAN01), amount_threshold → float, approval_level → int, approved_at → datetime; relationships: purchaseOrder (belongsTo), approver (belongsTo User); scopes: pending(), approved(), forPO(int $poId), byLevel(int $level) | | |
| TASK-025 | Create model `ApprovalThreshold.php` with traits: BelongsToTenant; fillable: role_name, min_amount, max_amount, approval_level, is_active; casts: min_amount → float, max_amount → float, approval_level → int, is_active → boolean; scopes: active(), forAmount(float $amount); methods: matchesAmount(float $amount): bool | | |
| TASK-026 | Create factory `POApprovalFactory.php` with states: pending(), approved(), rejected(), forSupervisor(), forManager(), forDirector() | | |
| TASK-027 | Create factory `ApprovalThresholdFactory.php` with states: supervisor(), manager(), director() | | |
| TASK-028 | Create service `POApprovalService.php` with methods: requiresApproval(PurchaseOrder $po): bool, getRequiredApprovalLevels(PurchaseOrder $po): Collection (determine levels based on total_amount and thresholds), getApprovers(PurchaseOrder $po): Collection (get approvers from approval matrix via SUB15 workflow engine per ARCH-PO-001), createApprovalRequests(PurchaseOrder $po): void, canApprove(User $user, PurchaseOrder $po): bool (check user has required approval authority for PO amount per SR-PO-001), isPendingApproval(PurchaseOrder $po): bool, isFullyApproved(PurchaseOrder $po): bool, getNextApprover(PurchaseOrder $po): ?User, isWithinApprovalLimit(User $user, float $amount): bool | | |
| TASK-029 | Create service `WorkflowEngineService.php` implementing ARCH-PO-001 flexible workflow; inject BackofficeWorkflowContract from SUB15; methods: getApprovalWorkflow(string $documentType, float $amount): array (returns workflow configuration), getApprovers(string $documentType, float $amount, ?int $departmentId = null): Collection (returns list of required approvers based on amount thresholds), notifyApprovers(array $approvers, Model $document): void, escalateApproval(Model $document, User $currentApprover): void (future: auto-escalation if timeout) | | |
| TASK-030 | Create action `SubmitPOForApprovalAction.php`; validate PO status is DRAFT; validate PO has lines and total_amount > 0; validate vendor is_active; determine required approval levels via POApprovalService; create approval requests via WorkflowEngineService; update PO status to PENDING and approval_status to PENDING; log activity "PO submitted for approval"; dispatch POSubmittedForApprovalEvent; notify approvers; return PurchaseOrder | | |
| TASK-031 | Create action `ApprovePurchaseOrderAction.php`; validate user is assigned approver; validate approval status PENDING for this level; validate PO still in PENDING status; update approval status to APPROVED with timestamp and comments; if all required levels approved: update PO status to APPROVED, approval_status to APPROVED, set approved_by and approved_at; log activity "PO approved by {user} at level {level}"; dispatch PurchaseOrderApprovedEvent (EV-PO-002); notify requester and next approver if more levels required; return PurchaseOrder | | |
| TASK-032 | Create action `RejectPurchaseOrderAction.php`; validate user is approver; validate rejection reason provided; update approval status to REJECTED with comments; update PO status to DRAFT (return for revision), approval_status to REJECTED; log activity "PO rejected by {user}: {reason}"; dispatch POApprovalRejectedEvent; notify buyer; return PurchaseOrder | | |
| TASK-033 | Create action `ClosePurchaseOrderAction.php`; validate user has 'close-purchase-orders' permission; validate PO status is APPROVED, PARTIAL, or FULFILLED; validate reason provided; update PO status to CLOSED with closed_by and closed_at; log activity "PO closed: {reason}"; dispatch PurchaseOrderClosedEvent; return PurchaseOrder | | |
| TASK-034 | Create event `POSubmittedForApprovalEvent` with properties: PurchaseOrder $po, Collection $approvers | | |
| TASK-035 | Create event `PurchaseOrderApprovedEvent` with properties: PurchaseOrder $po, POApproval $approval, User $approver (EV-PO-002) | | |
| TASK-036 | Create event `POApprovalRejectedEvent` with properties: PurchaseOrder $po, POApproval $approval, User $rejector, string $reason | | |
| TASK-037 | Create event `PurchaseOrderClosedEvent` with properties: PurchaseOrder $po, User $closedBy, string $reason | | |
| TASK-038 | Create listener `NotifyPOApproversListener.php` listening to POSubmittedForApprovalEvent; send notifications to all required approvers; create calendar reminders; create activity log entries | | |
| TASK-039 | Create listener `NotifyBuyerOnPOApprovalListener.php` listening to PurchaseOrderApprovedEvent and POApprovalRejectedEvent; send notification to buyer with approval/rejection details | | |
| TASK-040 | Create listener `UpdateVendorLastOrderDateListener.php` listening to PurchaseOrderApprovedEvent; update vendor's last_order_date field (add to vendor model in PLAN01 if needed) | | |

### GOAL-003: PO Lifecycle Management & Status Tracking

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| BR-PO-002 | Enforce closed PO immutability | | |
| DR-PO-001 | Maintain complete PO history | | |
| SR-PO-002 | Log all status changes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-041 | Create migration `2025_01_01_000009_create_po_status_history_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), po_id (BIGINT FK purchase_orders), old_status (VARCHAR 20), new_status (VARCHAR 20), changed_by (BIGINT FK users), change_reason (TEXT nullable), changed_at (TIMESTAMP), metadata (JSONB nullable); indexes: tenant_id, po_id, changed_at | | |
| TASK-042 | Create model `POStatusHistory.php` with traits: BelongsToTenant; fillable: po_id, old_status, new_status, changed_by, change_reason, changed_at; casts: old_status → PurchaseOrderStatus enum, new_status → PurchaseOrderStatus enum, changed_at → datetime, metadata → array; relationships: purchaseOrder (belongsTo), changedBy (belongsTo User); scopes: forPO(int $poId), byDateRange(Carbon $from, Carbon $to) | | |
| TASK-043 | Create service `POLifecycleService.php` with methods: transitionStatus(PurchaseOrder $po, PurchaseOrderStatus $newStatus, User $user, ?string $reason = null): bool (validate allowed transition, record history), canTransitionTo(PurchaseOrder $po, PurchaseOrderStatus $newStatus): bool, getStatusHistory(PurchaseOrder $po): Collection, getAveragePOApprovalTime(string $tenantId): float (metrics for performance monitoring), getPOsByStatusCount(string $tenantId): array (dashboard statistics) | | |
| TASK-044 | Create action `UpdatePOStatusAction.php`; validate status transition allowed; validate user permissions for target status; record status change in history via POLifecycleService; update PO status; log activity "PO status changed from {old} to {new}"; dispatch POStatusChangedEvent; return PurchaseOrder | | |
| TASK-045 | Create action `GetPOHistoryAction.php`; retrieve complete history including: status changes, approval actions, modifications, goods receipts; return chronologically ordered collection | | |
| TASK-046 | Create event `POStatusChangedEvent` with properties: PurchaseOrder $po, PurchaseOrderStatus $oldStatus, PurchaseOrderStatus $newStatus, User $changedBy, ?string $reason | | |
| TASK-047 | Create listener `RecordPOStatusChangeListener.php` listening to POStatusChangedEvent; create POStatusHistory record; send notifications if status is APPROVED or CLOSED | | |
| TASK-048 | Update PurchaseOrderObserver updating() method to prevent modification when status is CLOSED (BR-PO-002); throw POImmutableException if attempt to modify closed PO | | |
| TASK-049 | Create custom exception `POImmutableException` extends RuntimeException with message "Cannot modify closed purchase order" | | |
| TASK-050 | Add validation in UpdatePurchaseOrderAction to check can_be_modified before allowing any updates (double-check for BR-PO-002) | | |

### GOAL-004: API Controllers & Request Validation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-002, FR-PO-004 | Expose PO and approval APIs | | |
| SR-PO-001 | Enforce authorization matrix | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-051 | Create policy `PurchaseOrderPolicy.php` requiring 'create-purchase-orders' permission for creation; buyers can view/edit own draft POs; managers can view department POs; 'approve-purchase-orders-level-{1,2,3}' permissions for approval based on amount (SR-PO-001); 'close-purchase-orders' permission for closure; enforce tenant scope; validate PO can_be_modified for updates | | |
| TASK-052 | Create API controller `PurchaseOrderController.php` with routes: index (GET /purchasing/purchase-orders), store (POST /purchasing/purchase-orders), show (GET /purchasing/purchase-orders/{id}), update (PATCH /purchasing/purchase-orders/{id}), destroy (DELETE /purchasing/purchase-orders/{id}), submitForApproval (POST /purchase-orders/{id}/submit), approve (POST /purchase-orders/{id}/approve), reject (POST /purchase-orders/{id}/reject), close (POST /purchase-orders/{id}/close), history (GET /purchase-orders/{id}/history), createFromRequisition (POST /purchase-orders/from-requisition/{requisitionId}); authorize all actions; inject PurchaseOrderRepositoryContract | | |
| TASK-053 | Create form request `StorePurchaseOrderRequest.php` with validation: po_date (required, date), vendor_id (required, exists:vendors), requisition_id (nullable, exists:purchase_requisitions), delivery_address (nullable, string), delivery_date (nullable, date, after_or_equal:po_date), payment_terms (nullable, string, max:100), lines (required, array, min:1), lines.*.item_id (nullable, exists:inventory_items), lines.*.item_description (required, string), lines.*.quantity (required, numeric, min:0.0001), lines.*.uom_id (required, exists:uoms), lines.*.unit_price (required, numeric, min:0), lines.*.delivery_date (nullable, date), notes (nullable, string) | | |
| TASK-054 | Create form request `UpdatePurchaseOrderRequest.php` extending StorePurchaseOrderRequest; add validation for can_be_modified (BR-PO-002) | | |
| TASK-055 | Create form request `ApprovePurchaseOrderRequest.php` with validation: comments (nullable, string, max:500) | | |
| TASK-056 | Create form request `RejectPurchaseOrderRequest.php` with validation: reason (required, string, max:500) | | |
| TASK-057 | Create form request `ClosePurchaseOrderRequest.php` with validation: reason (required, string, max:500) | | |
| TASK-058 | Create API resource `PurchaseOrderResource.php` with fields: id, po_number, po_date, vendor (nested VendorResource), requisition (nested), buyer (nested UserResource), lines (nested collection), status, approval_status, total_amount, delivery_address, delivery_date, payment_terms, is_closed, can_be_modified, fulfillment_percentage, approved_by, approved_at, closed_by, closed_at, created_at, updated_at | | |
| TASK-059 | Create API resource `PurchaseOrderLineResource.php` with fields: line_number, item (nested), item_description, quantity, uom (nested), unit_price, line_total, received_quantity, remaining_quantity, is_fully_received, delivery_date | | |
| TASK-060 | Create API resource `POApprovalResource.php` with fields: approval_level, approver (nested UserResource), amount_threshold, status, comments, approved_at | | |
| TASK-061 | Create API resource `POStatusHistoryResource.php` with fields: old_status, new_status, changed_by (nested UserResource), change_reason, changed_at, metadata | | |

### GOAL-005: Testing, Documentation & Deployment

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| PR-PO-001 | PO creation < 1 second | | |
| SCR-PO-001 | Support 100,000+ POs per year | | |
| BR-PO-001, BR-PO-002 | Validate business rules enforcement | | |
| All FRs | Complete test coverage for PO management | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-062 | Write comprehensive unit tests for models: test PurchaseOrder status transitions, test can_be_modified logic (BR-PO-002), test fulfillment_percentage calculation, test POApproval workflow states | | |
| TASK-063 | Write comprehensive unit tests for services: test POCalculationService total calculations, test POApprovalService threshold matching (BR-PO-001), test WorkflowEngineService approval routing (ARCH-PO-001), test POLifecycleService status transitions | | |
| TASK-064 | Write comprehensive unit tests for actions: test CreatePurchaseOrderAction with validation, test CreatePOFromRequisitionAction conversion logic, test approval workflow actions with authorization checks | | |
| TASK-065 | Write feature tests for complete PO workflows: test PO creation→submission→approval→closed; test multi-level approval with different amounts (< $5K, < $50K, >= $50K per BR-PO-001); test rejection returns to draft; test cannot modify closed PO (BR-PO-002) | | |
| TASK-066 | Write integration tests: test workflow engine integration with SUB15 Backoffice (mocked); test approval matrix enforcement (SR-PO-001); test audit logging integration with SUB03 | | |
| TASK-067 | Write performance tests: test PO creation completes in < 1 second (PR-PO-001); test with 10 line items; test with complex approval routing; measure and optimize if needed | | |
| TASK-068 | Write scalability tests: simulate 100,000 POs over 12-month period (SCR-PO-001); test query performance; test pagination with large datasets; ensure no degradation | | |
| TASK-069 | Write security tests: test approval authorization enforced at each level (SR-PO-001); test users cannot approve own POs; test tenant isolation; test cannot skip approval levels | | |
| TASK-070 | Write acceptance tests: test PO creation functional; test requisition-to-PO conversion works; test multi-level approval complete; test closed PO immutability (BR-PO-002); test approval thresholds enforced (BR-PO-001) | | |
| TASK-071 | Set up Pest configuration for PO tests; configure database transactions, tenant seeding, vendor/requisition factories | | |
| TASK-072 | Achieve minimum 80% code coverage for PO module; run `./vendor/bin/pest --coverage --min=80`; add tests for uncovered paths | | |
| TASK-073 | Create API documentation using OpenAPI 3.0 spec: document all PO endpoints with examples, document approval workflow API, document status transitions, document authorization requirements | | |
| TASK-074 | Create user guide: PO creation procedures, approval workflow documentation, PO lifecycle management, requisition-to-PO conversion guide, closing PO procedures | | |
| TASK-075 | Create technical documentation: workflow engine integration architecture, approval threshold configuration, status transition state machine diagram, performance optimization techniques | | |
| TASK-076 | Create admin guide: configuring approval thresholds, managing approval matrix, workflow engine configuration, troubleshooting approval issues | | |
| TASK-077 | Update package README with PO management features: PO creation, multi-level approval, threshold-based routing, status lifecycle, immutability enforcement | | |
| TASK-078 | Validate all acceptance criteria: PO CRUD functional, approval workflow operational, thresholds enforced (BR-PO-001), closed PO immutable (BR-PO-002), performance meets requirements (PR-PO-001, SCR-PO-001) | | |
| TASK-079 | Conduct code review: verify business rules implemented (BR-PO-001, BR-PO-002), verify performance requirements met (PR-PO-001), verify security controls enforced (SR-PO-001), verify workflow engine integration (ARCH-PO-001) | | |
| TASK-080 | Run full test suite for PO module; verify all tests pass; fix flaky tests; ensure consistent results across environments | | |
| TASK-081 | Deploy to staging environment; test complete PO workflow end-to-end; test approval routing with various amounts; test with multiple concurrent approvals; verify performance with production-like data volume | | |
| TASK-082 | Create seeder `PurchaseOrderSeeder.php` for development with sample POs in various statuses (draft, pending, approved, closed), sample approval records, sample status history | | |
| TASK-083 | Update ApprovalThresholdSeeder to populate default thresholds per BR-PO-001: supervisor ($0-$5K), manager ($5K-$50K), director ($50K+) | | |

## 3. Alternatives

- **ALT-001**: Hard-code approval rules in service - rejected to maintain ARCH-PO-001 (use workflow engine for flexibility)
- **ALT-002**: Single-level approval for all POs - rejected; FR-PO-004 requires multi-level based on thresholds
- **ALT-003**: Allow modification of closed POs with audit trail - rejected; BR-PO-002 enforces immutability
- **ALT-004**: Auto-approve POs below threshold - rejected to maintain audit trail and control
- **ALT-005**: Skip approval for requisition-converted POs - rejected; all POs require approval per FR-PO-004
- **ALT-006**: Store approval matrix in purchasing module - rejected; use SUB15 Backoffice workflow engine for centralized management per ARCH-PO-001

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model and isolation
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, permission system
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking
- **DEP-004**: SUB15 (Backoffice) - Workflow engine, approval matrix, organization structure
- **DEP-005**: SUB14 (Inventory Management) - InventoryItem model (for PO lines)
- **DEP-006**: SUB06 (UOM) - UOM model for unit conversions
- **DEP-007**: SUB04 (Serial Numbering) - PO number generation
- **DEP-008**: PLAN01 (Vendor & Requisition Management) - Vendor model, PurchaseRequisition model

### Optional Dependencies
- **DEP-009**: SUB22 (Notifications) - Email notifications for approval requests and decisions
- **DEP-010**: SUB11 (Accounts Payable) - Invoice matching (used in PLAN03)

### Package Dependencies
- **DEP-011**: lorisleiva/laravel-actions ^2.0 - Action and job pattern
- **DEP-012**: Laravel Queue system - Async notification processing
- **DEP-013**: Laravel Cache - PO lookup optimization

## 5. Files

### Models & Enums
- `packages/purchasing/src/Models/PurchaseOrder.php` - PO header model
- `packages/purchasing/src/Models/PurchaseOrderLine.php` - PO line items model
- `packages/purchasing/src/Models/POApproval.php` - Approval workflow model
- `packages/purchasing/src/Models/ApprovalThreshold.php` - Threshold configuration model
- `packages/purchasing/src/Models/POStatusHistory.php` - Status change history model
- `packages/purchasing/src/Enums/PurchaseOrderStatus.php` - PO status enumeration
- `packages/purchasing/src/Enums/POApprovalStatus.php` - Approval status enumeration
- `packages/purchasing/src/Enums/ApprovalLevel.php` - Approval level enumeration

### Repositories & Contracts
- `packages/purchasing/src/Contracts/PurchaseOrderRepositoryContract.php` - PO repository interface
- `packages/purchasing/src/Repositories/PurchaseOrderRepository.php` - PO repository implementation

### Services
- `packages/purchasing/src/Services/POCalculationService.php` - PO total calculations
- `packages/purchasing/src/Services/POApprovalService.php` - Approval workflow logic
- `packages/purchasing/src/Services/WorkflowEngineService.php` - Flexible workflow engine integration
- `packages/purchasing/src/Services/POLifecycleService.php` - Status management and history

### Actions
- `packages/purchasing/src/Actions/CreatePurchaseOrderAction.php` - Create PO
- `packages/purchasing/src/Actions/CreatePOFromRequisitionAction.php` - Convert requisition to PO
- `packages/purchasing/src/Actions/UpdatePurchaseOrderAction.php` - Update PO
- `packages/purchasing/src/Actions/DeletePurchaseOrderAction.php` - Delete PO
- `packages/purchasing/src/Actions/SubmitPOForApprovalAction.php` - Submit for approval
- `packages/purchasing/src/Actions/ApprovePurchaseOrderAction.php` - Approve PO
- `packages/purchasing/src/Actions/RejectPurchaseOrderAction.php` - Reject PO
- `packages/purchasing/src/Actions/ClosePurchaseOrderAction.php` - Close PO
- `packages/purchasing/src/Actions/UpdatePOStatusAction.php` - Status transition
- `packages/purchasing/src/Actions/GetPOHistoryAction.php` - Retrieve history

### Controllers & Requests
- `packages/purchasing/src/Http/Controllers/PurchaseOrderController.php` - PO API controller
- `packages/purchasing/src/Http/Requests/StorePurchaseOrderRequest.php` - PO validation
- `packages/purchasing/src/Http/Requests/UpdatePurchaseOrderRequest.php` - PO update validation
- `packages/purchasing/src/Http/Requests/ApprovePurchaseOrderRequest.php` - Approval validation
- `packages/purchasing/src/Http/Requests/RejectPurchaseOrderRequest.php` - Rejection validation
- `packages/purchasing/src/Http/Requests/ClosePurchaseOrderRequest.php` - Closure validation

### Resources
- `packages/purchasing/src/Http/Resources/PurchaseOrderResource.php` - PO transformation
- `packages/purchasing/src/Http/Resources/PurchaseOrderLineResource.php` - Line transformation
- `packages/purchasing/src/Http/Resources/POApprovalResource.php` - Approval transformation
- `packages/purchasing/src/Http/Resources/POStatusHistoryResource.php` - History transformation

### Events & Listeners
- `packages/purchasing/src/Events/PurchaseOrderCreatedEvent.php` (EV-PO-001)
- `packages/purchasing/src/Events/PurchaseOrderUpdatedEvent.php`
- `packages/purchasing/src/Events/PurchaseOrderDeletedEvent.php`
- `packages/purchasing/src/Events/POCreatedFromRequisitionEvent.php`
- `packages/purchasing/src/Events/POSubmittedForApprovalEvent.php`
- `packages/purchasing/src/Events/PurchaseOrderApprovedEvent.php` (EV-PO-002)
- `packages/purchasing/src/Events/POApprovalRejectedEvent.php`
- `packages/purchasing/src/Events/PurchaseOrderClosedEvent.php`
- `packages/purchasing/src/Events/POStatusChangedEvent.php`
- `packages/purchasing/src/Listeners/NotifyPOApproversListener.php`
- `packages/purchasing/src/Listeners/NotifyBuyerOnPOApprovalListener.php`
- `packages/purchasing/src/Listeners/UpdateVendorLastOrderDateListener.php`
- `packages/purchasing/src/Listeners/RecordPOStatusChangeListener.php`

### Observers, Policies & Exceptions
- `packages/purchasing/src/Observers/PurchaseOrderObserver.php` - PO model observer
- `packages/purchasing/src/Policies/PurchaseOrderPolicy.php` - PO authorization
- `packages/purchasing/src/Exceptions/POImmutableException.php` - Closed PO exception

### Database
- `packages/purchasing/database/migrations/2025_01_01_000005_create_purchase_orders_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000006_create_purchase_order_lines_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000007_create_po_approvals_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000008_create_approval_thresholds_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000009_create_po_status_history_table.php`
- `packages/purchasing/database/factories/PurchaseOrderFactory.php`
- `packages/purchasing/database/factories/PurchaseOrderLineFactory.php`
- `packages/purchasing/database/factories/POApprovalFactory.php`
- `packages/purchasing/database/factories/ApprovalThresholdFactory.php`
- `packages/purchasing/database/seeders/PurchaseOrderSeeder.php`
- `packages/purchasing/database/seeders/ApprovalThresholdSeeder.php`

### Tests (Total: 83 tasks with testing components)
- `packages/purchasing/tests/Unit/Models/PurchaseOrderTest.php`
- `packages/purchasing/tests/Unit/Models/POApprovalTest.php`
- `packages/purchasing/tests/Unit/Services/POApprovalServiceTest.php`
- `packages/purchasing/tests/Unit/Services/WorkflowEngineServiceTest.php`
- `packages/purchasing/tests/Unit/Services/POLifecycleServiceTest.php`
- `packages/purchasing/tests/Feature/PurchaseOrderManagementTest.php`
- `packages/purchasing/tests/Feature/POApprovalWorkflowTest.php`
- `packages/purchasing/tests/Feature/POLifecycleTest.php`
- `packages/purchasing/tests/Integration/WorkflowEngineIntegrationTest.php`
- `packages/purchasing/tests/Performance/POCreationPerformanceTest.php`
- `packages/purchasing/tests/Scalability/LargeDatasetTest.php`

## 6. Testing

### Unit Tests (30 tests)
- **TEST-001**: PurchaseOrder status transitions
- **TEST-002**: PurchaseOrder can_be_modified logic (BR-PO-002)
- **TEST-003**: PurchaseOrder fulfillment_percentage calculation
- **TEST-004**: PurchaseOrderLine remaining_quantity calculation
- **TEST-005**: POApproval workflow states
- **TEST-006**: ApprovalThreshold matchesAmount logic
- **TEST-007**: POCalculationService total calculations
- **TEST-008**: POApprovalService threshold matching (BR-PO-001: $5K, $50K thresholds)
- **TEST-009**: WorkflowEngineService approval routing
- **TEST-010**: POLifecycleService status transitions
- **TEST-011**: All action classes with mocked dependencies

### Feature Tests (30 tests)
- **TEST-012**: Create PO via API
- **TEST-013**: Create PO from approved requisition
- **TEST-014**: Cannot modify closed PO (BR-PO-002)
- **TEST-015**: Cannot delete approved PO
- **TEST-016**: Submit PO for approval workflow
- **TEST-017**: Multi-level approval with < $5K amount (supervisor only)
- **TEST-018**: Multi-level approval with < $50K amount (supervisor + manager)
- **TEST-019**: Multi-level approval with >= $50K amount (supervisor + manager + director)
- **TEST-020**: Reject PO returns to draft status
- **TEST-021**: PO approval authorization enforced (SR-PO-001)
- **TEST-022**: Close PO manually
- **TEST-023**: Complete PO lifecycle (create→submit→approve→close)
- **TEST-024**: Status history recorded for all changes

### Integration Tests (8 tests)
- **TEST-025**: Workflow engine integration with SUB15 Backoffice (mocked)
- **TEST-026**: Approval matrix enforcement (SR-PO-001)
- **TEST-027**: Audit logging integration with SUB03
- **TEST-028**: Requisition status updated when converted to PO

### Performance Tests (5 tests)
- **TEST-029**: PO creation < 1 second (PR-PO-001)
- **TEST-030**: PO creation with 10 line items < 1 second
- **TEST-031**: Complex approval routing performance
- **TEST-032**: PO query pagination with 10,000 POs
- **TEST-033**: Approval workflow with concurrent approvals

### Scalability Tests (3 tests)
- **TEST-034**: 100,000 POs over 12 months (SCR-PO-001)
- **TEST-035**: Query performance with large dataset
- **TEST-036**: Pagination performance with 100,000+ records

### Security Tests (5 tests)
- **TEST-037**: Approval authorization enforced at each level (SR-PO-001)
- **TEST-038**: Cannot approve own PO
- **TEST-039**: Tenant isolation for POs
- **TEST-040**: Cannot skip approval levels
- **TEST-041**: Closed PO modification prevented (BR-PO-002)

### Acceptance Tests (2 tests)
- **TEST-042**: Complete PO workflow functional (create→approve→close)
- **TEST-043**: Approval thresholds enforced correctly (BR-PO-001)

**Total Test Coverage:** 83 tests (30 unit + 30 feature + 8 integration + 5 performance + 3 scalability + 5 security + 2 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Workflow engine integration complexity may cause delays - Mitigation: define clear contract with SUB15, provide fallback to simple threshold-based approval
- **RISK-002**: Approval bottlenecks may delay procurement - Mitigation: implement notification escalation, approval delegation (future)
- **RISK-003**: Performance degradation with large PO volumes - Mitigation: database indexing, query optimization, caching
- **RISK-004**: Concurrent approval conflicts (two approvers at same time) - Mitigation: use database locking, handle race conditions
- **RISK-005**: Closed PO immutability may be too restrictive - Mitigation: implement amendment/revision process (future enhancement)

### Assumptions
- **ASSUMPTION-001**: SUB15 Backoffice provides workflow engine API per ARCH-PO-001
- **ASSUMPTION-002**: Approval thresholds are configurable per tenant
- **ASSUMPTION-003**: All POs require approval regardless of amount
- **ASSUMPTION-004**: PO numbers are unique per tenant (not globally unique)
- **ASSUMPTION-005**: Closed POs cannot be reopened (must create new PO if needed)
- **ASSUMPTION-006**: Approval levels are sequential (level 1 before level 2, etc.)
- **ASSUMPTION-007**: One approver per level (no parallel approvals at same level)

## 8. KIV for Future Implementations

- **KIV-001**: PO amendment/revision workflow (create new version of closed PO)
- **KIV-002**: Approval delegation (delegate to another user temporarily)
- **KIV-003**: Approval escalation (auto-escalate if no response within SLA)
- **KIV-004**: Budget checking before PO approval
- **KIV-005**: PO templates for recurring purchases
- **KIV-006**: Blanket/standing POs with release schedules
- **KIV-007**: PO consolidation (merge multiple requisitions into one PO)
- **KIV-008**: Auto-approval for trusted vendors below threshold
- **KIV-009**: PO comparison shopping (compare quotes from multiple vendors)
- **KIV-010**: Contract-based PO pricing (reference master contracts)
- **KIV-011**: PO change orders (modify after approval with new approval)
- **KIV-012**: Multi-currency PO support
- **KIV-013**: PO analytics dashboard (spend by vendor, category, department)

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB16-PURCHASING.md](../prd/prd-01/PRD01-SUB16-PURCHASING.md)
- **Related Plans**:
  - PRD01-SUB16-PLAN01 (Vendor Management & Purchase Requisition) - Foundation (vendors, requisitions)
  - PRD01-SUB16-PLAN03 (Goods Receipt & Three-Way Matching) - Next plan, receives PO items
  - PRD01-SUB16-PLAN04 (Vendor Performance & Reporting) - Analytics and metrics
- **Integration Documentation**:
  - SUB15 (Backoffice) - Workflow engine integration (ARCH-PO-001)
  - SUB14 (Inventory Management) - Item master data for PO lines
  - SUB11 (Accounts Payable) - Three-way matching (PLAN03)
  - SUB03 (Audit Logging) - Activity tracking (SR-PO-002)
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
