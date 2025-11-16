# Nexus Procurement Service Integration Guide

## Overview

This guide provides technical details for developers integrating with Nexus Procurement domain services programmatically. The package exposes domain services that encapsulate complex business logic and can be injected or accessed directly.

## Service Architecture

### Domain Service Layer

Nexus Procurement implements a rich domain service layer that encapsulates business logic:

```
Domain Services (Business Logic)
├── RequisitionApprovalService     - Approval workflow management
├── ThreeWayMatchService          - Automated invoice matching
├── VendorPerformanceService      - Performance metric calculations
├── ProcurementAnalyticsService   - Business intelligence
├── PurchaseOrderService          - PO lifecycle management
├── GoodsReceiptService           - Receipt processing
├── RFQManagementService          - Competitive bidding
└── ContractManagementService     - Contract lifecycle
```

### Service Dependencies

Services follow dependency injection patterns:

```php
// Service registration in service provider
public function register()
{
    $this->app->singleton(RequisitionApprovalService::class, function ($app) {
        return new RequisitionApprovalService(
            $app->make(ApprovalMatrixRepository::class),
            $app->make(WorkflowEngine::class)
        );
    });
}
```

## Core Domain Services

### RequisitionApprovalService

**Purpose**: Manages the approval workflow for purchase requisitions based on configurable approval matrices.

**Key Methods**:

#### calculateApprovers(PurchaseRequisition $requisition): Collection
Calculates the required approvers based on requisition amount, department, and GL accounts.

```php
$service = app(RequisitionApprovalService::class);
$approvers = $service->calculateApprovers($requisition);

// Returns collection of approver requirements
[
    ['role' => 'manager', 'reason' => 'Department approval required'],
    ['role' => 'director', 'reason' => 'Amount exceeds manager limit']
]
```

#### routeForApproval(PurchaseRequisition $requisition): void
Routes the requisition through the approval workflow, creating approval tasks.

```php
$service->routeForApproval($requisition);
// Triggers workflow engine, sends notifications, creates audit trail
```

#### delegateApproval(Approval $approval, User $delegate): void
Allows approval delegation to another user.

```php
$service->delegateApproval($approval, $delegateUser);
// Updates approval assignment, sends notifications
```

#### checkApprovalStatus(PurchaseRequisition $requisition): ApprovalStatus
Returns current approval status with detailed information.

```php
$status = $service->checkApprovalStatus($requisition);
// Returns: approved, rejected, pending, escalated
```

### ThreeWayMatchService

**Purpose**: Performs automated 3-way matching between Purchase Orders, Goods Receipts, and Vendor Invoices.

**Key Methods**:

#### performMatch(VendorInvoice $invoice): ThreeWayMatchResult
Executes the complete 3-way matching process.

```php
$service = app(ThreeWayMatchService::class);
$result = $service->performMatch($invoice);

// Returns matching result with variances
$result->status;           // MatchStatus enum
$result->price_variance;   // Percentage difference
$result->quantity_variance; // Percentage difference
$result->auto_approved;    // Boolean
```

#### calculateVariances(VendorInvoice $invoice): array
Calculates price and quantity variances without full matching.

```php
$variances = $service->calculateVariances($invoice);
[
    'price_variance_percent' => 2.5,
    'quantity_variance_percent' => 0.0,
    'tolerance_exceeded' => false
]
```

#### evaluateTolerance(ThreeWayMatchResult $result): bool
Evaluates whether variances are within configured tolerance limits.

```php
$withinTolerance = $service->evaluateTolerance($result);
// Returns true if variances acceptable for auto-approval
```

#### getMatchingHistory(VendorInvoice $invoice): Collection
Retrieves historical matching attempts for audit purposes.

```php
$history = $service->getMatchingHistory($invoice);
// Returns collection of previous match attempts
```

### VendorPerformanceService

**Purpose**: Calculates and tracks vendor performance metrics for procurement intelligence.

**Key Methods**:

#### calculatePerformanceMetrics(Vendor $vendor, Period $period): array
Calculates comprehensive performance metrics for a vendor.

```php
$service = app(VendorPerformanceService::class);
$metrics = $service->calculatePerformanceMetrics($vendor, $period);

// Returns performance data
[
    'on_time_delivery_rate' => 95.2,
    'quality_acceptance_rate' => 98.1,
    'average_lead_time_days' => 12.5,
    'price_competitiveness_score' => 4.2,
    'overall_rating' => 4.5
]
```

#### updatePerformanceScore(Vendor $vendor): void
Updates the vendor's performance score based on recent activity.

```php
$service->updatePerformanceScore($vendor);
// Recalculates and persists performance metrics
```

#### getPerformanceTrend(Vendor $vendor, int $months): array
Analyzes performance trends over time.

```php
$trend = $service->getPerformanceTrend($vendor, 6);
// Returns trend analysis data
```

#### identifyUnderperformers(Collection $vendors, array $thresholds): Collection
Identifies vendors not meeting performance thresholds.

```php
$underperformers = $service->identifyUnderperformers($vendors, [
    'on_time_delivery' => 90.0,
    'quality_acceptance' => 95.0
]);
```

### ProcurementAnalyticsService

**Purpose**: Provides business intelligence and analytics for procurement operations.

**Key Methods**:

#### calculateSpendAnalytics(Period $period, array $filters): array
Generates comprehensive spend analysis reports.

```php
$service = app(ProcurementAnalyticsService::class);
$analytics = $service->calculateSpendAnalytics($period, [
    'department_id' => $department->id,
    'category' => 'IT'
]);

// Returns spend analysis
[
    'total_spend' => 1250000.00,
    'spend_by_category' => [...],
    'spend_by_vendor' => [...],
    'budget_variance' => 4.17,
    'trends' => [...]
]
```

#### generateEfficiencyReport(Department $department, Period $period): array
Analyzes procurement process efficiency metrics.

```php
$report = $service->generateEfficiencyReport($department, $period);

// Returns efficiency metrics
[
    'average_approval_time_days' => 2.3,
    'average_cycle_time_days' => 5.1,
    'on_time_delivery_rate' => 94.5,
    'first_pass_yield' => 87.3
]
```

#### trackComplianceMetrics(Period $period): array
Monitors compliance with procurement policies.

```php
$compliance = $service->trackComplianceMetrics($period);

// Returns compliance data
[
    'separation_of_duties_violations' => 2,
    'approval_bypass_incidents' => 0,
    'policy_adherence_rate' => 98.5
]
```

## Operational Services

### PurchaseOrderService

**Purpose**: Manages the complete purchase order lifecycle from creation to closure.

**Key Methods**:

#### createFromRequisition(PurchaseRequisition $requisition, Vendor $vendor): PurchaseOrder
Creates a PO from an approved requisition.

```php
$service = app(PurchaseOrderService::class);
$po = $service->createFromRequisition($requisition, $vendor);

// Returns fully configured purchase order
```

#### amendPurchaseOrder(PurchaseOrder $po, array $changes, string $reason): PurchaseOrder
Creates an amendment to an existing PO.

```php
$amendedPO = $service->amendPurchaseOrder($po, [
    'delivery_date' => '2024-12-15',
    'items' => $itemChanges
], 'Extended delivery required');
```

#### calculateTotals(PurchaseOrder $po): array
Recalculates PO totals including taxes and discounts.

```php
$totals = $service->calculateTotals($po);
// Returns ['subtotal', 'tax_amount', 'total_amount']
```

#### validatePurchaseOrder(PurchaseOrder $po): ValidationResult
Validates PO business rules and constraints.

```php
$result = $service->validatePurchaseOrder($po);
if (!$result->isValid()) {
    throw new DomainException($result->getErrors());
}
```

### GoodsReceiptService

**Purpose**: Handles goods receipt processing and inventory integration.

**Key Methods**:

#### processReceipt(array $receiptData): GoodsReceiptNote
Creates and processes a goods receipt.

```php
$service = app(GoodsReceiptService::class);
$grn = $service->processReceipt([
    'po_id' => $po->id,
    'items' => $receivedItems,
    'warehouse_id' => $warehouse->id
]);
```

#### performQualityInspection(GoodsReceiptNote $grn, array $inspectionData): void
Records quality inspection results.

```php
$service->performQualityInspection($grn, [
    'inspector_id' => $user->id,
    'results' => $inspectionResults
]);
```

#### updateInventory(GoodsReceiptNote $grn): void
Updates inventory levels based on receipt.

```php
$service->updateInventory($grn);
// Integrates with inventory system
```

### RFQManagementService

**Purpose**: Manages the complete Request for Quotation process.

**Key Methods**:

#### createRFQ(array $rfqData): RequestForQuotation
Creates and publishes an RFQ.

```php
$service = app(RFQManagementService::class);
$rfq = $service->createRFQ([
    'requisition_id' => $requisition->id,
    'vendors' => $invitedVendors,
    'deadline' => $deadline
]);
```

#### evaluateQuotes(RequestForQuotation $rfq): array
Evaluates submitted quotes and provides recommendations.

```php
$evaluation = $service->evaluateQuotes($rfq);
// Returns evaluation results with recommendations
```

#### selectWinner(RequestForQuotation $rfq, VendorQuote $winningQuote): PurchaseOrder
Selects the winning quote and creates a PO.

```php
$po = $service->selectWinner($rfq, $winningQuote);
// Automatically creates PO from winning quote
```

### ContractManagementService

**Purpose**: Manages procurement contract lifecycle and compliance.

**Key Methods**:

#### createContract(array $contractData): ProcurementContract
Creates a new procurement contract.

```php
$service = app(ContractManagementService::class);
$contract = $service->createContract([
    'vendor_id' => $vendor->id,
    'items' => $contractItems,
    'value' => 50000.00
]);
```

#### amendContract(ProcurementContract $contract, array $changes): ProcurementContract
Creates a contract amendment.

```php
$amendedContract = $service->amendContract($contract, [
    'value' => 55000.00,
    'items' => $itemChanges
]);
```

#### validateCompliance(ProcurementContract $contract): ComplianceResult
Validates contract compliance and identifies issues.

```php
$compliance = $service->validateCompliance($contract);
// Returns compliance status and issues
```

## Service Integration Patterns

### Dependency Injection

Services are designed for dependency injection:

```php
// In controller
public function __construct(
    PurchaseOrderService $poService,
    ThreeWayMatchService $matchService
) {
    $this->poService = $poService;
    $this->matchService = $matchService;
}

// In service
public function processInvoice(VendorInvoice $invoice)
{
    // Use injected services
    $matchResult = $this->matchService->performMatch($invoice);

    if ($matchResult->isAutoApproved()) {
        return $this->poService->markInvoicePaid($invoice);
    }
}
```

### Event-Driven Integration

Services emit domain events for loose coupling:

```php
// In service method
public function approveRequisition(PurchaseRequisition $requisition)
{
    $requisition->approve();
    $requisition->save();

    // Emit domain event
    event(new PurchaseRequisitionApproved($requisition));
}

// Event listener
class HandleRequisitionApproval
{
    public function handle(PurchaseRequisitionApproved $event)
    {
        // Trigger external integrations
        AccountingService::createAccrual($event->requisition);
        NotificationService::sendApprovalNotification($event->requisition);
    }
}
```

### Repository Pattern Integration

Services use repositories for data access:

```php
class PurchaseOrderService
{
    public function __construct(
        PurchaseOrderRepository $poRepository,
        VendorRepository $vendorRepository
    ) {
        $this->poRepository = $poRepository;
        $this->vendorRepository = $vendorRepository;
    }

    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        $vendor = $this->vendorRepository->findActive($data['vendor_id']);

        $po = new PurchaseOrder($data);
        $po->vendor()->associate($vendor);

        return $this->poRepository->save($po);
    }
}
```

## Service Configuration

### Service Parameters

Services accept configuration through constructor injection:

```php
// Service registration with configuration
$this->app->singleton(ThreeWayMatchService::class, function ($app) {
    $config = config('procurement.matching');

    return new ThreeWayMatchService(
        $config['price_tolerance_percent'],
        $config['quantity_tolerance_percent'],
        $config['auto_approve_enabled']
    );
});
```

### Runtime Configuration

Services can be configured at runtime:

```php
$service = app(RequisitionApprovalService::class);
$service->setEscalationDays(5);
$service->enableAutoApproval(true);
```

## Error Handling

### Domain Exceptions

Services throw specific domain exceptions:

```php
class RequisitionApprovalService
{
    public function approveRequisition(PurchaseRequisition $requisition, User $approver)
    {
        if (!$this->canApprove($requisition, $approver)) {
            throw new ApprovalNotAuthorizedException(
                "User {$approver->id} is not authorized to approve requisition {$requisition->id}"
            );
        }

        // Approval logic...
    }
}
```

### Validation Results

Services return validation results for complex validations:

```php
class ValidationResult
{
    private array $errors = [];
    private array $warnings = [];

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

## Testing Services

### Unit Testing

Test services in isolation with mocked dependencies:

```php
class RequisitionApprovalServiceTest extends TestCase
{
    public function test_calculates_correct_approvers_for_high_value_requisition()
    {
        // Arrange
        $requisition = new PurchaseRequisition(['total_amount' => 10000]);
        $mockRepository = Mockery::mock(ApprovalMatrixRepository::class);
        $mockRepository->shouldReceive('getRulesForAmount')
                      ->andReturn(['director', 'cfo']);

        $service = new RequisitionApprovalService($mockRepository);

        // Act
        $approvers = $service->calculateApprovers($requisition);

        // Assert
        $this->assertContains('director', $approvers);
        $this->assertContains('cfo', $approvers);
    }
}
```

### Integration Testing

Test service interactions with real dependencies:

```php
class ProcurementWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_complete_procurement_flow()
    {
        // Create requisition
        $requisition = RequisitionFactory::createApproved();

        // Convert to PO
        $poService = app(PurchaseOrderService::class);
        $po = $poService->createFromRequisition($requisition, $vendor);

        // Process receipt
        $receiptService = app(GoodsReceiptService::class);
        $grn = $receiptService->processReceipt([
            'po_id' => $po->id,
            'items' => $receivedItems
        ]);

        // Match invoice
        $matchService = app(ThreeWayMatchService::class);
        $result = $matchService->performMatch($invoice);

        $this->assertEquals(MatchStatus::MATCHED, $result->status);
    }
}
```

## Performance Considerations

### Service Caching

Implement caching for expensive operations:

```php
class VendorPerformanceService
{
    public function calculatePerformanceMetrics(Vendor $vendor, Period $period)
    {
        $cacheKey = "vendor.{$vendor->id}.performance.{$period->toString()}";

        return Cache::remember($cacheKey, 3600, function () use ($vendor, $period) {
            // Expensive calculation logic
            return $this->performCalculation($vendor, $period);
        });
    }
}
```

### Background Processing

Use queues for long-running operations:

```php
class ProcurementAnalyticsService
{
    public function generateEfficiencyReport(Department $department, Period $period)
    {
        // Queue the heavy calculation
        GenerateEfficiencyReport::dispatch($department, $period)
                               ->onQueue('analytics');

        return ['status' => 'queued', 'job_id' => $jobId];
    }
}
```

### Bulk Operations

Implement bulk operations for efficiency:

```php
class VendorPerformanceService
{
    public function bulkUpdatePerformanceScores(Collection $vendors): void
    {
        $vendors->chunk(50)->each(function ($chunk) {
            // Process chunk of vendors
            $chunk->each(function ($vendor) {
                $this->updatePerformanceScore($vendor);
            });

            // Commit chunk
            DB::commit();
        });
    }
}
```

This service integration guide provides the technical foundation for integrating with Nexus Procurement domain services programmatically.