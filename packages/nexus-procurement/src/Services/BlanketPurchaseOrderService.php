<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\BlanketPurchaseOrder;
use Nexus\Procurement\Models\BlanketPurchaseOrderItem;
use Nexus\Procurement\Models\BlanketPORelease;
use Nexus\Procurement\Models\BlanketPOReleaseItem;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Enums\BlanketPurchaseOrderStatus;
use Nexus\Procurement\Enums\BlanketPOReleaseStatus;
use Nexus\Sequencing\Services\SequencingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Blanket Purchase Order Service
 *
 * Manages blanket purchase orders and their releases.
 */
class BlanketPurchaseOrderService
{
    public function __construct(
        private SequencingService $sequencingService,
        private PurchaseOrderService $poService
    ) {}

    /**
     * Create a new blanket purchase order.
     */
    public function createBlanketPO(array $data): BlanketPurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            // Generate blanket PO number
            $blanketPONumber = $this->sequencingService->generateNumber('blanket_po');

            // Create blanket PO
            $blanketPO = BlanketPurchaseOrder::create([
                'blanket_po_number' => $blanketPONumber,
                'vendor_id' => $data['vendor_id'],
                'created_by' => Auth::id(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'total_committed_value' => $data['total_committed_value'],
                'currency_code' => $data['currency_code'] ?? 'USD',
                'valid_from' => $data['valid_from'],
                'valid_until' => $data['valid_until'],
                'payment_terms' => $data['payment_terms'] ?? null,
                'status' => BlanketPurchaseOrderStatus::DRAFT,
                'auto_approval_limit' => $data['auto_approval_limit'] ?? 0,
                'utilization_alert_threshold' => $data['utilization_alert_threshold'] ?? 0.80,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create blanket PO items
            foreach ($data['items'] as $index => $itemData) {
                $blanketPO->items()->create([
                    'line_number' => $index + 1,
                    'item_description' => $itemData['item_description'],
                    'specifications' => $itemData['specifications'] ?? null,
                    'unit_of_measure' => $itemData['unit_of_measure'] ?? null,
                    'max_quantity' => $itemData['max_quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_line_value' => $itemData['max_quantity'] * $itemData['unit_price'],
                    'category_code' => $itemData['category_code'] ?? null,
                    'gl_account_code' => $itemData['gl_account_code'] ?? null,
                ]);
            }

            return $blanketPO;
        });
    }

    /**
     * Activate a blanket purchase order.
     */
    public function activateBlanketPO(BlanketPurchaseOrder $blanketPO): void
    {
        if ($blanketPO->status !== BlanketPurchaseOrderStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft blanket POs can be activated.');
        }

        if ($blanketPO->items()->count() === 0) {
            throw new \InvalidArgumentException('Blanket PO must have at least one item before activation.');
        }

        $blanketPO->update(['status' => BlanketPurchaseOrderStatus::ACTIVE]);
    }

    /**
     * Create a release against a blanket purchase order.
     */
    public function createRelease(BlanketPurchaseOrder $blanketPO, array $releaseData): BlanketPORelease
    {
        if (!$blanketPO->isActive()) {
            throw new \InvalidArgumentException('Blanket PO must be active to create releases.');
        }

        return DB::transaction(function () use ($blanketPO, $releaseData) {
            // Generate release number
            $releaseNumber = $this->sequencingService->generateNumber('blanket_po_release');

            // Calculate total release value
            $totalReleaseValue = 0;
            foreach ($releaseData['items'] as $itemData) {
                $blanketPOItem = BlanketPurchaseOrderItem::findOrFail($itemData['blanket_po_item_id']);
                $lineTotal = $itemData['quantity'] * $blanketPOItem->unit_price;
                $totalReleaseValue += $lineTotal;

                // Check if quantity is available
                if (!$blanketPOItem->canReleaseQuantity($itemData['quantity'])) {
                    throw new \InvalidArgumentException("Insufficient quantity available for item: {$blanketPOItem->item_description}");
                }
            }

            // Check if total value is within remaining committed value
            if (!$blanketPO->canReleaseValue($totalReleaseValue)) {
                throw new \InvalidArgumentException('Release value exceeds remaining committed value.');
            }

            // Create release
            $release = BlanketPORelease::create([
                'blanket_po_id' => $blanketPO->id,
                'release_number' => $releaseNumber,
                'created_by' => Auth::id(),
                'title' => $releaseData['title'],
                'description' => $releaseData['description'] ?? null,
                'total_release_value' => $totalReleaseValue,
                'required_delivery_date' => $releaseData['required_delivery_date'] ?? null,
                'status' => BlanketPOReleaseStatus::DRAFT,
                'notes' => $releaseData['notes'] ?? null,
            ]);

            // Create release items
            foreach ($releaseData['items'] as $itemData) {
                $blanketPOItem = BlanketPurchaseOrderItem::findOrFail($itemData['blanket_po_item_id']);

                $release->items()->create([
                    'blanket_po_item_id' => $blanketPOItem->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $blanketPOItem->unit_price,
                    'line_total' => $itemData['quantity'] * $blanketPOItem->unit_price,
                    'delivery_date' => $itemData['delivery_date'] ?? $release->required_delivery_date,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            return $release;
        });
    }

    /**
     * Submit release for approval.
     */
    public function submitReleaseForApproval(BlanketPORelease $release): void
    {
        if ($release->status !== BlanketPOReleaseStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft releases can be submitted for approval.');
        }

        $release->update(['status' => BlanketPOReleaseStatus::PENDING_APPROVAL]);

        // TODO: Trigger approval workflow
    }

    /**
     * Approve a release.
     */
    public function approveRelease(BlanketPORelease $release): void
    {
        if ($release->status !== BlanketPOReleaseStatus::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Only pending releases can be approved.');
        }

        $release->update([
            'status' => BlanketPOReleaseStatus::APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Convert approved release to purchase order.
     */
    public function convertReleaseToPO(BlanketPORelease $release): PurchaseOrder
    {
        if ($release->status !== BlanketPOReleaseStatus::APPROVED) {
            throw new \InvalidArgumentException('Only approved releases can be converted to purchase orders.');
        }

        return DB::transaction(function () use ($release) {
            $blanketPO = $release->blanketPurchaseOrder;

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->sequencingService->generateNumber('purchase_order'),
                'vendor_id' => $blanketPO->vendor_id,
                'status' => \Nexus\Procurement\Enums\PurchaseOrderStatus::DRAFT,
                'order_date' => now(),
                'expected_delivery_date' => $release->required_delivery_date,
                'payment_terms' => $blanketPO->payment_terms,
                'notes' => "Created from Blanket PO Release: {$release->release_number}",
                'created_by' => Auth::id(),
                'currency_code' => $blanketPO->currency_code,
            ]);

            // Create PO items from release items
            foreach ($release->items as $releaseItem) {
                $purchaseOrder->items()->create([
                    'item_description' => $releaseItem->blanketPOItem->item_description,
                    'quantity' => $releaseItem->quantity,
                    'unit_price' => $releaseItem->unit_price,
                    'total_price' => $releaseItem->line_total,
                    'uom' => $releaseItem->blanketPOItem->unit_of_measure,
                    'specifications' => $releaseItem->blanketPOItem->specifications,
                    'gl_account_code' => $releaseItem->blanketPOItem->gl_account_code,
                ]);
            }

            // Update totals
            $this->poService->updateTotals($purchaseOrder);

            // Update release status
            $release->update([
                'status' => BlanketPOReleaseStatus::CONVERTED_TO_PO,
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Get blanket PO utilization summary.
     */
    public function getUtilizationSummary(BlanketPurchaseOrder $blanketPO): array
    {
        $totalReleased = $blanketPO->getTotalReleasedValue();
        $utilizationPercentage = $blanketPO->getUtilizationPercentage();

        return [
            'blanket_po' => $blanketPO,
            'total_committed' => $blanketPO->total_committed_value,
            'total_released' => $totalReleased,
            'remaining_value' => $blanketPO->getRemainingValue(),
            'utilization_percentage' => $utilizationPercentage,
            'alert_triggered' => $blanketPO->shouldTriggerUtilizationAlert(),
            'releases_count' => $blanketPO->releases()->count(),
            'active_releases' => $blanketPO->releases()->whereIn('status', [
                BlanketPOReleaseStatus::DRAFT,
                BlanketPOReleaseStatus::PENDING_APPROVAL,
                BlanketPOReleaseStatus::APPROVED,
            ])->count(),
        ];
    }

    /**
     * Get blanket PO items utilization.
     */
    public function getItemsUtilization(BlanketPurchaseOrder $blanketPO): array
    {
        $items = [];

        foreach ($blanketPO->items as $item) {
            $releasedQuantity = $item->getTotalReleasedQuantity();
            $utilizationPercentage = $item->max_quantity > 0
                ? ($releasedQuantity / $item->max_quantity) * 100
                : 0;

            $items[] = [
                'item' => $item,
                'max_quantity' => $item->max_quantity,
                'released_quantity' => $releasedQuantity,
                'remaining_quantity' => $item->getRemainingQuantity(),
                'utilization_percentage' => $utilizationPercentage,
            ];
        }

        return $items;
    }

    /**
     * Expire blanket POs that have reached their end date.
     */
    public function expireBlanketPOs(): int
    {
        return BlanketPurchaseOrder::where('status', BlanketPurchaseOrderStatus::ACTIVE)
            ->where('valid_until', '<', now())
            ->update(['status' => BlanketPurchaseOrderStatus::EXPIRED]);
    }
}