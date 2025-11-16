<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\BlanketPurchaseOrder;
use Nexus\Procurement\Services\BlanketPurchaseOrderService;
use Nexus\Procurement\Http\Requests\CreateBlanketPORequest;
use Nexus\Procurement\Http\Requests\CreateBlanketPOReleaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Blanket Purchase Order Controller
 *
 * Handles blanket purchase order management operations.
 */
class BlanketPurchaseOrderController extends Controller
{
    public function __construct(
        private BlanketPurchaseOrderService $blanketPOService
    ) {}

    /**
     * List blanket purchase orders with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BlanketPurchaseOrder::with(['vendor', 'creator', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->vendor_id, fn($q) => $q->where('vendor_id', $request->vendor_id))
            ->when($request->search, function ($q) use ($request) {
                $q->where('blanket_po_number', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%");
            })
            ->orderBy('created_at', 'desc');

        $blanketPOs = $query->paginate($request->per_page ?? 15);

        return response()->json($blanketPOs);
    }

    /**
     * Get specific blanket purchase order with full details.
     */
    public function show(BlanketPurchaseOrder $blanketPO): JsonResponse
    {
        $blanketPO->load([
            'vendor',
            'creator',
            'items',
            'releases.items.blanketPOItem',
        ]);

        $utilization = $this->blanketPOService->getUtilizationSummary($blanketPO);
        $itemsUtilization = $this->blanketPOService->getItemsUtilization($blanketPO);

        return response()->json([
            'blanket_po' => $blanketPO,
            'utilization' => $utilization,
            'items_utilization' => $itemsUtilization,
        ]);
    }

    /**
     * Create a new blanket purchase order.
     */
    public function store(CreateBlanketPORequest $request): JsonResponse
    {
        $blanketPO = $this->blanketPOService->createBlanketPO($request->validated());

        return response()->json($blanketPO->load(['vendor', 'items']), 201);
    }

    /**
     * Update blanket purchase order (only if draft).
     */
    public function update(BlanketPurchaseOrder $blanketPO, CreateBlanketPORequest $request): JsonResponse
    {
        if ($blanketPO->status !== \Nexus\Procurement\Enums\BlanketPurchaseOrderStatus::DRAFT) {
            return response()->json(['error' => 'Only draft blanket POs can be updated'], 422);
        }

        // Update blanket PO
        $blanketPO->update($request->validated());

        // Update items (simplified - in real implementation, handle item updates properly)
        $blanketPO->items()->delete(); // Remove existing items
        foreach ($request->items as $index => $itemData) {
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

        return response()->json($blanketPO->load(['vendor', 'items']));
    }

    /**
     * Activate blanket purchase order.
     */
    public function activate(BlanketPurchaseOrder $blanketPO): JsonResponse
    {
        $this->blanketPOService->activateBlanketPO($blanketPO);

        return response()->json([
            'message' => 'Blanket PO activated successfully',
            'blanket_po' => $blanketPO,
        ]);
    }

    /**
     * Suspend blanket purchase order.
     */
    public function suspend(BlanketPurchaseOrder $blanketPO): JsonResponse
    {
        $blanketPO->update(['status' => \Nexus\Procurement\Enums\BlanketPurchaseOrderStatus::SUSPENDED]);

        return response()->json([
            'message' => 'Blanket PO suspended successfully',
            'blanket_po' => $blanketPO,
        ]);
    }

    /**
     * Cancel blanket purchase order.
     */
    public function cancel(BlanketPurchaseOrder $blanketPO): JsonResponse
    {
        if ($blanketPO->releases()->whereIn('status', [
            \Nexus\Procurement\Enums\BlanketPOReleaseStatus::APPROVED,
            \Nexus\Procurement\Enums\BlanketPOReleaseStatus::CONVERTED_TO_PO,
        ])->exists()) {
            return response()->json(['error' => 'Cannot cancel blanket PO with active releases'], 422);
        }

        $blanketPO->update(['status' => \Nexus\Procurement\Enums\BlanketPurchaseOrderStatus::CANCELLED]);

        return response()->json([
            'message' => 'Blanket PO cancelled successfully',
            'blanket_po' => $blanketPO,
        ]);
    }

    /**
     * Get blanket PO utilization report.
     */
    public function utilization(BlanketPurchaseOrder $blanketPO): JsonResponse
    {
        $utilization = $this->blanketPOService->getUtilizationSummary($blanketPO);
        $itemsUtilization = $this->blanketPOService->getItemsUtilization($blanketPO);

        return response()->json([
            'utilization' => $utilization,
            'items_utilization' => $itemsUtilization,
        ]);
    }

    /**
     * Get blanket PO statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = [
            'total_blanket_pos' => BlanketPurchaseOrder::count(),
            'active_blanket_pos' => BlanketPurchaseOrder::where('status', 'active')->count(),
            'expired_blanket_pos' => BlanketPurchaseOrder::where('status', 'expired')->count(),
            'total_committed_value' => BlanketPurchaseOrder::sum('total_committed_value'),
            'total_released_value' => BlanketPurchaseOrder::with('releases')->get()->sum(function ($bpo) {
                return $bpo->getTotalReleasedValue();
            }),
            'average_utilization' => BlanketPurchaseOrder::with('releases')->get()->avg(function ($bpo) {
                return $bpo->getUtilizationPercentage();
            }),
            'recent_blanket_pos' => BlanketPurchaseOrder::with(['vendor'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }
}