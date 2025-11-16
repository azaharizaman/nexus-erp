# Nexus Procurement Domain Model

## Overview

This document provides a comprehensive technical overview of the Nexus Procurement domain model, designed for developers and system integrators implementing procurement solutions.

## Core Domain Entities

### PurchaseRequisition

**Purpose**: Initiates the procurement process by capturing business requirements and routing for approval.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `requisition_number`: Auto-generated sequential number
- `tenant_id`: Multi-tenant isolation
- `department_id`: Requesting department
- `requester_id`: User initiating request
- `justification`: Business case for purchase
- `required_date`: When items are needed
- `status`: Current approval state
- `total_amount`: Sum of all line items
- `currency`: Transaction currency

**Business Logic**:
- Approval workflow routing based on amount and department
- Budget availability checking
- Automatic conversion to PO upon approval
- Audit trail of all changes

**Relationships**:
- `belongsTo` Department
- `belongsTo` User (requester)
- `hasMany` PurchaseRequisitionItem
- `morphMany` Approval (via workflow integration)

**Domain Events**:
- `PurchaseRequisitionCreated`
- `PurchaseRequisitionApproved`
- `PurchaseRequisitionRejected`
- `PurchaseRequisitionConverted`

### PurchaseOrder

**Purpose**: Authoritative commitment to purchase from a vendor with legally binding terms.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `po_number`: Auto-generated sequential number
- `tenant_id`: Multi-tenant isolation
- `requisition_id`: Source requisition (optional)
- `vendor_id`: Selected supplier
- `status`: PO lifecycle state
- `order_date`: When PO was issued
- `delivery_date`: Expected delivery
- `payment_terms`: Net 30, etc.
- `shipping_terms`: FOB, CIF, etc.
- `total_amount`: PO value including taxes
- `tax_amount`: Total tax calculated
- `currency`: Transaction currency

**Business Logic**:
- Amendment tracking with version control
- Partial receipt handling
- Automatic closure when fully received
- Integration with accounting for accrual

**Relationships**:
- `belongsTo` PurchaseRequisition
- `belongsTo` Vendor
- `hasMany` PurchaseOrderItem
- `hasMany` GoodsReceiptNote
- `hasMany` VendorInvoice
- `hasMany` POAmendment

**Domain Events**:
- `PurchaseOrderCreated`
- `PurchaseOrderApproved`
- `PurchaseOrderSentToVendor`
- `PurchaseOrderAmended`
- `PurchaseOrderClosed`

### GoodsReceiptNote

**Purpose**: Documents the physical receipt of goods against a purchase order.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `grn_number`: Auto-generated sequential number
- `tenant_id`: Multi-tenant isolation
- `po_id`: Referenced purchase order
- `receiver_id`: User performing receipt
- `receipt_date`: When goods were received
- `warehouse_id`: Receiving location
- `carrier_name`: Delivery carrier
- `delivery_note_number`: Carrier's reference
- `status`: Receipt processing state

**Business Logic**:
- Quality inspection integration
- Partial receipt handling
- Automatic inventory updates
- 3-way matching trigger

**Relationships**:
- `belongsTo` PurchaseOrder
- `belongsTo` Warehouse
- `belongsTo` User (receiver)
- `hasMany` GoodsReceiptItem

**Domain Events**:
- `GoodsReceiptCreated`
- `GoodsReceiptProcessed`
- `GoodsReceiptQualityChecked`

### VendorInvoice

**Purpose**: Vendor billing document requiring 3-way matching validation.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `invoice_number`: Vendor's invoice number
- `tenant_id`: Multi-tenant isolation
- `po_id`: Referenced purchase order
- `vendor_id`: Billing vendor
- `invoice_date`: Invoice issue date
- `due_date`: Payment due date
- `status`: Invoice processing state
- `invoice_amount`: Total billed amount
- `tax_amount`: Tax component
- `currency`: Billing currency

**Business Logic**:
- 3-way matching automation
- Tolerance-based auto-approval
- Variance analysis and reporting
- Payment authorization workflow

**Relationships**:
- `belongsTo` PurchaseOrder
- `belongsTo` Vendor
- `hasMany` VendorInvoiceItem
- `hasOne` ThreeWayMatchResult

**Domain Events**:
- `VendorInvoiceReceived`
- `VendorInvoiceMatched`
- `VendorInvoiceApproved`
- `VendorInvoicePaid`

## Advanced Domain Entities

### RequestForQuotation

**Purpose**: Manages competitive bidding processes for vendor selection.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `rfq_number`: Auto-generated sequential number
- `tenant_id`: Multi-tenant isolation
- `requisition_id`: Source requisition
- `title`: RFQ description
- `description`: Detailed requirements
- `submission_deadline`: Quote due date
- `status`: RFQ lifecycle state
- `evaluation_criteria`: Scoring methodology

**Business Logic**:
- Automated vendor invitation
- Quote evaluation algorithms
- Winner selection and notification
- Audit trail of evaluation process

**Relationships**:
- `belongsTo` PurchaseRequisition
- `hasMany` RFQItem
- `hasMany` VendorQuote

**Domain Events**:
- `RFQCreated`
- `RFQPublished`
- `RFQClosed`
- `RFQWinnerSelected`

### ProcurementContract

**Purpose**: Formal vendor agreements with terms and conditions.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `contract_number`: Auto-generated sequential number
- `tenant_id`: Multi-tenant isolation
- `vendor_id`: Contract vendor
- `contract_type`: Framework, Master, etc.
- `title`: Contract description
- `start_date`: Contract effective date
- `end_date`: Contract expiration
- `status`: Contract lifecycle state
- `value`: Total contract value
- `currency`: Contract currency

**Business Logic**:
- Amendment tracking with approval
- Compliance monitoring
- Automatic renewal notifications
- Spend tracking against contract

**Relationships**:
- `belongsTo` Vendor
- `hasMany` ProcurementContractItem
- `hasMany` ContractAmendment
- `hasMany` BlanketPurchaseOrder

**Domain Events**:
- `ProcurementContractCreated`
- `ProcurementContractActivated`
- `ProcurementContractAmended`
- `ProcurementContractExpired`

### BlanketPurchaseOrder

**Purpose**: Long-term purchase commitments with release-based consumption.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `bpo_number`: Auto-generated sequential number
- `tenant_id`: Multi-tenant isolation
- `contract_id`: Parent contract
- `vendor_id`: Supplying vendor
- `start_date`: BPO effective date
- `end_date`: BPO expiration
- `total_value`: Maximum commitment value
- `consumed_value`: Amount released to date
- `status`: BPO lifecycle state

**Business Logic**:
- Release order generation
- Budget commitment tracking
- Automatic expiration handling
- Contract compliance validation

**Relationships**:
- `belongsTo` ProcurementContract
- `belongsTo` Vendor
- `hasMany` BlanketPurchaseOrderItem
- `hasMany` BlanketPORelease

**Domain Events**:
- `BlanketPurchaseOrderCreated`
- `BlanketPOReleaseCreated`
- `BlanketPurchaseOrderExpired`

### Vendor

**Purpose**: Supplier master data with performance tracking.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `tenant_id`: Multi-tenant isolation
- `vendor_number`: Auto-generated sequential number
- `name`: Legal entity name
- `tax_id`: Tax identification number
- `payment_terms`: Default payment terms
- `status`: Active, Inactive, Blacklisted
- `rating`: Performance score (1-5)
- `total_spend`: Lifetime purchase value
- `on_time_delivery_rate`: Performance metric

**Business Logic**:
- Performance score calculation
- Risk assessment algorithms
- Payment term negotiation
- Compliance monitoring

**Relationships**:
- `hasMany` PurchaseOrder
- `hasMany` VendorInvoice
- `hasMany` ProcurementContract
- `hasMany` VendorPerformanceMetric
- `hasMany` VendorUser (portal access)

**Domain Events**:
- `VendorCreated`
- `VendorPerformanceCalculated`
- `VendorStatusChanged`

## Supporting Entities

### ThreeWayMatchResult

**Purpose**: Records the automated reconciliation of PO, GRN, and Invoice.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `invoice_id`: Referenced invoice
- `match_status`: Matched, Quantity Variance, Price Variance, Unmatched
- `po_amount`: Purchase order value
- `receipt_amount`: Goods receipt value
- `invoice_amount`: Invoice billed amount
- `price_variance_percent`: Price difference percentage
- `quantity_variance_percent`: Quantity difference percentage
- `auto_approved`: Whether automatically approved

**Business Logic**:
- Automated matching algorithms
- Tolerance threshold evaluation
- Variance analysis reporting
- Approval workflow triggering

### VendorUser

**Purpose**: External vendor portal authentication and authorization.

**Key Attributes**:
- `id`: Unique identifier (UUID)
- `tenant_id`: Multi-tenant isolation
- `vendor_id`: Parent vendor organization
- `email`: Login email address
- `name`: User's full name
- `role`: Portal access level
- `status`: Active, Inactive, Pending
- `last_login_at`: Last portal access

**Business Logic**:
- Multi-vendor user management
- Role-based portal access
- Password reset workflows
- Session management

## Domain Services

### RequisitionApprovalService

**Responsibilities**:
- Evaluates approval matrix rules
- Routes requisitions to appropriate approvers
- Handles approval delegation and escalation
- Manages approval hierarchies

**Key Methods**:
- `routeForApproval(PurchaseRequisition $requisition)`
- `calculateApprovalMatrix(PurchaseRequisition $requisition)`
- `delegateApproval(Approval $approval, User $delegate)`

### ThreeWayMatchService

**Responsibilities**:
- Performs automated 3-way matching
- Calculates variance percentages
- Applies tolerance rules
- Generates match reports

**Key Methods**:
- `performMatch(VendorInvoice $invoice)`
- `calculateVariances(VendorInvoice $invoice)`
- `evaluateTolerance(ThreeWayMatchResult $result)`

### VendorPerformanceService

**Responsibilities**:
- Calculates vendor performance metrics
- Updates performance scores
- Generates performance reports
- Identifies underperforming vendors

**Key Methods**:
- `calculatePerformanceMetrics(Vendor $vendor)`
- `updatePerformanceScore(Vendor $vendor)`
- `generatePerformanceReport(Vendor $vendor, Period $period)`

### ProcurementAnalyticsService

**Responsibilities**:
- Generates procurement intelligence
- Calculates spend analytics
- Tracks process efficiency metrics
- Provides reporting data

**Key Methods**:
- `calculateSpendAnalytics(Period $period)`
- `generateEfficiencyReport(Department $department)`
- `trackComplianceMetrics(Period $period)`

## Integration Patterns

### Event-Driven Integration

The package emits domain events for loose coupling:

```php
// Listen for procurement events
Event::listen(PurchaseOrderCreated::class, function ($event) {
    // Integrate with external systems
    AccountingService::createAccrual($event->purchaseOrder);
    InventoryService::reserveStock($event->purchaseOrder);
});

// Handle vendor invoice matching
Event::listen(VendorInvoiceMatched::class, function ($event) {
    // Trigger payment workflow
    PaymentService::initiateApproval($event->invoice);
});
```

### API Integration

RESTful APIs for external system integration:

```php
// Create purchase order programmatically
$po = app(PurchaseOrderService::class)->createFromRequisition(
    $requisition,
    $vendor,
    $items
);

// Submit vendor invoice
$invoice = app(VendorInvoiceService::class)->create([
    'po_id' => $po->id,
    'vendor_id' => $vendor->id,
    'amount' => 1500.00,
    'items' => $invoiceItems
]);
```

### Repository Pattern

Data access abstraction for testability:

```php
// Inject repository in service
public function __construct(
    PurchaseOrderRepository $poRepository,
    VendorRepository $vendorRepository
) {
    $this->poRepository = $poRepository;
    $this->vendorRepository = $vendorRepository;
}

// Use repository methods
$pos = $this->poRepository->findByVendorAndStatus($vendorId, 'approved');
```

## Configuration Management

### Approval Matrix Configuration

```php
// Configure approval rules
'approval_matrix' => [
    'rules' => [
        [
            'condition' => 'amount <= 5000',
            'approver_role' => 'manager',
            'department_specific' => false
        ],
        [
            'condition' => 'amount > 5000 AND amount <= 50000',
            'approver_role' => 'director',
            'department_specific' => true
        ]
    ]
]
```

### 3-Way Matching Configuration

```php
'matching' => [
    'tolerance' => [
        'price_percent' => 5.0,
        'quantity_percent' => 2.0
    ],
    'auto_approve' => [
        'enabled' => true,
        'max_variance_percent' => 10.0
    ]
]
```

### Separation of Duties Configuration

```php
'separation_of_duties' => [
    'rules' => [
        'requester_cannot_approve' => true,
        'creator_cannot_receive' => true,
        'single_user_complete_cycle' => false
    ]
]
```

## Performance Considerations

### Database Optimization

- **Tenant-scoped indexes**: All queries filtered by tenant_id
- **Status-based indexes**: Frequent status filtering operations
- **Date range indexes**: Analytics queries by date ranges
- **Foreign key indexes**: Relationship traversal optimization

### Caching Strategy

- **Reference data**: Vendors, approval matrices cached for performance
- **Computed values**: Performance metrics cached with TTL
- **Query results**: Analytics queries cached for dashboard performance

### Background Processing

- **Heavy calculations**: Performance metrics calculated asynchronously
- **Email notifications**: Sent via queued jobs
- **Integration calls**: External system calls queued for reliability

## Testing Strategy

### Unit Tests

Domain logic testing with mocked dependencies:

```php
public function test_requisition_approval_routing()
{
    $requisition = new PurchaseRequisition(['total_amount' => 10000]);
    $service = new RequisitionApprovalService();

    $approvers = $service->calculateApprovers($requisition);

    $this->assertContains('director', $approvers->pluck('role'));
}
```

### Integration Tests

End-to-end workflow testing:

```php
public function test_complete_procurement_flow()
{
    // Create requisition
    $requisition = RequisitionFactory::createApproved();

    // Convert to PO
    $po = app(PurchaseOrderService::class)->createFromRequisition($requisition);

    // Receive goods
    $grn = app(GoodsReceiptService::class)->processReceipt($po, $items);

    // Match invoice
    $invoice = InvoiceFactory::createForPO($po);
    $match = app(ThreeWayMatchService::class)->performMatch($invoice);

    $this->assertEquals(MatchStatus::MATCHED, $match->status);
}
```

### Domain-Driven Testing

Test business rules and invariants:

```php
public function test_po_cannot_be_amended_after_partial_receipt()
{
    $po = PurchaseOrderFactory::createWithPartialReceipt();

    $this->expectException(DomainException::class);
    $po->amendItems($newItems);
}
```

This domain model provides a solid foundation for implementing comprehensive procurement solutions with proper separation of concerns, business rule enforcement, and integration capabilities.