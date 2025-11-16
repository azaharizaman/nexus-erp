<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\PurchaseRequisition;
use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Enums\PurchaseOrderStatus;
use Nexus\Sequencing\Services\SequencingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Purchase Order Service
 *
 * Handles creation and management of purchase orders.
 */
class PurchaseOrderService
{
    public function __construct(
        private SequencingService $sequencingService
    ) {}

    /**
     * Create purchase order from approved requisition.
     */
    public function createFromRequisition(PurchaseRequisition $requisition, Vendor $vendor): PurchaseOrder
    {
        if ($requisition->status !== \Nexus\Procurement\Enums\RequisitionStatus::APPROVED) {
            throw new \InvalidArgumentException('Only approved requisitions can be converted to purchase orders.');
        }

        return DB::transaction(function () use ($requisition, $vendor) {
            // Generate PO number
            $poNumber = $this->sequencingService->generateNumber('purchase_order');

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'requisition_id' => $requisition->id,
                'vendor_id' => $vendor->id,
                'status' => PurchaseOrderStatus::DRAFT,
                'order_date' => now(),
                'expected_delivery_date' => $requisition->expected_delivery_date,
                'currency' => $requisition->currency ?? 'USD',
                'terms_and_conditions' => $requisition->terms_and_conditions,
                'notes' => $requisition->notes,
                'created_by' => Auth::id(),
            ]);

            // Create PO items from requisition items
            foreach ($requisition->items as $requisitionItem) {
                $purchaseOrder->items()->create([
                    'requisition_item_id' => $requisitionItem->id,
                    'item_description' => $requisitionItem->item_description,
                    'quantity' => $requisitionItem->quantity,
                    'unit_price' => $requisitionItem->estimated_unit_price,
                    'total_price' => $requisitionItem->quantity * $requisitionItem->estimated_unit_price,
                    'uom' => $requisitionItem->uom,
                    'specifications' => $requisitionItem->specifications,
                ]);
            }

            // Update requisition status
            $requisition->update([
                'status' => \Nexus\Procurement\Enums\RequisitionStatus::ORDERED,
                'ordered_at' => now(),
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Submit purchase order for approval.
     */
    public function submitForApproval(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft purchase orders can be submitted for approval.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::PENDING_APPROVAL,
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
        ]);
    }

    /**
     * Approve purchase order.
     */
    public function approve(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Only pending purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);
    }

    /**
     * Send purchase order to vendor.
     */
    public function sendToVendor(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::APPROVED) {
            throw new \InvalidArgumentException('Only approved purchase orders can be sent to vendor.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::SENT_TO_VENDOR,
            'sent_to_vendor_at' => now(),
            'sent_to_vendor_by' => Auth::id(),
        ]);
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(PurchaseOrder $purchaseOrder, string $reason): void
    {
        if (in_array($purchaseOrder->status, [PurchaseOrderStatus::RECEIVED, PurchaseOrderStatus::CLOSED])) {
            throw new \InvalidArgumentException('Received or closed purchase orders cannot be cancelled.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
            'cancellation_reason' => $reason,
        ]);

        // Update requisition status back if applicable
        if ($purchaseOrder->requisition) {
            $purchaseOrder->requisition->update([
                'status' => \Nexus\Procurement\Enums\RequisitionStatus::APPROVED,
            ]);
        }
    }

    /**
     * Update purchase order totals.
     */
    public function updateTotals(PurchaseOrder $purchaseOrder): void
    {
        $subtotal = $purchaseOrder->items->sum('total_price');
        $taxAmount = ($subtotal * ($purchaseOrder->tax_rate ?? 0)) / 100;
        $discountAmount = ($subtotal * ($purchaseOrder->discount_rate ?? 0)) / 100;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Check if purchase order is fully received.
     */
    public function isFullyReceived(PurchaseOrder $purchaseOrder): bool
    {
        foreach ($purchaseOrder->items as $item) {
            $receivedQuantity = $purchaseOrder->goodsReceiptNotes
                ->flatMap->items
                ->where('po_item_id', $item->id)
                ->sum('quantity_received');

            if ($receivedQuantity < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Close purchase order.
     */
    public function close(PurchaseOrder $purchaseOrder): void
    {
        if (!$this->isFullyReceived($purchaseOrder)) {
            throw new \InvalidArgumentException('Purchase order cannot be closed until all items are received.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
        ]);
    }
}