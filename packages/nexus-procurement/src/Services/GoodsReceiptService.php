<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Enums\GoodsReceiptStatus;
use Nexus\Sequencing\Services\SequencingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Goods Receipt Service
 *
 * Handles goods receipt note creation and processing.
 */
class GoodsReceiptService
{
    public function __construct(
        private SequencingService $sequencingService
    ) {}

    /**
     * Create goods receipt note from purchase order.
     */
    public function createFromPurchaseOrder(PurchaseOrder $purchaseOrder, array $receiptData): GoodsReceiptNote
    {
        if (!in_array($purchaseOrder->status, [
            \Nexus\Procurement\Enums\PurchaseOrderStatus::SENT_TO_VENDOR,
            \Nexus\Procurement\Enums\PurchaseOrderStatus::PARTIALLY_RECEIVED
        ])) {
            throw new \InvalidArgumentException('Purchase order must be sent to vendor or partially received.');
        }

        return DB::transaction(function () use ($purchaseOrder, $receiptData) {
            // Generate GRN number
            $grnNumber = $this->sequencingService->generateNumber('goods_receipt_note');

            // Create goods receipt note
            $grn = GoodsReceiptNote::create([
                'grn_number' => $grnNumber,
                'po_id' => $purchaseOrder->id,
                'vendor_id' => $purchaseOrder->vendor_id,
                'status' => GoodsReceiptStatus::DRAFT,
                'receipt_date' => $receiptData['receipt_date'] ?? now(),
                'received_by' => Auth::id(),
                'carrier_name' => $receiptData['carrier_name'] ?? null,
                'tracking_number' => $receiptData['tracking_number'] ?? null,
                'delivery_note_number' => $receiptData['delivery_note_number'] ?? null,
                'inspection_notes' => $receiptData['inspection_notes'] ?? null,
                'quality_check_passed' => $receiptData['quality_check_passed'] ?? true,
            ]);

            // Create GRN items from PO items
            foreach ($purchaseOrder->items as $poItem) {
                $receivedQuantity = $receiptData['items'][$poItem->id]['quantity_received'] ?? 0;
                $condition = $receiptData['items'][$poItem->id]['condition'] ?? 'good';

                if ($receivedQuantity > 0) {
                    $grn->items()->create([
                        'po_item_id' => $poItem->id,
                        'quantity_ordered' => $poItem->quantity,
                        'quantity_received' => $receivedQuantity,
                        'unit_price' => $poItem->unit_price,
                        'condition' => $condition,
                        'batch_number' => $receiptData['items'][$poItem->id]['batch_number'] ?? null,
                        'expiry_date' => $receiptData['items'][$poItem->id]['expiry_date'] ?? null,
                        'serial_numbers' => $receiptData['items'][$poItem->id]['serial_numbers'] ?? null,
                        'notes' => $receiptData['items'][$poItem->id]['notes'] ?? null,
                    ]);
                }
            }

            // Update PO status
            $this->updatePurchaseOrderStatus($purchaseOrder);

            return $grn;
        });
    }

    /**
     * Confirm goods receipt.
     */
    public function confirm(GoodsReceiptNote $grn): void
    {
        if ($grn->status !== GoodsReceiptStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft goods receipts can be confirmed.');
        }

        $grn->update([
            'status' => GoodsReceiptStatus::CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
        ]);

        // Update PO status
        $this->updatePurchaseOrderStatus($grn->purchaseOrder);
    }

    /**
     * Reject goods receipt.
     */
    public function reject(GoodsReceiptNote $grn, string $reason): void
    {
        if ($grn->status !== GoodsReceiptStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft goods receipts can be rejected.');
        }

        $grn->update([
            'status' => GoodsReceiptStatus::REJECTED,
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Update purchase order status based on receipts.
     */
    private function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $totalOrdered = $purchaseOrder->items->sum('quantity');
        $totalReceived = $purchaseOrder->goodsReceiptNotes
            ->where('status', GoodsReceiptStatus::CONFIRMED)
            ->flatMap->items
            ->sum('quantity_received');

        if ($totalReceived == 0) {
            $status = \Nexus\Procurement\Enums\PurchaseOrderStatus::SENT_TO_VENDOR;
        } elseif ($totalReceived < $totalOrdered) {
            $status = \Nexus\Procurement\Enums\PurchaseOrderStatus::PARTIALLY_RECEIVED;
        } else {
            $status = \Nexus\Procurement\Enums\PurchaseOrderStatus::RECEIVED;
        }

        $purchaseOrder->update(['status' => $status]);
    }

    /**
     * Get receipt summary for purchase order.
     */
    public function getReceiptSummary(PurchaseOrder $purchaseOrder): array
    {
        $summary = [];

        foreach ($purchaseOrder->items as $item) {
            $receivedQuantity = $purchaseOrder->goodsReceiptNotes
                ->where('status', GoodsReceiptStatus::CONFIRMED)
                ->flatMap->items
                ->where('po_item_id', $item->id)
                ->sum('quantity_received');

            $summary[] = [
                'po_item_id' => $item->id,
                'item_description' => $item->item_description,
                'quantity_ordered' => $item->quantity,
                'quantity_received' => $receivedQuantity,
                'quantity_pending' => $item->quantity - $receivedQuantity,
                'percentage_received' => $item->quantity > 0 ? ($receivedQuantity / $item->quantity) * 100 : 0,
            ];
        }

        return $summary;
    }

    /**
     * Check if goods receipt can be invoiced.
     */
    public function canBeInvoiced(GoodsReceiptNote $grn): bool
    {
        return $grn->status === GoodsReceiptStatus::CONFIRMED &&
               $grn->vendorInvoice === null;
    }
}