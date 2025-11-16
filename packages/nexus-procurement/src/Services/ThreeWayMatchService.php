<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\VendorInvoice;
use Nexus\Procurement\Models\ThreeWayMatchResult;
use Nexus\Procurement\Enums\MatchStatus;
use Illuminate\Support\Facades\Config;

/**
 * Three-Way Match Service
 *
 * Performs 3-way matching between PO, GRN, and Invoice for payment authorization.
 */
class ThreeWayMatchService
{
    /**
     * Perform 3-way match for an invoice.
     */
    public function performMatch(VendorInvoice $invoice): ThreeWayMatchResult
    {
        $po = $invoice->purchaseOrder;
        $grn = $invoice->goodsReceiptNote;

        // Calculate variances
        $priceVariance = $this->calculatePriceVariance($po, $invoice);
        $quantityVariance = $this->calculateQuantityVariance($po, $grn, $invoice);
        $totalVariance = $this->calculateTotalVariance($po, $invoice);

        // Determine match status
        $matchStatus = $this->determineMatchStatus($priceVariance, $quantityVariance, $totalVariance);

        // Create match result
        return ThreeWayMatchResult::create([
            'po_id' => $po->id,
            'grn_id' => $grn?->id,
            'vendor_invoice_id' => $invoice->id,
            'match_date' => now(),
            'match_status' => $matchStatus->value,
            'price_variance_pct' => $priceVariance,
            'quantity_variance_pct' => $quantityVariance,
            'total_variance_amount' => $totalVariance,
            'tolerance_applied' => $this->getToleranceRules(),
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
     * Get tolerance rules applied.
     */
    private function getToleranceRules(): array
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
}