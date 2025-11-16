<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\VendorInvoice;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Vendor Performance Service
 *
 * Tracks and calculates vendor performance metrics.
 */
class VendorPerformanceService
{
    /**
     * Calculate comprehensive vendor performance metrics.
     */
    public function calculatePerformanceMetrics(int $vendorId, int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months);

        $purchaseOrders = PurchaseOrder::where('vendor_id', $vendorId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $goodsReceipts = GoodsReceiptNote::whereHas('purchaseOrder', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->where('created_at', '>=', $startDate)->get();

        $invoices = VendorInvoice::where('vendor_id', $vendorId)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'vendor_id' => $vendorId,
            'period_months' => $months,
            'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($purchaseOrders, $goodsReceipts),
            'quality_rating' => $this->calculateQualityRating($goodsReceipts),
            'price_competitiveness' => $this->calculatePriceCompetitiveness($vendorId, $months),
            'invoice_accuracy' => $this->calculateInvoiceAccuracy($invoices),
            'overall_score' => 0, // Will be calculated from above metrics
            'metrics_breakdown' => [
                'total_pos' => $purchaseOrders->count(),
                'total_receipts' => $goodsReceipts->count(),
                'total_invoices' => $invoices->count(),
                'total_po_value' => $purchaseOrders->sum('total_amount'),
                'avg_delivery_time' => $this->calculateAverageDeliveryTime($purchaseOrders, $goodsReceipts),
                'rejection_rate' => $this->calculateRejectionRate($goodsReceipts),
            ],
        ];
    }

    /**
     * Calculate on-time delivery rate.
     */
    private function calculateOnTimeDeliveryRate(Collection $purchaseOrders, Collection $goodsReceipts): float
    {
        if ($purchaseOrders->isEmpty()) {
            return 0.0;
        }

        $onTimeDeliveries = 0;

        foreach ($purchaseOrders as $po) {
            $receipts = $goodsReceipts->where('purchase_order_id', $po->id);
            if ($receipts->isNotEmpty()) {
                $latestReceipt = $receipts->sortByDesc('receipt_date')->first();
                if ($latestReceipt->receipt_date <= $po->expected_delivery_date) {
                    $onTimeDeliveries++;
                }
            }
        }

        return ($onTimeDeliveries / $purchaseOrders->count()) * 100;
    }

    /**
     * Calculate quality rating based on rejections and returns.
     */
    private function calculateQualityRating(Collection $goodsReceipts): float
    {
        if ($goodsReceipts->isEmpty()) {
            return 100.0; // Default perfect score if no receipts
        }

        $totalReceived = $goodsReceipts->sum('received_quantity');
        $totalRejected = $goodsReceipts->sum('rejected_quantity');

        if ($totalReceived + $totalRejected === 0) {
            return 100.0;
        }

        $rejectionRate = ($totalRejected / ($totalReceived + $totalRejected)) * 100;
        return max(0, 100 - $rejectionRate); // Quality score is inverse of rejection rate
    }

    /**
     * Calculate price competitiveness compared to market average.
     */
    private function calculatePriceCompetitiveness(int $vendorId, int $months): float
    {
        $startDate = Carbon::now()->subMonths($months);

        // Get vendor's average price per item category
        $vendorPrices = PurchaseOrder::join('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.vendor_id', $vendorId)
            ->where('purchase_orders.created_at', '>=', $startDate)
            ->selectRaw('purchase_order_items.item_description, AVG(purchase_order_items.unit_price) as avg_price')
            ->groupBy('purchase_order_items.item_description')
            ->get();

        if ($vendorPrices->isEmpty()) {
            return 50.0; // Neutral score
        }

        // For simplicity, return a score based on how many items they have pricing for
        // In a real implementation, this would compare against market averages
        return min(100.0, 50.0 + ($vendorPrices->count() * 5));
    }

    /**
     * Calculate invoice accuracy based on matching results.
     */
    private function calculateInvoiceAccuracy(Collection $invoices): float
    {
        if ($invoices->isEmpty()) {
            return 100.0;
        }

        $accurateInvoices = $invoices->filter(function ($invoice) {
            return $invoice->matching_status === 'matched' || $invoice->matching_status === 'approved';
        })->count();

        return ($accurateInvoices / $invoices->count()) * 100;
    }

    /**
     * Calculate average delivery time in days.
     */
    private function calculateAverageDeliveryTime(Collection $purchaseOrders, Collection $goodsReceipts): float
    {
        $deliveryTimes = [];

        foreach ($purchaseOrders as $po) {
            $receipts = $goodsReceipts->where('purchase_order_id', $po->id);
            if ($receipts->isNotEmpty()) {
                $latestReceipt = $receipts->sortByDesc('receipt_date')->first();
                $deliveryTime = $po->order_date->diffInDays($latestReceipt->receipt_date);
                $deliveryTimes[] = $deliveryTime;
            }
        }

        return $deliveryTimes ? array_sum($deliveryTimes) / count($deliveryTimes) : 0;
    }

    /**
     * Calculate rejection rate.
     */
    private function calculateRejectionRate(Collection $goodsReceipts): float
    {
        $totalReceived = $goodsReceipts->sum('received_quantity');
        $totalRejected = $goodsReceipts->sum('rejected_quantity');

        if ($totalReceived + $totalRejected === 0) {
            return 0.0;
        }

        return ($totalRejected / ($totalReceived + $totalRejected)) * 100;
    }

    /**
     * Get vendor performance ranking compared to other vendors.
     */
    public function getVendorRanking(int $vendorId, int $months = 12): array
    {
        $vendorMetrics = $this->calculatePerformanceMetrics($vendorId, $months);

        // Get all vendors with performance data
        $allVendors = Vendor::whereHas('purchaseOrders', function ($query) use ($months) {
            $query->where('created_at', '>=', Carbon::now()->subMonths($months));
        })->get();

        $rankings = [];
        foreach ($allVendors as $vendor) {
            $metrics = $this->calculatePerformanceMetrics($vendor->id, $months);
            $rankings[] = [
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'on_time_delivery_rate' => $metrics['on_time_delivery_rate'],
                'quality_rating' => $metrics['quality_rating'],
                'overall_score' => $this->calculateOverallScore($metrics),
            ];
        }

        // Sort by overall score descending
        usort($rankings, function ($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });

        // Find current vendor's rank
        $rank = null;
        foreach ($rankings as $index => $ranking) {
            if ($ranking['vendor_id'] === $vendorId) {
                $rank = $index + 1;
                break;
            }
        }

        return [
            'vendor_id' => $vendorId,
            'rank' => $rank,
            'total_vendors' => count($rankings),
            'percentile' => $rank ? (($rankings[0]['overall_score'] - $rankings[$rank - 1]['overall_score']) / $rankings[0]['overall_score']) * 100 : 0,
            'metrics' => $vendorMetrics,
        ];
    }

    /**
     * Calculate overall performance score.
     */
    private function calculateOverallScore(array $metrics): float
    {
        $weights = [
            'on_time_delivery_rate' => 0.4,
            'quality_rating' => 0.3,
            'price_competitiveness' => 0.2,
            'invoice_accuracy' => 0.1,
        ];

        $score = 0;
        foreach ($weights as $metric => $weight) {
            $score += ($metrics[$metric] ?? 0) * $weight;
        }

        return round($score, 2);
    }

    /**
     * Record manual vendor rating.
     */
    public function recordManualRating(int $vendorId, array $ratingData): void
    {
        // This would typically create a VendorRating model record
        // For now, we'll just validate the data
        $validated = validator($ratingData, [
            'rating' => 'required|numeric|min:1|max:5',
            'comments' => 'nullable|string',
            'rated_by' => 'required|exists:users,id',
            'criteria' => 'array',
        ])->validate();

        // In a real implementation, save to vendor_ratings table
        // VendorRating::create(array_merge($validated, ['vendor_id' => $vendorId]));
    }
}