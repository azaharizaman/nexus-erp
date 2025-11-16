<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\PurchaseRequisition;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\VendorInvoice;
use Nexus\Procurement\Models\Vendor;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Procurement Analytics Service
 *
 * Provides analytics and reporting for procurement operations.
 */
class ProcurementAnalyticsService
{
    /**
     * Get comprehensive procurement analytics dashboard data.
     */
    public function getDashboardData(int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months);

        return [
            'spend_analysis' => $this->getSpendAnalysis($startDate),
            'supplier_performance' => $this->getSupplierPerformanceSummary($startDate),
            'procurement_efficiency' => $this->getProcurementEfficiencyMetrics($startDate),
            'compliance_metrics' => $this->getComplianceMetrics($startDate),
            'trends' => $this->getProcurementTrends($months),
        ];
    }

    /**
     * Get spend analysis by category, vendor, and time period.
     */
    public function getSpendAnalysis(Carbon $startDate): array
    {
        $spendByVendor = PurchaseOrder::where('created_at', '>=', $startDate)
            ->selectRaw('vendor_id, SUM(total_amount) as total_spend')
            ->groupBy('vendor_id')
            ->with('vendor')
            ->get()
            ->sortByDesc('total_spend')
            ->take(10);

        $spendByMonth = PurchaseOrder::where('created_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total_spend")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $spendByCategory = PurchaseOrder::join('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.created_at', '>=', $startDate)
            ->selectRaw('purchase_order_items.category, SUM(purchase_order_items.total_amount) as total_spend')
            ->groupBy('purchase_order_items.category')
            ->orderByDesc('total_spend')
            ->get();

        return [
            'total_spend' => PurchaseOrder::where('created_at', '>=', $startDate)->sum('total_amount'),
            'by_vendor' => $spendByVendor,
            'by_month' => $spendByMonth,
            'by_category' => $spendByCategory,
        ];
    }

    /**
     * Get supplier performance summary.
     */
    public function getSupplierPerformanceSummary(Carbon $startDate): array
    {
        $vendors = Vendor::whereHas('purchaseOrders', function ($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })->get();

        $performanceData = $vendors->map(function ($vendor) use ($startDate) {
            $pos = $vendor->purchaseOrders()->where('created_at', '>=', $startDate)->get();
            $receipts = GoodsReceiptNote::whereHas('purchaseOrder', function ($query) use ($vendor) {
                $query->where('vendor_id', $vendor->id);
            })->where('created_at', '>=', $startDate)->get();

            return [
                'vendor' => $vendor,
                'total_pos' => $pos->count(),
                'total_spend' => $pos->sum('total_amount'),
                'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($pos, $receipts),
                'quality_score' => $this->calculateQualityScore($receipts),
            ];
        })->sortByDesc('total_spend');

        return [
            'total_vendors' => $vendors->count(),
            'top_vendors_by_spend' => $performanceData->take(10),
            'average_on_time_delivery' => $performanceData->avg('on_time_delivery_rate'),
            'average_quality_score' => $performanceData->avg('quality_score'),
        ];
    }

    /**
     * Get procurement efficiency metrics.
     */
    public function getProcurementEfficiencyMetrics(Carbon $startDate): array
    {
        $requisitions = PurchaseRequisition::where('created_at', '>=', $startDate)->get();
        $purchaseOrders = PurchaseOrder::where('created_at', '>=', $startDate)->get();

        $avgProcessingTime = $requisitions->avg(function ($req) {
            return $req->created_at->diffInDays($req->approved_at ?? now());
        });

        $requisitionToPOConversionRate = 0;
        if ($requisitions->count() > 0) {
            $convertedReqs = $requisitions->filter(function ($req) {
                return $req->purchaseOrders()->exists();
            })->count();
            $requisitionToPOConversionRate = ($convertedReqs / $requisitions->count()) * 100;
        }

        $avgOrderCycleTime = $purchaseOrders->avg(function ($po) {
            $receiptDate = $po->goodsReceipts()->max('receipt_date');
            return $receiptDate ? $po->order_date->diffInDays($receiptDate) : null;
        });

        return [
            'total_requisitions' => $requisitions->count(),
            'total_purchase_orders' => $purchaseOrders->count(),
            'average_processing_time_days' => round($avgProcessingTime ?? 0, 1),
            'requisition_to_po_conversion_rate' => round($requisitionToPOConversionRate, 1),
            'average_order_cycle_time_days' => round($avgOrderCycleTime ?? 0, 1),
            'efficiency_score' => $this->calculateEfficiencyScore($requisitionToPOConversionRate, $avgProcessingTime),
        ];
    }

    /**
     * Get compliance metrics.
     */
    public function getComplianceMetrics(Carbon $startDate): array
    {
        $purchaseOrders = PurchaseOrder::where('created_at', '>=', $startDate)->get();
        $invoices = VendorInvoice::where('created_at', '>=', $startDate)->get();

        $approvedPOs = $purchaseOrders->filter(function ($po) {
            return in_array($po->status, ['approved', 'sent_to_vendor', 'partially_received', 'completed']);
        })->count();

        $poApprovalRate = $purchaseOrders->count() > 0 ? ($approvedPOs / $purchaseOrders->count()) * 100 : 0;

        $matchedInvoices = $invoices->filter(function ($invoice) {
            return $invoice->matching_status === 'matched';
        })->count();

        $invoiceMatchingRate = $invoices->count() > 0 ? ($matchedInvoices / $invoices->count()) * 100 : 0;

        return [
            'po_approval_rate' => round($poApprovalRate, 1),
            'invoice_matching_rate' => round($invoiceMatchingRate, 1),
            'total_pos' => $purchaseOrders->count(),
            'approved_pos' => $approvedPOs,
            'total_invoices' => $invoices->count(),
            'matched_invoices' => $matchedInvoices,
            'overall_compliance_score' => round(($poApprovalRate + $invoiceMatchingRate) / 2, 1),
        ];
    }

    /**
     * Get procurement trends over time.
     */
    public function getProcurementTrends(int $months): array
    {
        $trends = [];

        for ($i = $months; $i >= 1; $i--) {
            $periodStart = Carbon::now()->subMonths($i);
            $periodEnd = Carbon::now()->subMonths($i - 1);

            $periodSpend = PurchaseOrder::whereBetween('created_at', [$periodStart, $periodEnd])->sum('total_amount');
            $periodPOs = PurchaseOrder::whereBetween('created_at', [$periodStart, $periodEnd])->count();
            $periodReceipts = GoodsReceiptNote::whereBetween('created_at', [$periodStart, $periodEnd])->count();

            $trends[] = [
                'period' => $periodStart->format('M Y'),
                'total_spend' => $periodSpend,
                'purchase_orders' => $periodPOs,
                'goods_receipts' => $periodReceipts,
            ];
        }

        return $trends;
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
     * Calculate quality score.
     */
    private function calculateQualityScore(Collection $goodsReceipts): float
    {
        if ($goodsReceipts->isEmpty()) {
            return 100.0;
        }

        $totalReceived = $goodsReceipts->sum('received_quantity');
        $totalRejected = $goodsReceipts->sum('rejected_quantity');

        if ($totalReceived + $totalRejected === 0) {
            return 100.0;
        }

        $rejectionRate = ($totalRejected / ($totalReceived + $totalRejected)) * 100;
        return max(0, 100 - $rejectionRate);
    }

    /**
     * Calculate efficiency score.
     */
    private function calculateEfficiencyScore(float $conversionRate, ?float $avgProcessingTime): float
    {
        $timeScore = $avgProcessingTime ? max(0, 100 - ($avgProcessingTime * 5)) : 50;
        $conversionScore = $conversionRate;

        return round(($timeScore + $conversionScore) / 2, 1);
    }

    /**
     * Get category-wise spend analysis.
     */
    public function getCategorySpendAnalysis(Carbon $startDate): Collection
    {
        return PurchaseOrder::join('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.created_at', '>=', $startDate)
            ->selectRaw('
                purchase_order_items.category,
                COUNT(DISTINCT purchase_orders.id) as po_count,
                SUM(purchase_order_items.total_amount) as total_spend,
                AVG(purchase_order_items.unit_price) as avg_unit_price,
                SUM(purchase_order_items.quantity) as total_quantity
            ')
            ->groupBy('purchase_order_items.category')
            ->orderByDesc('total_spend')
            ->get();
    }

    /**
     * Get savings analysis compared to previous periods.
     */
    public function getSavingsAnalysis(int $months = 12): array
    {
        $currentPeriod = Carbon::now()->subMonths($months);
        $previousPeriod = Carbon::now()->subMonths($months * 2);

        $currentSpend = PurchaseOrder::where('created_at', '>=', $currentPeriod)->sum('total_amount');
        $previousSpend = PurchaseOrder::where('created_at', '>=', $previousPeriod)
            ->where('created_at', '<', $currentPeriod)
            ->sum('total_amount');

        $spendChange = $previousSpend > 0 ? (($currentSpend - $previousSpend) / $previousSpend) * 100 : 0;

        return [
            'current_period_spend' => $currentSpend,
            'previous_period_spend' => $previousSpend,
            'spend_change_percentage' => round($spendChange, 2),
            'savings_amount' => max(0, $previousSpend - $currentSpend),
            'period_months' => $months,
        ];
    }
}