<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Services\VendorPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Vendor Performance Controller
 *
 * API endpoints for vendor performance tracking and analytics.
 */
class VendorPerformanceController extends Controller
{
    public function __construct(
        private VendorPerformanceService $performanceService
    ) {}

    /**
     * Get vendor performance metrics.
     */
    public function metrics(Vendor $vendor, Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $metrics = $this->performanceService->calculatePerformanceMetrics($vendor->id, $months);

        return response()->json($metrics);
    }

    /**
     * Get vendor performance ranking.
     */
    public function ranking(Vendor $vendor, Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $ranking = $this->performanceService->getVendorRanking($vendor->id, $months);

        return response()->json($ranking);
    }

    /**
     * Get performance dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);

        // Get top performing vendors
        $topVendors = Vendor::whereHas('purchaseOrders', function ($query) use ($months) {
            $query->where('created_at', '>=', \Carbon\Carbon::now()->subMonths($months));
        })->get()->map(function ($vendor) use ($months) {
            $metrics = $this->performanceService->calculatePerformanceMetrics($vendor->id, $months);
            return [
                'vendor' => $vendor,
                'metrics' => $metrics,
                'overall_score' => $this->performanceService->calculateOverallScore($metrics),
            ];
        })->sortByDesc('overall_score')->take(10);

        // Get performance trends
        $trends = $this->getPerformanceTrends($months);

        return response()->json([
            'top_performers' => $topVendors,
            'trends' => $trends,
            'summary' => [
                'total_vendors' => Vendor::count(),
                'active_vendors' => Vendor::whereHas('purchaseOrders', function ($query) use ($months) {
                    $query->where('created_at', '>=', \Carbon\Carbon::now()->subMonths($months));
                })->count(),
                'average_score' => $topVendors->avg('overall_score'),
            ],
        ]);
    }

    /**
     * Record manual vendor rating.
     */
    public function rateVendor(Vendor $vendor, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
            'criteria' => 'array',
            'criteria.on_time_delivery' => 'nullable|numeric|min:1|max:5',
            'criteria.quality' => 'nullable|numeric|min:1|max:5',
            'criteria.communication' => 'nullable|numeric|min:1|max:5',
            'criteria.price' => 'nullable|numeric|min:1|max:5',
        ]);

        $this->performanceService->recordManualRating($vendor->id, array_merge($validated, [
            'rated_by' => $request->user()->id,
        ]));

        return response()->json(['message' => 'Vendor rating recorded successfully']);
    }

    /**
     * Get performance trends over time.
     */
    private function getPerformanceTrends(int $months): array
    {
        $trends = [];

        for ($i = $months; $i >= 1; $i--) {
            $periodStart = \Carbon\Carbon::now()->subMonths($i);
            $periodEnd = \Carbon\Carbon::now()->subMonths($i - 1);

            $purchaseOrders = \Nexus\Procurement\Models\PurchaseOrder::whereBetween('created_at', [$periodStart, $periodEnd])->get();
            $goodsReceipts = \Nexus\Procurement\Models\GoodsReceiptNote::whereBetween('created_at', [$periodStart, $periodEnd])->get();

            if ($purchaseOrders->isNotEmpty()) {
                $onTimeDelivery = $this->calculateOnTimeDeliveryForPeriod($purchaseOrders, $goodsReceipts);
                $trends[] = [
                    'period' => $periodStart->format('M Y'),
                    'on_time_delivery_rate' => $onTimeDelivery,
                    'total_pos' => $purchaseOrders->count(),
                ];
            }
        }

        return $trends;
    }

    /**
     * Calculate on-time delivery for a specific period.
     */
    private function calculateOnTimeDeliveryForPeriod($purchaseOrders, $goodsReceipts): float
    {
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

        return $purchaseOrders->count() > 0 ? ($onTimeDeliveries / $purchaseOrders->count()) * 100 : 0;
    }
}