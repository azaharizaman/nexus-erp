<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\VendorInvoice;
use Nexus\Procurement\Models\ThreeWayMatchResult;
use Nexus\Procurement\Enums\MatchStatus;
use Nexus\Procurement\Contracts\ThreeWayMatchServiceContract;

/**
 * Three-Way Match Service
 *
 * Performs 3-way matching between PO, GRN, and Invoice for payment authorization.
 */
class ThreeWayMatchService implements ThreeWayMatchServiceContract
{
    /**
     * Perform 3-way matching for a purchase order, goods receipt, and vendor invoice
     */
    public function performMatch(string $purchaseOrderId, string $goodsReceiptId, string $vendorInvoiceId): ThreeWayMatchResult
    {
        $po = PurchaseOrder::findOrFail($purchaseOrderId);
        $grn = GoodsReceiptNote::findOrFail($goodsReceiptId);
        $invoice = VendorInvoice::findOrFail($vendorInvoiceId);

        // Calculate variances
        $priceVariance = $this->calculatePriceVariance($po, $invoice);
        $quantityVariance = $this->calculateQuantityVariance($po, $grn, $invoice);
        $totalVariance = $this->calculateTotalVariance($po, $invoice);

        // Determine match status
        $matchStatus = $this->determineMatchStatus($priceVariance, $quantityVariance, $totalVariance);

        // Create match result
        return ThreeWayMatchResult::create([
            'po_id' => $po->id,
            'grn_id' => $grn->id,
            'vendor_invoice_id' => $invoice->id,
            'match_date' => now(),
            'match_status' => $matchStatus->value,
            'price_variance_pct' => $priceVariance,
            'quantity_variance_pct' => $quantityVariance,
            'total_variance_amount' => $totalVariance,
            'tolerance_applied' => $this->getAppliedToleranceRules(),
            'variance_details' => $this->getVarianceDetails($po, $grn, $invoice),
        ]);
    }

    /**
     * Calculate price variance between PO and invoice.
     */
    private function calculatePriceVariance(PurchaseOrder $po, VendorInvoice $invoice): float
    {
        $poTotal = $po->total_amount;
        $invoiceTotal = $invoice->total_amount;

        if ($poTotal == 0) {
            return 0;
        }

        return (($invoiceTotal - $poTotal) / $poTotal) * 100;
    }

    /**
     * Calculate quantity variance between PO, GRN, and invoice.
     */
    private function calculateQuantityVariance(PurchaseOrder $po, ?GoodsReceiptNote $grn, VendorInvoice $invoice): float
    {
        $totalOrdered = $po->items->sum('quantity');
        $totalReceived = $grn?->items->sum('quantity_received') ?? 0;
        $totalInvoiced = $invoice->items->sum('quantity');

        // Compare received vs invoiced (most important check)
        if ($totalReceived == 0) {
            return 0;
        }

        return (($totalInvoiced - $totalReceived) / $totalReceived) * 100;
    }

    /**
     * Calculate total variance amount.
     */
    private function calculateTotalVariance(PurchaseOrder $po, VendorInvoice $invoice): float
    {
        return abs($invoice->total_amount - $po->total_amount);
    }

    /**
     * Determine match status based on variances and tolerances.
     */
    private function determineMatchStatus(float $priceVariance, float $quantityVariance, float $totalVariance): MatchStatus
    {
        $tolerance = Config::get('procurement.three_way_match');

        // Check quantity variance first (most critical)
        if (abs($quantityVariance) > $tolerance['quantity_variance_tolerance']) {
            return MatchStatus::REJECTED;
        }

        // Check price variance
        if (abs($priceVariance) > $tolerance['price_variance_tolerance']) {
            return MatchStatus::VARIANCE;
        }

        // Check total variance amount
        if ($totalVariance > $tolerance['total_variance_amount']) {
            return MatchStatus::VARIANCE;
        }

        return MatchStatus::MATCHED;
    }

    /**
     * Get tolerance rules applied (private method).
     */
    private function getAppliedToleranceRules(): array
    {
        return Config::get('procurement.three_way_match', []);
    }

    /**
     * Get detailed variance information.
     */
    private function getVarianceDetails(PurchaseOrder $po, ?GoodsReceiptNote $grn, VendorInvoice $invoice): array
    {
        $details = [];

        foreach ($po->items as $poItem) {
            $grnItem = $grn?->items->where('po_item_id', $poItem->id)->first();
            $invoiceItem = $invoice->items->where('po_item_id', $poItem->id)->first();

            $details[] = [
                'po_item_id' => $poItem->id,
                'ordered_quantity' => $poItem->quantity,
                'received_quantity' => $grnItem?->quantity_received ?? 0,
                'invoiced_quantity' => $invoiceItem?->quantity ?? 0,
                'unit_price_po' => $poItem->unit_price,
                'unit_price_invoice' => $invoiceItem?->unit_price ?? 0,
            ];
        }

        return $details;
    }

    /**
     * Check if match result allows payment authorization.
     */
    public function canAuthorizePayment(ThreeWayMatchResult $result): bool
    {
        return $result->match_status === MatchStatus::MATCHED->value ||
               ($result->match_status === MatchStatus::VARIANCE->value && $result->approved_override);
    }

    /**
     * Perform 3-way matching for a vendor invoice (auto-find PO and GRN)
     */
    public function matchInvoice(string $vendorInvoiceId): ThreeWayMatchResult
    {
        $invoice = VendorInvoice::with(['purchaseOrder', 'goodsReceiptNote'])->findOrFail($vendorInvoiceId);

        return $this->performMatch(
            $invoice->purchase_order_id,
            $invoice->goods_receipt_note_id,
            $vendorInvoiceId
        );
    }

    /**
     * Check if documents match within tolerance rules
     */
    public function checkTolerance(array $poData, array $grnData, array $invoiceData): array
    {
        // Calculate variances
        $priceVariance = $this->calculatePriceVarianceFromData($poData, $invoiceData);
        $quantityVariance = $this->calculateQuantityVarianceFromData($poData, $grnData, $invoiceData);
        $totalVariance = abs($invoiceData['total_amount'] - $poData['total_amount']);

        // Get tolerance rules
        $tolerance = $this->getToleranceRules($poData['tenant_id']);

        return [
            'price_variance_pct' => $priceVariance,
            'quantity_variance_pct' => $quantityVariance,
            'total_variance_amount' => $totalVariance,
            'within_tolerance' => abs($priceVariance) <= $tolerance['price_variance_tolerance'] &&
                                 abs($quantityVariance) <= $tolerance['quantity_variance_tolerance'] &&
                                 $totalVariance <= $tolerance['total_variance_amount'],
            'tolerance_rules' => $tolerance,
        ];
    }

    /**
     * Get tolerance rules for a tenant
     */
    public function getToleranceRules(string $tenantId): array
    {
        // In a real implementation, this would fetch from database
        // For now, return default config
        return Config::get('procurement.three_way_match', [
            'price_variance_tolerance' => 5.0,
            'quantity_variance_tolerance' => 2.0,
            'total_variance_amount' => 100.0,
        ]);
    }

    /**
     * Update tolerance rules for a tenant
     */
    public function updateToleranceRules(string $tenantId, array $rules): bool
    {
        // In a real implementation, this would save to database
        // For now, just validate the rules
        $required = ['price_variance_tolerance', 'quantity_variance_tolerance', 'total_variance_amount'];
        foreach ($required as $key) {
            if (!isset($rules[$key]) || !is_numeric($rules[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get match results for a purchase order
     */
    public function getMatchesForPurchaseOrder(string $purchaseOrderId): \Illuminate\Database\Eloquent\Collection
    {
        return ThreeWayMatchResult::where('po_id', $purchaseOrderId)->get();
    }

    /**
     * Get match results for a vendor invoice
     */
    public function getMatchesForInvoice(string $vendorInvoiceId): \Illuminate\Database\Eloquent\Collection
    {
        return ThreeWayMatchResult::where('vendor_invoice_id', $vendorInvoiceId)->get();
    }

    /**
     * Approve a match result
     */
    public function approveMatch(string $matchResultId, string $approverId, string $comments = null): ThreeWayMatchResult
    {
        $result = ThreeWayMatchResult::findOrFail($matchResultId);
        $result->update([
            'approved_override' => true,
            'approved_by' => $approverId,
            'approval_comments' => $comments,
            'approved_at' => now(),
        ]);
        return $result;
    }

    /**
     * Reject a match result
     */
    public function rejectMatch(string $matchResultId, string $approverId, string $comments = null): ThreeWayMatchResult
    {
        $result = ThreeWayMatchResult::findOrFail($matchResultId);
        $result->update([
            'approved_override' => false,
            'rejected_by' => $approverId,
            'rejection_comments' => $comments,
            'rejected_at' => now(),
        ]);
        return $result;
    }

    /**
     * Get match statistics for a tenant
     */
    public function getMatchStatistics(string $tenantId, array $dateRange = []): array
    {
        $query = ThreeWayMatchResult::whereHas('purchaseOrder', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });

        if (!empty($dateRange)) {
            $query->whereBetween('match_date', $dateRange);
        }

        $results = $query->get();

        return [
            'total_matches' => $results->count(),
            'matched' => $results->where('match_status', MatchStatus::MATCHED->value)->count(),
            'variance' => $results->where('match_status', MatchStatus::VARIANCE->value)->count(),
            'rejected' => $results->where('match_status', MatchStatus::REJECTED->value)->count(),
            'average_price_variance' => $results->avg('price_variance_pct'),
            'average_quantity_variance' => $results->avg('quantity_variance_pct'),
        ];
    }

    /**
     * Validate match data
     */
    public function validateMatchData(array $data): array
    {
        $errors = [];

        if (empty($data['purchase_order_id'])) {
            $errors[] = 'Purchase order ID is required';
        }

        if (empty($data['goods_receipt_id'])) {
            $errors[] = 'Goods receipt ID is required';
        }

        if (empty($data['vendor_invoice_id'])) {
            $errors[] = 'Vendor invoice ID is required';
        }

        return $errors;
    }

    /**
     * Calculate price variance from data arrays
     */
    private function calculatePriceVarianceFromData(array $poData, array $invoiceData): float
    {
        $poTotal = $poData['total_amount'];
        $invoiceTotal = $invoiceData['total_amount'];

        if ($poTotal == 0) {
            return 0;
        }

        return (($invoiceTotal - $poTotal) / $poTotal) * 100;
    }

    /**
     * Calculate quantity variance from data arrays
     */
    private function calculateQuantityVarianceFromData(array $poData, array $grnData, array $invoiceData): float
    {
        $totalOrdered = array_sum(array_column($poData['items'] ?? [], 'quantity'));
        $totalReceived = array_sum(array_column($grnData['items'] ?? [], 'quantity_received'));
        $totalInvoiced = array_sum(array_column($invoiceData['items'] ?? [], 'quantity'));

        if ($totalReceived == 0) {
            return 0;
        }

        return (($totalInvoiced - $totalReceived) / $totalReceived) * 100;
    }
}