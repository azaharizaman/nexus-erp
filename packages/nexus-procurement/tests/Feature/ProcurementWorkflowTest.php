<?php

declare(strict_types=1);

use Nexus\Procurement\Models\PurchaseRequisition;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\VendorInvoice;
use Nexus\Procurement\Enums\RequisitionStatus;
use Nexus\Procurement\Enums\PurchaseOrderStatus;
use Nexus\Procurement\Enums\GoodsReceiptStatus;
use Nexus\Procurement\Enums\InvoiceStatus;
use Nexus\Procurement\Services\ThreeWayMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Procurement System Feature Tests
 *
 * Tests the complete procure-to-pay workflow implemented in Phase 1.
 */
describe('Procurement System', function () {

    it('completes the procure-to-pay workflow', function () {
        // Create test data directly
        $department = \Nexus\Backoffice\Models\Department::create([
            'name' => 'IT Department',
            'code' => 'IT',
            'description' => 'Information Technology Department',
            'company_id' => 1, // Assume company exists
            'is_active' => true,
        ]);

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'code' => 'TV001',
            'email' => 'vendor@example.com',
            'phone' => '123-456-7890',
            'address' => '123 Vendor St',
            'city' => 'Vendor City',
            'state' => 'VS',
            'postal_code' => '12345',
            'country' => 'USA',
            'is_active' => true,
        ]);

        // 1. Create purchase requisition
        $requisition = PurchaseRequisition::factory()->create([
            'department_id' => $department->id,
            'requested_by' => $user->id,
            'status' => RequisitionStatus::DRAFT,
        ]);

        $requisition->items()->create([
            'item_description' => 'Test Item',
            'quantity' => 10,
            'estimated_unit_price' => 100.00,
            'uom' => 'pcs',
        ]);

        expect($requisition->status)->toBe(RequisitionStatus::DRAFT);

        // 2. Submit requisition for approval
        $approvalService = app(\Nexus\Procurement\Services\RequisitionApprovalService::class);
        $approvalService->submitForApproval($requisition);

        expect($requisition->fresh()->status)->toBe(RequisitionStatus::PENDING_APPROVAL);

        // 3. Approve requisition
        $approvalService->approve($requisition);

        expect($requisition->fresh()->status)->toBe(RequisitionStatus::APPROVED);

        // 4. Create purchase order from requisition
        $poService = app(\Nexus\Procurement\Services\PurchaseOrderService::class);
        $purchaseOrder = $poService->createFromRequisition($requisition, $vendor);

        expect($purchaseOrder)->toBeInstanceOf(PurchaseOrder::class);
        expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::DRAFT);
        expect($requisition->fresh()->status)->toBe(RequisitionStatus::ORDERED);

        // 5. Approve and send PO to vendor
        $poService->approve($purchaseOrder);
        $poService->sendToVendor($purchaseOrder);

        expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::SENT_TO_VENDOR);

        // 6. Create goods receipt
        $grService = app(\Nexus\Procurement\Services\GoodsReceiptService::class);
        $receiptData = [
            'receipt_date' => now(),
            'items' => [
                $purchaseOrder->items->first()->id => [
                    'quantity_received' => 10,
                    'condition' => 'good',
                ],
            ],
        ];

        $goodsReceipt = $grService->createFromPurchaseOrder($purchaseOrder, $receiptData);
        $grService->confirm($goodsReceipt);

        expect($goodsReceipt)->toBeInstanceOf(GoodsReceiptNote::class);
        expect($goodsReceipt->status)->toBe(GoodsReceiptStatus::CONFIRMED);
        expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::RECEIVED);

        // 7. Create vendor invoice
        $invoice = VendorInvoice::factory()->create([
            'po_id' => $purchaseOrder->id,
            'grn_id' => $goodsReceipt->id,
            'vendor_id' => $vendor->id,
            'status' => InvoiceStatus::RECEIVED,
            'total_amount' => 1000.00, // Matches PO total
        ]);

        $invoice->items()->create([
            'po_item_id' => $purchaseOrder->items->first()->id,
            'quantity' => 10,
            'unit_price' => 100.00,
        ]);

        // 8. Perform three-way match
        $matchService = app(ThreeWayMatchService::class);
        $matchResult = $matchService->performMatch($invoice);

        expect($matchResult->match_status)->toBe(\Nexus\Procurement\Enums\MatchStatus::MATCHED->value);
        expect($matchService->canAuthorizePayment($matchResult))->toBeTrue();

        // 9. Complete workflow
        $poService->close($purchaseOrder);

        expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrderStatus::CLOSED);
    });

    it('handles three-way match variances correctly', function () {
        // Create test data directly
        $department = \Nexus\Backoffice\Models\Department::create([
            'name' => 'Finance Department',
            'code' => 'FIN',
            'description' => 'Finance Department',
            'company_id' => 1,
            'is_active' => true,
        ]);

        $user = \App\Models\User::create([
            'name' => 'Finance User',
            'email' => 'finance@example.com',
            'password' => bcrypt('password'),
        ]);

        $vendor = Vendor::create([
            'name' => 'Variance Vendor',
            'code' => 'VV001',
            'email' => 'variance@example.com',
            'phone' => '123-456-7890',
            'address' => '456 Variance St',
            'city' => 'Variance City',
            'state' => 'VS',
            'postal_code' => '12345',
            'country' => 'USA',
            'is_active' => true,
        ]);

        // Create approved PO and GRN
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO001',
            'requisition_id' => null,
            'vendor_id' => $vendor->id,
            'status' => 'received',
            'order_date' => now(),
            'total_amount' => 1000.00,
        ]);

        $purchaseOrder->items()->create([
            'requisition_item_id' => null,
            'item_description' => 'Test Item',
            'quantity' => 10,
            'unit_price' => 100.00,
            'total_price' => 1000.00,
            'uom' => 'pcs',
        ]);

        $goodsReceipt = GoodsReceiptNote::create([
            'grn_number' => 'GRN001',
            'po_id' => $purchaseOrder->id,
            'vendor_id' => $vendor->id,
            'status' => 'confirmed',
            'receipt_date' => now(),
            'received_by' => $user->id,
        ]);

        $goodsReceipt->items()->create([
            'po_item_id' => $purchaseOrder->items->first()->id,
            'quantity_ordered' => 10,
            'quantity_received' => 10,
            'unit_price' => 100.00,
            'condition' => 'good',
        ]);

        // Create invoice with variance (overcharged)
        $invoice = VendorInvoice::create([
            'invoice_number' => 'INV001',
            'po_id' => $purchaseOrder->id,
            'grn_id' => $goodsReceipt->id,
            'vendor_id' => $vendor->id,
            'status' => 'received',
            'invoice_date' => now(),
            'total_amount' => 1100.00, // 10% over PO amount
        ]);

        $invoice->items()->create([
            'po_item_id' => $purchaseOrder->items->first()->id,
            'quantity' => 10,
            'unit_price' => 110.00,
        ]);

        // Perform three-way match
        $matchService = app(ThreeWayMatchService::class);
        $matchResult = $matchService->performMatch($invoice);

        expect($matchResult->match_status)->toBe(\Nexus\Procurement\Enums\MatchStatus::VARIANCE->value);
        expect($matchResult->price_variance_pct)->toBe(10.0);
        expect($matchService->canAuthorizePayment($matchResult))->toBeFalse();
    });
});