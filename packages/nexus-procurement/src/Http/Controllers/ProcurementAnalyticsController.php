<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Services\ProcurementAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Procurement Analytics Controller
 *
 * API endpoints for procurement analytics and reporting.
 */
class ProcurementAnalyticsController extends Controller
{
    public function __construct(
        private ProcurementAnalyticsService $analyticsService
    ) {}

    /**
     * Get analytics dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $dashboardData = $this->analyticsService->getDashboardData($months);

        return response()->json($dashboardData);
    }

    /**
     * Get spend analysis report.
     */
    public function spendAnalysis(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $startDate = \Carbon\Carbon::now()->subMonths($months);

        $spendAnalysis = $this->analyticsService->getSpendAnalysis($startDate);

        return response()->json($spendAnalysis);
    }

    /**
     * Get supplier performance report.
     */
    public function supplierPerformance(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $startDate = \Carbon\Carbon::now()->subMonths($months);

        $performanceSummary = $this->analyticsService->getSupplierPerformanceSummary($startDate);

        return response()->json($performanceSummary);
    }

    /**
     * Get procurement efficiency metrics.
     */
    public function efficiencyMetrics(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $startDate = \Carbon\Carbon::now()->subMonths($months);

        $efficiencyMetrics = $this->analyticsService->getProcurementEfficiencyMetrics($startDate);

        return response()->json($efficiencyMetrics);
    }

    /**
     * Get compliance metrics report.
     */
    public function complianceMetrics(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $startDate = \Carbon\Carbon::now()->subMonths($months);

        $complianceMetrics = $this->analyticsService->getComplianceMetrics($startDate);

        return response()->json($complianceMetrics);
    }

    /**
     * Get procurement trends.
     */
    public function trends(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $trends = $this->analyticsService->getProcurementTrends($months);

        return response()->json($trends);
    }

    /**
     * Get category-wise spend analysis.
     */
    public function categorySpend(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $startDate = \Carbon\Carbon::now()->subMonths($months);

        $categorySpend = $this->analyticsService->getCategorySpendAnalysis($startDate);

        return response()->json($categorySpend);
    }

    /**
     * Get savings analysis.
     */
    public function savingsAnalysis(Request $request): JsonResponse
    {
        $months = $request->get('months', 12);
        $savingsAnalysis = $this->analyticsService->getSavingsAnalysis($months);

        return response()->json($savingsAnalysis);
    }

    /**
     * Export analytics report.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $type = $request->get('type', 'dashboard');
        $months = $request->get('months', 12);

        // In a real implementation, this would generate and return a PDF or Excel file
        // For now, return JSON data that could be used for report generation
        $data = match($type) {
            'spend' => $this->analyticsService->getSpendAnalysis(\Carbon\Carbon::now()->subMonths($months)),
            'performance' => $this->analyticsService->getSupplierPerformanceSummary(\Carbon\Carbon::now()->subMonths($months)),
            'efficiency' => $this->analyticsService->getProcurementEfficiencyMetrics(\Carbon\Carbon::now()->subMonths($months)),
            'compliance' => $this->analyticsService->getComplianceMetrics(\Carbon\Carbon::now()->subMonths($months)),
            default => $this->analyticsService->getDashboardData($months),
        };

        // Create a temporary JSON file for export
        $filename = "procurement_{$type}_report_" . now()->format('Y-m-d') . ".json";
        $tempPath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        \Illuminate\Support\Facades\Storage::disk('local')->makeDirectory('temp');

        \Illuminate\Support\Facades\Storage::disk('local')->put('temp/' . $filename, json_encode($data, JSON_PRETTY_PRINT));

        return response()->download(storage_path('app/temp/' . $filename), $filename, [
            'Content-Type' => 'application/json',
        ])->deleteFileAfterSend();
    }
}