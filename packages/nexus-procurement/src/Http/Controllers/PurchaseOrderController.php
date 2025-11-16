<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\PurchaseRequisition;
use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Services\PurchaseOrderService;
use Nexus\Procurement\Http\Requests\CreatePurchaseOrderRequest;
use Nexus\Procurement\Http\Resources\PurchaseOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Purchase Order Controller
 *
 * API endpoints for purchase order management.
 */
class PurchaseOrderController
{
    public function __construct(
        private PurchaseOrderService $poService
    ) {}

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['requisition', 'vendor', 'items']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('requisition_id')) {
            $query->where('requisition_id', $request->requisition_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $purchaseOrders = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => PurchaseOrderResource::collection($purchaseOrders),
            'meta' => [
                'total' => $purchaseOrders->total(),
                'per_page' => $purchaseOrders->perPage(),
                'current_page' => $purchaseOrders->currentPage(),
                'last_page' => $purchaseOrders->lastPage(),
            ],
        ]);
    }

    /**
     * Create purchase order from requisition.
     */
    public function createFromRequisition(CreatePurchaseOrderRequest $request): JsonResponse
    {
        $requisition = PurchaseRequisition::findOrFail($request->requisition_id);
        $vendor = Vendor::findOrFail($request->vendor_id);

        try {
            $purchaseOrder = $this->poService->createFromRequisition($requisition, $vendor);

            return response()->json([
                'data' => new PurchaseOrderResource(
                    $purchaseOrder->load(['requisition', 'vendor', 'items'])
                ),
                'message' => 'Purchase order created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json([
            'data' => new PurchaseOrderResource(
                $purchaseOrder->load([
                    'requisition',
                    'vendor',
                    'items',
                    'goodsReceiptNotes',
                    'vendorInvoices'
                ])
            ),
        ]);
    }

    /**
     * Update the specified purchase order.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (!in_array($purchaseOrder->status, [
            \Nexus\Procurement\Enums\PurchaseOrderStatus::DRAFT,
            \Nexus\Procurement\Enums\PurchaseOrderStatus::PENDING_APPROVAL
        ])) {
            return response()->json([
                'message' => 'Cannot update purchase order in current status.',
            ], 422);
        }

        $validated = $request->validate([
            'expected_delivery_date' => 'nullable|date',
            'terms_and_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'array',
            'items.*.unit_price' => 'numeric|min:0',
            'items.*.specifications' => 'nullable|string',
        ]);

        // Update PO basic info
        $purchaseOrder->update(collect($validated)->except('items')->toArray());

        // Update items if provided
        if (isset($validated['items'])) {
            foreach ($validated['items'] as $itemId => $itemData) {
                $item = $purchaseOrder->items()->find($itemId);
                if ($item) {
                    $item->update([
                        'unit_price' => $itemData['unit_price'],
                        'total_price' => $item->quantity * $itemData['unit_price'],
                        'specifications' => $itemData['specifications'] ?? $item->specifications,
                    ]);
                }
            }
        }

        // Recalculate totals
        $this->poService->updateTotals($purchaseOrder);

        return response()->json([
            'data' => new PurchaseOrderResource(
                $purchaseOrder->load(['requisition', 'vendor', 'items'])
            ),
            'message' => 'Purchase order updated successfully.',
        ]);
    }

    /**
     * Submit purchase order for approval.
     */
    public function submitForApproval(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $this->poService->submitForApproval($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource(
                    $purchaseOrder->load(['requisition', 'vendor', 'items'])
                ),
                'message' => 'Purchase order submitted for approval.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve purchase order.
     */
    public function approve(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $this->poService->approve($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource(
                    $purchaseOrder->load(['requisition', 'vendor', 'items'])
                ),
                'message' => 'Purchase order approved.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send purchase order to vendor.
     */
    public function sendToVendor(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $this->poService->sendToVendor($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource(
                    $purchaseOrder->load(['requisition', 'vendor', 'items'])
                ),
                'message' => 'Purchase order sent to vendor.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->poService->cancel($purchaseOrder, $request->reason);

            return response()->json([
                'data' => new PurchaseOrderResource(
                    $purchaseOrder->load(['requisition', 'vendor', 'items'])
                ),
                'message' => 'Purchase order cancelled.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Close purchase order.
     */
    public function close(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $this->poService->close($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource(
                    $purchaseOrder->load(['requisition', 'vendor', 'items'])
                ),
                'message' => 'Purchase order closed.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get receipt summary.
     */
    public function receiptSummary(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $summary = $this->poService->getReceiptSummary($purchaseOrder);

        return response()->json([
            'data' => $summary,
        ]);
    }
}