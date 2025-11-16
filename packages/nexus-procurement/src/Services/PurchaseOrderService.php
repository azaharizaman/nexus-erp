<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Contracts\PurchaseOrderServiceContract;
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
class PurchaseOrderService implements PurchaseOrderServiceContract
{
    public function __construct(
        private SequencingService $sequencingService
    ) {}

    /**
     * Create a purchase order from approved requisition
     */
    public function createFromRequisition(string $requisitionId, array $additionalData = []): PurchaseOrder
    {
        $requisition = PurchaseRequisition::findOrFail($requisitionId);
        $vendor = Vendor::findOrFail($additionalData['vendor_id'] ?? $requisition->preferred_vendor_id);

        if ($requisition->status !== \Nexus\Procurement\Enums\RequisitionStatus::APPROVED) {
            throw new \InvalidArgumentException('Only approved requisitions can be converted to purchase orders.');
        }

        return DB::transaction(function () use ($requisition, $vendor, $additionalData) {
            // Generate PO number
            $poNumber = $this->sequencingService->generateNumber('purchase_order');

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'requisition_id' => $requisition->id,
                'vendor_id' => $vendor->id,
                'status' => PurchaseOrderStatus::DRAFT,
                'order_date' => now(),
                'expected_delivery_date' => $additionalData['expected_delivery_date'] ?? $requisition->expected_delivery_date,
                'currency' => $additionalData['currency'] ?? $requisition->currency ?? 'USD',
                'terms_and_conditions' => $additionalData['terms_and_conditions'] ?? $requisition->terms_and_conditions,
                'notes' => $additionalData['notes'] ?? $requisition->notes,
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
     * Approve purchase order
     */
    public function approve(string $id, string $approverId, string $comments = null): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderStatus::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Only pending purchase orders can be approved.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => $approverId,
            'approval_comments' => $comments,
        ]);

        return $purchaseOrder;
    }

    /**
     * Reject purchase order
     */
    public function reject(string $id, string $approverId, string $comments = null): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderStatus::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Only pending purchase orders can be rejected.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $approverId,
            'cancellation_reason' => $comments,
        ]);

        return $purchaseOrder;
    }

    /**
     * Send purchase order to vendor
     */
    public function sendToVendor(string $id): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderStatus::APPROVED) {
            throw new \InvalidArgumentException('Only approved purchase orders can be sent to vendor.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::SENT,
            'sent_to_vendor_at' => now(),
            'sent_to_vendor_by' => Auth::id(),
        ]);

        return $purchaseOrder;
    }

    /**
     * Update purchase order
     */
    public function update(string $id, array $data): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft purchase orders can be updated.');
        }

        $purchaseOrder->update($data);

        return $purchaseOrder;
    }

    /**
     * Amend purchase order
     */
    public function amend(string $id, array $amendmentData, string $reason): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (!in_array($purchaseOrder->status, [PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::SENT])) {
            throw new \InvalidArgumentException('Only approved or sent purchase orders can be amended.');
        }

        return DB::transaction(function () use ($purchaseOrder, $amendmentData, $reason) {
            // Create amendment record
            $amendment = $purchaseOrder->amendments()->create([
                'amendment_data' => $amendmentData,
                'reason' => $reason,
                'amended_by' => Auth::id(),
                'amended_at' => now(),
            ]);

            // Update purchase order with amendment data
            $purchaseOrder->update($amendmentData);

            return $purchaseOrder;
        });
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(PurchaseOrder $purchaseOrder, string $reason): void
    {
        if (in_array($purchaseOrder->status, [PurchaseOrderStatus::FULLY_RECEIVED, PurchaseOrderStatus::CLOSED])) {
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
     * Close purchase order
     */
    public function close(string $id, string $reason = null): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if (!$this->isFullyReceived($purchaseOrder)) {
            throw new \InvalidArgumentException('Purchase order cannot be closed until all items are received.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
            'closure_reason' => $reason,
        ]);

        return $purchaseOrder;
    }

    /**
     * Get purchase orders by vendor
     */
    public function getByVendor(string $vendorId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = PurchaseOrder::where('vendor_id', $vendorId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('order_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('order_date', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Get purchase orders by status
     */
    public function getByStatus(string $tenantId, string $status): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseOrder::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get();
    }

    /**
     * Get overdue purchase orders
     */
    public function getOverdue(string $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseOrder::where('tenant_id', $tenantId)
            ->where('expected_delivery_date', '<', now())
            ->whereNotIn('status', [PurchaseOrderStatus::CLOSED, PurchaseOrderStatus::CANCELLED])
            ->get();
    }

    /**
     * Calculate purchase order totals
     */
    public function calculateTotals(string $id): array
    {
        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);

        $subtotal = $purchaseOrder->items->sum('total_price');
        $taxAmount = ($subtotal * ($purchaseOrder->tax_rate ?? 0)) / 100;
        $discountAmount = ($subtotal * ($purchaseOrder->discount_rate ?? 0)) / 100;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Validate purchase order data
     */
    public function validateData(array $data): array
    {
        $errors = [];

        if (empty($data['vendor_id'])) {
            $errors[] = 'Vendor ID is required';
        }

        if (empty($data['expected_delivery_date'])) {
            $errors[] = 'Expected delivery date is required';
        }

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                if (empty($item['item_description'])) {
                    $errors[] = "Item {$index}: Description is required";
                }
                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    $errors[] = "Item {$index}: Valid quantity is required";
                }
                if (!isset($item['unit_price']) || $item['unit_price'] < 0) {
                    $errors[] = "Item {$index}: Valid unit price is required";
                }
            }
        }

        return $errors;
    }
}