---
plan: Goods Receipt Notes & Three-Way Matching
version: 1.0
date_created: 2025-11-12
last_updated: 2025-11-12
owner: Development Team
status: Planned
tags: [feature, purchasing, goods-receipt, three-way-matching, quality-inspection, inventory-integration, batch-tracking, business-logic, procurement]
---

# PRD01-SUB16-PLAN03: Implement Goods Receipt Notes & Three-Way Matching

![Status: Planned](https://img.shields.io/badge/Status-Planned-blue)

This implementation plan covers goods receipt processing, quality inspection, partial delivery handling, and three-way matching (PO + GRN + Invoice). This plan completes the procure-to-pay cycle by receiving ordered goods, posting to inventory, and enabling invoice verification through three-way matching with Accounts Payable integration.

## 1. Requirements & Constraints

### Functional Requirements
- **FR-PO-005**: Process goods receipt with quality inspection and batch/serial tracking
- **FR-PO-006**: Handle partial deliveries and back orders
- **FR-PO-007**: Perform three-way matching (PO + GRN + Vendor Invoice)

### Business Rules
- **BR-PO-003**: Cannot receive more than ordered quantity per PO line

### Data Requirements
- **DR-PO-002**: Record all GRN details including batch/serial numbers, quality status

### Integration Requirements
- **IR-PO-001**: Integrate with AP for three-way matching
- **IR-PO-002**: Integrate with Inventory for stock posting

### Security Requirements
- **SR-PO-002**: Log all GRN modifications with user and timestamp

### Event Requirements
- **EV-PO-003**: Emit GoodsReceivedEvent for inventory posting and AP matching

### Constraints
- **CON-001**: Depends on SUB01 (Multi-Tenancy) for tenant isolation
- **CON-002**: Depends on SUB02 (Authentication) for user access control
- **CON-003**: Depends on SUB03 (Audit Logging) for activity tracking
- **CON-004**: Depends on SUB14 (Inventory Management) for stock movements
- **CON-005**: Depends on SUB11 (Accounts Payable) for three-way matching
- **CON-006**: Depends on PLAN02 for approved PO data

### Guidelines
- **GUD-001**: Follow repository pattern for all data access
- **GUD-002**: Use Laravel Actions for all business logic
- **GUD-003**: Generate unique GRN numbers automatically
- **GUD-004**: Log all quality inspection outcomes
- **GUD-005**: Post to inventory only after quality inspection passes

### Patterns
- **PAT-001**: State machine pattern for GRN status transitions
- **PAT-002**: Observer pattern for automatic PO fulfillment updates
- **PAT-003**: Strategy pattern for quality inspection rules
- **PAT-004**: Repository pattern with contracts for data access
- **PAT-005**: Event-driven integration with Inventory and AP modules

## 2. Implementation Steps

### GOAL-001: Goods Receipt Note Foundation & Creation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-005 | Implement GRN creation with quality inspection | | |
| DR-PO-002 | Record GRN details including batch/serial numbers | | |
| SR-PO-002 | Log all GRN modifications | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Create migration `2025_01_01_000010_create_goods_receipt_notes_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), grn_number (VARCHAR 100 unique per tenant), grn_date (DATE), po_id (BIGINT FK purchase_orders), vendor_id (BIGINT FK vendors), received_by (BIGINT FK users), warehouse_id (BIGINT nullable FK warehouses from SUB14), delivery_note_number (VARCHAR 100 nullable), carrier_name (VARCHAR 255 nullable), inspection_status (VARCHAR 20 default 'pending': pending/passed/failed), inspected_by (BIGINT nullable FK users), inspected_at (TIMESTAMP nullable), inspection_notes (TEXT nullable), status (VARCHAR 20 default 'draft': draft/posted), posted_at (TIMESTAMP nullable), notes (TEXT nullable), timestamps; indexes: tenant_id, grn_number, po_id, vendor_id, received_by, grn_date, inspection_status, status | | |
| TASK-002 | Create migration `2025_01_01_000011_create_goods_receipt_note_lines_table.php` with columns: id (BIGSERIAL), grn_id (BIGINT FK goods_receipt_notes cascade), line_number (INT), po_line_id (BIGINT FK purchase_order_lines), item_id (BIGINT FK inventory_items), received_quantity (DECIMAL 15,4), accepted_quantity (DECIMAL 15,4 default 0), rejected_quantity (DECIMAL 15,4 default 0), uom_id (BIGINT FK uoms), batch_number (VARCHAR 100 nullable), serial_number (VARCHAR 100 nullable), expiry_date (DATE nullable), quality_status (VARCHAR 20 default 'pending': pending/passed/failed), rejection_reason (TEXT nullable), notes (TEXT nullable), timestamps; indexes: grn_id, po_line_id, item_id, batch_number, serial_number, quality_status; computed: inspection_pending (quality_status === 'pending') | | |
| TASK-003 | Create enum `GRNInspectionStatus` with values: PENDING, PASSED, FAILED, PARTIAL (some lines passed, some failed) | | |
| TASK-004 | Create enum `GRNStatus` with values: DRAFT, POSTED (posted to inventory) | | |
| TASK-005 | Create enum `LineQualityStatus` with values: PENDING, PASSED, FAILED | | |
| TASK-006 | Create model `GoodsReceiptNote.php` with traits: BelongsToTenant, HasActivityLogging, SoftDeletes; fillable: grn_number, grn_date, po_id, vendor_id, received_by, warehouse_id, delivery_note_number, carrier_name, inspection_notes, notes; casts: grn_date → date, inspection_status → GRNInspectionStatus enum, inspected_at → datetime, status → GRNStatus enum, posted_at → datetime; relationships: purchaseOrder (belongsTo), vendor (belongsTo), receivedBy (belongsTo User), inspectedBy (belongsTo User), warehouse (belongsTo), lines (hasMany GoodsReceiptNoteLine); scopes: byStatus(GRNStatus $status), byInspectionStatus(GRNInspectionStatus $status), pending(), posted(), byPO(int $poId), byVendor(int $vendorId), byDateRange(Carbon $from, Carbon $to); computed: is_draft (status === DRAFT), is_posted (status === POSTED), total_received_quantity (sum of lines received_quantity), total_accepted_quantity (sum of lines accepted_quantity), total_rejected_quantity (sum of lines rejected_quantity), inspection_complete (all lines have quality_status !== 'pending'), can_be_posted (inspection_complete && inspection_status === 'passed') | | |
| TASK-007 | Create model `GoodsReceiptNoteLine.php` with fillable: line_number, po_line_id, item_id, received_quantity, accepted_quantity, rejected_quantity, uom_id, batch_number, serial_number, expiry_date, quality_status, rejection_reason, notes; casts: received_quantity → float, accepted_quantity → float, rejected_quantity → float, quality_status → LineQualityStatus enum, expiry_date → date; relationships: grn (belongsTo GoodsReceiptNote), poLine (belongsTo PurchaseOrderLine), item (belongsTo InventoryItem), uom (belongsTo); computed: inspection_pending (quality_status === 'pending'), variance (received_quantity - (accepted_quantity + rejected_quantity)) | | |
| TASK-008 | Create factory `GoodsReceiptNoteFactory.php` with states: draft(), posted(), pending(), inspectionPassed(), inspectionFailed(), withPO(), withVendor(), withLines(int $count = 3) | | |
| TASK-009 | Create factory `GoodsReceiptNoteLineFactory.php` with states: withItem(), withBatch(), withSerial(), passed(), failed(), partiallyAccepted() | | |
| TASK-010 | Create contract `GoodsReceiptNoteRepositoryContract.php` with methods: findById(int $id): ?GoodsReceiptNote, findByNumber(string $number, string $tenantId): ?GoodsReceiptNote, create(array $data): GoodsReceiptNote, update(GoodsReceiptNote $grn, array $data): GoodsReceiptNote, delete(GoodsReceiptNote $grn): bool, paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator, getByPO(int $poId): Collection, getPending(): Collection, getByVendor(int $vendorId): Collection | | |
| TASK-011 | Implement `GoodsReceiptNoteRepository.php` with eager loading for purchaseOrder, vendor, receivedBy, lines, items; implement filters: status, inspection_status, po_id, vendor_id, grn_date_range, warehouse_id | | |
| TASK-012 | Create service `GRNCalculationService.php` with methods: calculateTotals(GoodsReceiptNote $grn): array (returns totals for received, accepted, rejected), updatePOLineReceivedQuantity(PurchaseOrderLine $poLine, float $quantity): void, calculatePOFulfillmentPercentage(PurchaseOrder $po): float, canReceiveQuantity(PurchaseOrderLine $poLine, float $quantity): bool (validate BR-PO-003: cannot exceed ordered) | | |
| TASK-013 | Create action `CreateGoodsReceiptAction.php` using AsAction; inject GoodsReceiptNoteRepositoryContract, PurchaseOrderRepositoryContract, GRNCalculationService, ActivityLoggerContract; validate PO exists and status is APPROVED or PARTIAL; validate vendor matches PO vendor; generate grn_number (format: GRN-{sequence}); create GRN header; create GRN lines from input; validate received quantities do not exceed ordered (BR-PO-003); set inspection_status to PENDING; set status to DRAFT; log activity "Goods receipt created for PO {po_number}"; dispatch GoodsReceiptCreatedEvent; return GoodsReceiptNote | | |
| TASK-014 | Create action `UpdateGoodsReceiptAction.php`; validate GRN status is DRAFT (only draft can be modified); validate quantities within limits (BR-PO-003); recalculate totals; log activity "Goods receipt updated" with changes; dispatch GoodsReceiptUpdatedEvent | | |
| TASK-015 | Create action `DeleteGoodsReceiptAction.php`; validate GRN status is DRAFT (cannot delete posted GRN); soft delete GRN; log activity "Goods receipt deleted"; dispatch GoodsReceiptDeletedEvent | | |
| TASK-016 | Create event `GoodsReceiptCreatedEvent` with properties: GoodsReceiptNote $grn, User $createdBy | | |
| TASK-017 | Create event `GoodsReceiptUpdatedEvent` with properties: GoodsReceiptNote $grn, array $changes, User $updatedBy | | |
| TASK-018 | Create event `GoodsReceiptDeletedEvent` with properties: GoodsReceiptNote $grn, User $deletedBy | | |
| TASK-019 | Create observer `GoodsReceiptNoteObserver.php` with creating() to generate grn_number and set grn_date; updating() to recalculate totals, validate inspection completion; deleting() to prevent deletion of posted GRNs | | |

### GOAL-002: Quality Inspection & Acceptance

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-005 | Implement quality inspection workflow | | |
| DR-PO-002 | Record quality inspection outcomes | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-020 | Create migration `2025_01_01_000012_create_quality_inspection_checklists_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), item_id (BIGINT nullable FK inventory_items), checklist_name (VARCHAR 255), criteria (JSONB: array of {criterion: string, passed: bool, notes: string}), is_active (BOOLEAN default true), timestamps; indexes: tenant_id, item_id, is_active | | |
| TASK-021 | Create model `QualityInspectionChecklist.php` with traits: BelongsToTenant; fillable: item_id, checklist_name, criteria, is_active; casts: criteria → array, is_active → boolean; relationships: item (belongsTo InventoryItem); scopes: active(), forItem(int $itemId) | | |
| TASK-022 | Create factory `QualityInspectionChecklistFactory.php` with states: active(), inactive(), forSpecificItem(int $itemId), withCriteria(array $criteria) | | |
| TASK-023 | Create service `QualityInspectionService.php` with methods: getChecklist(InventoryItem $item): ?QualityInspectionChecklist, performInspection(GoodsReceiptNoteLine $line, array $results): void (validates against checklist), calculateInspectionResult(array $results): LineQualityStatus (passed if all criteria passed), requiresInspection(InventoryItem $item): bool | | |
| TASK-024 | Create action `InspectGoodsReceiptLineAction.php`; validate GRN line exists; validate quality_status is PENDING; retrieve inspection checklist for item; validate inspection results against checklist; update line quality_status (passed/failed); calculate accepted_quantity and rejected_quantity; log activity "Line {line_number} inspection: {result}"; dispatch GoodsLineInspectedEvent | | |
| TASK-025 | Create action `CompleteGRNInspectionAction.php`; validate all lines inspected (no PENDING); calculate overall inspection_status: PASSED (all passed), FAILED (all failed), PARTIAL (mixed); set inspected_by and inspected_at; log activity "GRN inspection completed: {status}"; dispatch GoodsInspectionCompletedEvent | | |
| TASK-026 | Create action `RejectGoodsReceiptLineAction.php`; validate rejection_reason provided; set line quality_status to FAILED; set accepted_quantity = 0, rejected_quantity = received_quantity; log activity "Line {line_number} rejected: {reason}"; dispatch GoodsLineRejectedEvent | | |
| TASK-027 | Create action `AcceptGoodsReceiptLineAction.php`; set line quality_status to PASSED; set accepted_quantity = received_quantity, rejected_quantity = 0; log activity "Line {line_number} accepted"; dispatch GoodsLineAcceptedEvent | | |
| TASK-028 | Create event `GoodsLineInspectedEvent` with properties: GoodsReceiptNoteLine $line, LineQualityStatus $result, User $inspector | | |
| TASK-029 | Create event `GoodsInspectionCompletedEvent` with properties: GoodsReceiptNote $grn, GRNInspectionStatus $overallStatus, User $inspector | | |
| TASK-030 | Create event `GoodsLineRejectedEvent` with properties: GoodsReceiptNoteLine $line, string $reason, User $rejector | | |
| TASK-031 | Create event `GoodsLineAcceptedEvent` with properties: GoodsReceiptNoteLine $line, User $acceptor | | |

### GOAL-003: Partial Deliveries & PO Fulfillment

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-006 | Handle partial deliveries and back orders | | |
| BR-PO-003 | Cannot receive more than ordered | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-032 | Update PurchaseOrder model: add computed property back_order_lines (lines where remaining_quantity > 0); add computed property has_back_orders (bool); update fulfillment_percentage calculation to use accepted_quantity | | |
| TASK-033 | Update PurchaseOrderLine model: add computed property back_order_quantity (ordered - accepted); add computed property is_back_order (back_order_quantity > 0) | | |
| TASK-034 | Create service `POFulfillmentService.php` with methods: updatePOFulfillment(GoodsReceiptNote $grn): void (updates PO line received quantities and PO status), updatePOStatus(PurchaseOrder $po): void (updates status to PARTIAL or FULFILLED based on fulfillment), getBackOrderLines(PurchaseOrder $po): Collection, canClosePOWithBackOrders(PurchaseOrder $po): bool (check if back orders acceptable for closure) | | |
| TASK-035 | Create action `PostGoodsReceiptToInventoryAction.php` using AsAction; validate GRN inspection_status is PASSED or PARTIAL; validate GRN status is DRAFT (not already posted); validate warehouse_id exists; for each line with quality_status PASSED: post accepted_quantity to inventory via SUB14 InventoryMovementService; update PO line received_quantity; update PO fulfillment status via POFulfillmentService; set GRN status to POSTED with posted_at timestamp; log activity "GRN posted to inventory"; dispatch GoodsReceivedEvent (EV-PO-003); return GoodsReceiptNote | | |
| TASK-036 | Create action `CreateBackOrderReportAction.php`; retrieve PO back order lines; calculate total back order value; generate report data with line details, expected delivery dates, vendor information; return Collection | | |
| TASK-037 | Create event `GoodsReceivedEvent` with properties: GoodsReceiptNote $grn, PurchaseOrder $po, Collection $inventoryMovements (EV-PO-003) | | |
| TASK-038 | Create listener `UpdatePOFulfillmentListener.php` listening to GoodsReceivedEvent; update PO status (APPROVED → PARTIAL or FULFILLED); update PO line received quantities; recalculate fulfillment percentage | | |
| TASK-039 | Create listener `NotifyBuyerOnGoodsReceivedListener.php` listening to GoodsReceivedEvent; send notification to buyer with GRN details; notify if back orders exist | | |

### GOAL-004: Three-Way Matching Integration

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| FR-PO-007 | Perform three-way matching (PO + GRN + Invoice) | | |
| IR-PO-001 | Integrate with AP for invoice matching | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-040 | Create migration `2025_01_01_000013_create_three_way_matches_table.php` with columns: id (BIGSERIAL), tenant_id (UUID FK), po_id (BIGINT FK purchase_orders), grn_id (BIGINT FK goods_receipt_notes), invoice_id (BIGINT nullable FK vendor_invoices from SUB11), match_status (VARCHAR 20: pending/matched/variance), quantity_variance (DECIMAL 15,4 default 0), price_variance (DECIMAL 15,2 default 0), total_variance (DECIMAL 15,2 default 0), variance_percentage (DECIMAL 5,2 default 0), matched_by (BIGINT nullable FK users), matched_at (TIMESTAMP nullable), notes (TEXT nullable), timestamps; indexes: tenant_id, po_id, grn_id, invoice_id, match_status | | |
| TASK-041 | Create enum `ThreeWayMatchStatus` with values: PENDING, MATCHED, VARIANCE, FAILED | | |
| TASK-042 | Create model `ThreeWayMatch.php` with traits: BelongsToTenant; fillable: po_id, grn_id, invoice_id, match_status, quantity_variance, price_variance, total_variance, variance_percentage, notes; casts: match_status → ThreeWayMatchStatus enum, quantity_variance → float, price_variance → float, total_variance → float, variance_percentage → float, matched_at → datetime; relationships: purchaseOrder (belongsTo), grn (belongsTo GoodsReceiptNote), invoice (belongsTo VendorInvoice from SUB11), matchedBy (belongsTo User); scopes: pending(), matched(), withVariance() | | |
| TASK-043 | Create factory `ThreeWayMatchFactory.php` with states: pending(), matched(), withVariance(), failed() | | |
| TASK-044 | Create contract `ThreeWayMatchingServiceContract.php` with methods: performMatch(int $poId, int $grnId, int $invoiceId): ThreeWayMatch, calculateVariances(PurchaseOrder $po, GoodsReceiptNote $grn, VendorInvoice $invoice): array, isWithinTolerance(float $variancePercentage): bool (check if variance acceptable per threshold), approvMatch(ThreeWayMatch $match, User $user): void, rejectMatch(ThreeWayMatch $match, User $user, string $reason): void | | |
| TASK-045 | Implement `ThreeWayMatchingService.php` implementing contract; inject PurchaseOrderRepositoryContract, GoodsReceiptNoteRepositoryContract, VendorInvoiceRepositoryContract from SUB11; performMatch() method: retrieve PO, GRN, invoice; validate vendor matches; validate quantities: invoice qty should match GRN accepted qty; validate prices: invoice price should match PO price; calculate variances: quantity_variance = invoice_qty - grn_accepted_qty, price_variance = invoice_price - po_price, total_variance = (invoice_qty * invoice_price) - (grn_accepted_qty * po_price); calculate variance_percentage = (total_variance / po_line_total) * 100; determine match_status: MATCHED (variance_percentage < 5%), VARIANCE (5% <= variance < 10%), FAILED (variance >= 10%); create ThreeWayMatch record; return ThreeWayMatch | | |
| TASK-046 | Create action `PerformThreeWayMatchAction.php` using AsAction; validate PO, GRN, invoice all exist; validate GRN is posted; validate invoice not already matched; perform match via ThreeWayMatchingService; log activity "Three-way match performed: {status}"; dispatch ThreeWayMatchCompletedEvent; return ThreeWayMatch | | |
| TASK-047 | Create action `ApproveThreeWayMatchAction.php`; validate match status is VARIANCE (matches with variance need approval); validate user has 'approve-invoice-variance' permission; update match status to MATCHED; set matched_by and matched_at; approve invoice in SUB11 via InvoiceApprovalService; log activity "Three-way match approved despite variance"; dispatch ThreeWayMatchApprovedEvent | | |
| TASK-048 | Create action `RejectThreeWayMatchAction.php`; validate rejection reason provided; update match status to FAILED; reject invoice in SUB11; log activity "Three-way match rejected: {reason}"; dispatch ThreeWayMatchRejectedEvent; notify AP team | | |
| TASK-049 | Create event `ThreeWayMatchCompletedEvent` with properties: ThreeWayMatch $match, ThreeWayMatchStatus $status | | |
| TASK-050 | Create event `ThreeWayMatchApprovedEvent` with properties: ThreeWayMatch $match, User $approver | | |
| TASK-051 | Create event `ThreeWayMatchRejectedEvent` with properties: ThreeWayMatch $match, User $rejector, string $reason | | |
| TASK-052 | Create listener `NotifyAPOnMatchCompletedListener.php` listening to ThreeWayMatchCompletedEvent; send notification to AP team with match results; flag variances requiring attention | | |
| TASK-053 | Create listener `UpdateInvoiceStatusListener.php` listening to ThreeWayMatchApprovedEvent; update invoice status in SUB11 to approved; trigger payment processing | | |

### GOAL-005: API Controllers, Testing & Documentation

| Requirements Addressed | Description | Completed | Date |
|------------------------|-------------|-----------|------|
| All FRs | Complete test coverage for goods receipt and matching | | |
| IR-PO-001, IR-PO-002 | Integration testing with AP and Inventory | | |

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-054 | Create policy `GoodsReceiptNotePolicy.php` requiring 'create-goods-receipts' permission for creation; 'inspect-goods' permission for quality inspection; 'post-goods-receipts' permission for posting to inventory; enforce tenant scope | | |
| TASK-055 | Create policy `ThreeWayMatchPolicy.php` requiring 'perform-three-way-match' permission; 'approve-invoice-variance' for variance approval; enforce tenant scope | | |
| TASK-056 | Create API controller `GoodsReceiptNoteController.php` with routes: index (GET /purchasing/goods-receipts), store (POST /purchasing/goods-receipts), show (GET /purchasing/goods-receipts/{id}), update (PATCH /purchasing/goods-receipts/{id}), destroy (DELETE /purchasing/goods-receipts/{id}), inspectLine (POST /goods-receipts/{id}/lines/{lineId}/inspect), completeInspection (POST /goods-receipts/{id}/complete-inspection), postToInventory (POST /goods-receipts/{id}/post); authorize actions | | |
| TASK-057 | Create API controller `ThreeWayMatchController.php` with routes: index (GET /purchasing/three-way-matches), performMatch (POST /purchasing/three-way-matches), approve (POST /three-way-matches/{id}/approve), reject (POST /three-way-matches/{id}/reject); authorize actions | | |
| TASK-058 | Create form request `StoreGoodsReceiptRequest.php` with validation: grn_date (required, date), po_id (required, exists:purchase_orders), warehouse_id (nullable, exists:warehouses), delivery_note_number (nullable, string, max:100), carrier_name (nullable, string, max:255), lines (required, array, min:1), lines.*.po_line_id (required, exists:purchase_order_lines), lines.*.received_quantity (required, numeric, min:0.0001), lines.*.batch_number (nullable, string, max:100), lines.*.serial_number (nullable, string, max:100), lines.*.expiry_date (nullable, date, after:today), notes (nullable, string) | | |
| TASK-059 | Create form request `InspectGoodsReceiptLineRequest.php` with validation: inspection_results (required, array), inspection_results.*.criterion (required, string), inspection_results.*.passed (required, boolean), inspection_results.*.notes (nullable, string), accepted_quantity (required, numeric, min:0), rejected_quantity (required, numeric, min:0) | | |
| TASK-060 | Create form request `PerformThreeWayMatchRequest.php` with validation: po_id (required, exists:purchase_orders), grn_id (required, exists:goods_receipt_notes), invoice_id (required, exists:vendor_invoices) | | |
| TASK-061 | Create form request `ApproveMatchRequest.php` with validation: notes (nullable, string, max:500) | | |
| TASK-062 | Create form request `RejectMatchRequest.php` with validation: reason (required, string, max:500) | | |
| TASK-063 | Create API resource `GoodsReceiptNoteResource.php` with fields: id, grn_number, grn_date, purchaseOrder (nested), vendor (nested), receivedBy (nested), warehouse (nested), lines (nested collection), inspection_status, inspected_by, inspected_at, status, is_posted, total_received_quantity, total_accepted_quantity, total_rejected_quantity, can_be_posted, created_at | | |
| TASK-064 | Create API resource `GoodsReceiptNoteLineResource.php` with fields: line_number, item (nested), received_quantity, accepted_quantity, rejected_quantity, uom (nested), batch_number, serial_number, expiry_date, quality_status, rejection_reason | | |
| TASK-065 | Create API resource `ThreeWayMatchResource.php` with fields: id, purchaseOrder (nested), grn (nested), invoice (nested), match_status, quantity_variance, price_variance, total_variance, variance_percentage, matched_by, matched_at, notes | | |
| TASK-066 | Write comprehensive unit tests for models: test GoodsReceiptNote inspection_complete logic, test GRN line variance calculation, test ThreeWayMatch variance calculation | | |
| TASK-067 | Write comprehensive unit tests for services: test GRNCalculationService quantity limits (BR-PO-003), test QualityInspectionService checklist validation, test POFulfillmentService fulfillment calculations, test ThreeWayMatchingService variance calculations | | |
| TASK-068 | Write comprehensive unit tests for actions: test CreateGoodsReceiptAction with validation, test inspection workflow actions, test three-way matching actions | | |
| TASK-069 | Write feature tests for complete GRN workflows: test goods receipt creation→inspection→posting; test partial receipt with back orders (FR-PO-006); test quality inspection rejection; test posting to inventory | | |
| TASK-070 | Write integration tests: test inventory posting integration with SUB14 (mocked); test three-way matching integration with SUB11 AP (mocked); test back order reporting | | |
| TASK-071 | Write business rule tests: test cannot receive more than ordered (BR-PO-003); test GRN must pass inspection before posting; test three-way match variance tolerances | | |
| TASK-072 | Write acceptance tests: test complete procure-to-pay cycle (requisition→PO→GRN→invoice match); test partial delivery handling; test quality inspection workflow; test three-way matching with variance approval | | |
| TASK-073 | Set up Pest configuration for GRN tests; configure database transactions, PO/vendor factories | | |
| TASK-074 | Achieve minimum 80% code coverage for GRN and matching modules; run `./vendor/bin/pest --coverage --min=80` | | |
| TASK-075 | Create API documentation using OpenAPI 3.0: document all GRN endpoints, document inspection workflow, document three-way matching API, document inventory integration | | |
| TASK-076 | Create user guide: goods receipt procedures, quality inspection workflow, partial delivery handling, three-way matching process, back order management | | |
| TASK-077 | Create technical documentation: inventory integration architecture, three-way matching algorithm, variance tolerance configuration, batch/serial tracking implementation | | |
| TASK-078 | Create admin guide: configuring quality inspection checklists, managing variance tolerances, troubleshooting posting failures, managing back orders | | |
| TASK-079 | Update package README with GRN features: goods receipt processing, quality inspection, partial deliveries, three-way matching, inventory integration | | |
| TASK-080 | Validate all acceptance criteria: GRN creation functional, quality inspection works, posting to inventory successful, three-way matching operational, partial delivery handling correct (FR-PO-006), quantity limits enforced (BR-PO-003) | | |
| TASK-081 | Conduct code review: verify business rules (BR-PO-003), verify integration with SUB14 and SUB11, verify event dispatching (EV-PO-003), verify data recording (DR-PO-002) | | |
| TASK-082 | Run full test suite for GRN module; verify all tests pass; fix integration issues with Inventory and AP modules | | |
| TASK-083 | Deploy to staging; test complete goods receipt workflow end-to-end; test inventory posting with real warehouse data; test three-way matching with sample invoices | | |
| TASK-084 | Create seeder `GoodsReceiptSeeder.php` for development with sample GRNs in various statuses, sample inspection data, sample three-way matches | | |
| TASK-085 | Create seeder `QualityInspectionChecklistSeeder.php` with sample checklists for common item categories | | |

## 3. Alternatives

- **ALT-001**: Auto-approve all goods receipts without inspection - rejected; FR-PO-005 requires quality inspection
- **ALT-002**: Allow over-receiving against PO - rejected; BR-PO-003 prevents exceeding ordered quantity
- **ALT-003**: Post to inventory before inspection - rejected; only accepted goods should post to inventory
- **ALT-004**: Skip three-way matching for invoices matching GRN exactly - considered but rejected for audit trail
- **ALT-005**: Manual inventory posting instead of automatic - rejected; integration with SUB14 provides automation
- **ALT-006**: Store batch/serial numbers in inventory module only - rejected; DR-PO-002 requires recording at receipt

## 4. Dependencies

### Mandatory Dependencies
- **DEP-001**: SUB01 (Multi-Tenancy) - Tenant model and isolation
- **DEP-002**: SUB02 (Authentication & Authorization) - User model, permission system
- **DEP-003**: SUB03 (Audit Logging) - ActivityLoggerContract for tracking
- **DEP-004**: SUB14 (Inventory Management) - InventoryItem model, InventoryMovementService, Warehouse model
- **DEP-005**: SUB11 (Accounts Payable) - VendorInvoice model, InvoiceApprovalService for three-way matching
- **DEP-006**: SUB06 (UOM) - UOM model for unit conversions
- **DEP-007**: SUB04 (Serial Numbering) - GRN number generation
- **DEP-008**: PLAN02 (Purchase Orders) - PurchaseOrder model, PurchaseOrderLine model

### Optional Dependencies
- **DEP-009**: SUB22 (Notifications) - Email notifications for inspection results and matching variances

### Package Dependencies
- **DEP-010**: lorisleiva/laravel-actions ^2.0 - Action and job pattern
- **DEP-011**: Laravel Queue system - Async inventory posting
- **DEP-012**: Laravel Events - Integration with SUB14 and SUB11

## 5. Files

### Models & Enums
- `packages/purchasing/src/Models/GoodsReceiptNote.php` - GRN header model
- `packages/purchasing/src/Models/GoodsReceiptNoteLine.php` - GRN line items model
- `packages/purchasing/src/Models/QualityInspectionChecklist.php` - Inspection criteria model
- `packages/purchasing/src/Models/ThreeWayMatch.php` - Three-way matching model
- `packages/purchasing/src/Enums/GRNInspectionStatus.php` - GRN inspection status enumeration
- `packages/purchasing/src/Enums/GRNStatus.php` - GRN status enumeration (draft/posted)
- `packages/purchasing/src/Enums/LineQualityStatus.php` - Line quality status enumeration
- `packages/purchasing/src/Enums/ThreeWayMatchStatus.php` - Match status enumeration

### Repositories & Contracts
- `packages/purchasing/src/Contracts/GoodsReceiptNoteRepositoryContract.php` - GRN repository interface
- `packages/purchasing/src/Repositories/GoodsReceiptNoteRepository.php` - GRN repository implementation
- `packages/purchasing/src/Contracts/ThreeWayMatchingServiceContract.php` - Matching service interface

### Services
- `packages/purchasing/src/Services/GRNCalculationService.php` - GRN calculations and PO fulfillment
- `packages/purchasing/src/Services/QualityInspectionService.php` - Inspection workflow logic
- `packages/purchasing/src/Services/POFulfillmentService.php` - PO fulfillment tracking
- `packages/purchasing/src/Services/ThreeWayMatchingService.php` - Three-way matching logic

### Actions
- `packages/purchasing/src/Actions/CreateGoodsReceiptAction.php` - Create GRN
- `packages/purchasing/src/Actions/UpdateGoodsReceiptAction.php` - Update GRN
- `packages/purchasing/src/Actions/DeleteGoodsReceiptAction.php` - Delete GRN
- `packages/purchasing/src/Actions/InspectGoodsReceiptLineAction.php` - Inspect line
- `packages/purchasing/src/Actions/CompleteGRNInspectionAction.php` - Complete inspection
- `packages/purchasing/src/Actions/RejectGoodsReceiptLineAction.php` - Reject line
- `packages/purchasing/src/Actions/AcceptGoodsReceiptLineAction.php` - Accept line
- `packages/purchasing/src/Actions/PostGoodsReceiptToInventoryAction.php` - Post to inventory
- `packages/purchasing/src/Actions/CreateBackOrderReportAction.php` - Back order report
- `packages/purchasing/src/Actions/PerformThreeWayMatchAction.php` - Perform match
- `packages/purchasing/src/Actions/ApproveThreeWayMatchAction.php` - Approve match
- `packages/purchasing/src/Actions/RejectThreeWayMatchAction.php` - Reject match

### Controllers & Requests
- `packages/purchasing/src/Http/Controllers/GoodsReceiptNoteController.php` - GRN API controller
- `packages/purchasing/src/Http/Controllers/ThreeWayMatchController.php` - Matching API controller
- `packages/purchasing/src/Http/Requests/StoreGoodsReceiptRequest.php` - GRN validation
- `packages/purchasing/src/Http/Requests/InspectGoodsReceiptLineRequest.php` - Inspection validation
- `packages/purchasing/src/Http/Requests/PerformThreeWayMatchRequest.php` - Match validation
- `packages/purchasing/src/Http/Requests/ApproveMatchRequest.php` - Match approval validation
- `packages/purchasing/src/Http/Requests/RejectMatchRequest.php` - Match rejection validation

### Resources
- `packages/purchasing/src/Http/Resources/GoodsReceiptNoteResource.php` - GRN transformation
- `packages/purchasing/src/Http/Resources/GoodsReceiptNoteLineResource.php` - Line transformation
- `packages/purchasing/src/Http/Resources/ThreeWayMatchResource.php` - Match transformation

### Events & Listeners
- `packages/purchasing/src/Events/GoodsReceiptCreatedEvent.php`
- `packages/purchasing/src/Events/GoodsReceiptUpdatedEvent.php`
- `packages/purchasing/src/Events/GoodsLineInspectedEvent.php`
- `packages/purchasing/src/Events/GoodsInspectionCompletedEvent.php`
- `packages/purchasing/src/Events/GoodsLineRejectedEvent.php`
- `packages/purchasing/src/Events/GoodsLineAcceptedEvent.php`
- `packages/purchasing/src/Events/GoodsReceivedEvent.php` (EV-PO-003)
- `packages/purchasing/src/Events/ThreeWayMatchCompletedEvent.php`
- `packages/purchasing/src/Events/ThreeWayMatchApprovedEvent.php`
- `packages/purchasing/src/Events/ThreeWayMatchRejectedEvent.php`
- `packages/purchasing/src/Listeners/UpdatePOFulfillmentListener.php`
- `packages/purchasing/src/Listeners/NotifyBuyerOnGoodsReceivedListener.php`
- `packages/purchasing/src/Listeners/NotifyAPOnMatchCompletedListener.php`
- `packages/purchasing/src/Listeners/UpdateInvoiceStatusListener.php`

### Observers & Policies
- `packages/purchasing/src/Observers/GoodsReceiptNoteObserver.php` - GRN model observer
- `packages/purchasing/src/Policies/GoodsReceiptNotePolicy.php` - GRN authorization
- `packages/purchasing/src/Policies/ThreeWayMatchPolicy.php` - Match authorization

### Database
- `packages/purchasing/database/migrations/2025_01_01_000010_create_goods_receipt_notes_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000011_create_goods_receipt_note_lines_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000012_create_quality_inspection_checklists_table.php`
- `packages/purchasing/database/migrations/2025_01_01_000013_create_three_way_matches_table.php`
- `packages/purchasing/database/factories/GoodsReceiptNoteFactory.php`
- `packages/purchasing/database/factories/GoodsReceiptNoteLineFactory.php`
- `packages/purchasing/database/factories/QualityInspectionChecklistFactory.php`
- `packages/purchasing/database/factories/ThreeWayMatchFactory.php`
- `packages/purchasing/database/seeders/GoodsReceiptSeeder.php`
- `packages/purchasing/database/seeders/QualityInspectionChecklistSeeder.php`

### Tests (Total: 85 tasks with testing components)
- `packages/purchasing/tests/Unit/Models/GoodsReceiptNoteTest.php`
- `packages/purchasing/tests/Unit/Models/ThreeWayMatchTest.php`
- `packages/purchasing/tests/Unit/Services/GRNCalculationServiceTest.php`
- `packages/purchasing/tests/Unit/Services/QualityInspectionServiceTest.php`
- `packages/purchasing/tests/Unit/Services/ThreeWayMatchingServiceTest.php`
- `packages/purchasing/tests/Feature/GoodsReceiptWorkflowTest.php`
- `packages/purchasing/tests/Feature/QualityInspectionTest.php`
- `packages/purchasing/tests/Feature/ThreeWayMatchingTest.php`
- `packages/purchasing/tests/Integration/InventoryIntegrationTest.php`
- `packages/purchasing/tests/Integration/APIntegrationTest.php`

## 6. Testing

### Unit Tests (25 tests)
- **TEST-001**: GoodsReceiptNote inspection_complete logic
- **TEST-002**: GoodsReceiptNote can_be_posted validation
- **TEST-003**: GRN line variance calculation
- **TEST-004**: ThreeWayMatch variance calculation
- **TEST-005**: GRNCalculationService quantity limits (BR-PO-003)
- **TEST-006**: QualityInspectionService checklist validation
- **TEST-007**: POFulfillmentService fulfillment percentage
- **TEST-008**: ThreeWayMatchingService variance tolerances
- **TEST-009**: All action classes with mocked dependencies

### Feature Tests (30 tests)
- **TEST-010**: Create goods receipt via API
- **TEST-011**: Cannot receive more than ordered (BR-PO-003)
- **TEST-012**: Quality inspection workflow (pending→passed/failed)
- **TEST-013**: Complete GRN inspection marks all lines
- **TEST-014**: Post GRN to inventory after inspection passes
- **TEST-015**: Cannot post GRN before inspection complete
- **TEST-016**: Partial receipt creates back orders (FR-PO-006)
- **TEST-017**: Reject goods receipt line with reason
- **TEST-018**: Accept goods receipt line
- **TEST-019**: Complete goods receipt workflow (create→inspect→post)
- **TEST-020**: PO fulfillment updated after posting
- **TEST-021**: Three-way match with exact match (no variance)
- **TEST-022**: Three-way match with acceptable variance (< 5%)
- **TEST-023**: Three-way match with variance requiring approval (5-10%)
- **TEST-024**: Three-way match failed (variance >= 10%)

### Integration Tests (15 tests)
- **TEST-025**: Inventory posting integration with SUB14 (mocked)
- **TEST-026**: Three-way matching integration with SUB11 AP (mocked)
- **TEST-027**: Back order reporting with PO data
- **TEST-028**: GRN event triggers inventory movement
- **TEST-029**: Three-way match event triggers invoice approval

### Business Rule Tests (5 tests)
- **TEST-030**: Cannot receive more than ordered (BR-PO-003)
- **TEST-031**: GRN must pass inspection before posting
- **TEST-032**: Three-way match variance tolerances enforced
- **TEST-033**: Only accepted quantity posts to inventory
- **TEST-034**: Batch/serial numbers recorded correctly (DR-PO-002)

### Acceptance Tests (10 tests)
- **TEST-035**: Complete procure-to-pay cycle (requisition→PO→GRN→match)
- **TEST-036**: Partial delivery handling functional (FR-PO-006)
- **TEST-037**: Quality inspection workflow operational
- **TEST-038**: Three-way matching with variance approval
- **TEST-039**: Inventory integration functional
- **TEST-040**: AP integration functional
- **TEST-041**: Back order tracking accurate
- **TEST-042**: Batch/serial tracking functional
- **TEST-043**: GRN audit trail complete (SR-PO-002)
- **TEST-044**: Three-way match event dispatched (EV-PO-003)

**Total Test Coverage:** 85 tests (25 unit + 30 feature + 15 integration + 5 business rule + 10 acceptance)

## 7. Risks & Assumptions

### Risks
- **RISK-001**: Inventory integration complexity may cause posting failures - Mitigation: comprehensive error handling, rollback on failure
- **RISK-002**: Three-way matching variance calculation may have edge cases - Mitigation: thorough testing with various scenarios
- **RISK-003**: Quality inspection checklist management may become complex - Mitigation: flexible JSONB structure, admin interface
- **RISK-004**: Concurrent GRN posting may cause inventory conflicts - Mitigation: database locking, transaction management
- **RISK-005**: AP integration delays may block invoice processing - Mitigation: async event processing, retry logic

### Assumptions
- **ASSUMPTION-001**: SUB14 Inventory provides InventoryMovementService API for posting
- **ASSUMPTION-002**: SUB11 AP provides VendorInvoice model and InvoiceApprovalService
- **ASSUMPTION-003**: Quality inspection is mandatory for all receipts (configurable per item)
- **ASSUMPTION-004**: Only accepted quantity posts to inventory (rejected quantity does not)
- **ASSUMPTION-005**: Three-way match tolerance is configurable (default 5% acceptable, 5-10% requires approval, >10% fails)
- **ASSUMPTION-006**: Batch/serial numbers are optional (not all items require tracking)
- **ASSUMPTION-007**: One GRN can only reference one PO (no consolidated receipts)

## 8. KIV for Future Implementations

- **KIV-001**: Consolidated goods receipts (multiple POs in one GRN)
- **KIV-002**: Return to vendor workflow (RTV)
- **KIV-003**: Advanced quality inspection with photos/attachments
- **KIV-004**: Barcode scanning for batch/serial numbers
- **KIV-005**: Configurable variance tolerance per vendor/item
- **KIV-006**: Auto-matching invoices to GRNs (without manual trigger)
- **KIV-007**: GRN templates for recurring receipts
- **KIV-008**: Mobile app for warehouse goods receipt
- **KIV-009**: Weighted average cost calculation on receipt
- **KIV-010**: Inspection sampling (inspect subset of received quantity)
- **KIV-011**: Certificate of analysis (COA) attachment for quality items
- **KIV-012**: Cross-docking (direct delivery to customer without storage)

## 9. Related PRD / Further Reading

- **Master PRD**: [../prd/PRD01-MVP.md](../prd/PRD01-MVP.md)
- **Sub-PRD**: [../prd/prd-01/PRD01-SUB16-PURCHASING.md](../prd/prd-01/PRD01-SUB16-PURCHASING.md)
- **Related Plans**:
  - PRD01-SUB16-PLAN01 (Vendor Management & Purchase Requisition) - Foundation
  - PRD01-SUB16-PLAN02 (Purchase Order Management & Approval) - PO creation and approval
  - PRD01-SUB16-PLAN04 (Vendor Performance & Reporting) - Next plan, vendor analytics
- **Integration Documentation**:
  - SUB14 (Inventory Management) - InventoryMovementService, Warehouse model
  - SUB11 (Accounts Payable) - VendorInvoice model, InvoiceApprovalService
  - SUB03 (Audit Logging) - Activity tracking (SR-PO-002)
- **Architecture Documentation**: [../../architecture/](../../architecture/)
- **Coding Guidelines**: [../../CODING_GUIDELINES.md](../../CODING_GUIDELINES.md)
