<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Services\GoodsReceiptService;
use Nexus\Procurement\Http\Requests\CreateGoodsReceiptRequest;
use Nexus\Procurement\Http\Resources\GoodsReceiptResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Goods Receipt Controller
 *
 * API endpoints for goods receipt management.
 */
class GoodsReceiptController
{
    public function __construct(
        private GoodsReceiptService $grService
    ) {}

    /**
     * Display a listing of goods receipts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = GoodsReceiptNote::with(['purchaseOrder', 'vendor', 'items']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('po_id')) {
            $query->where('po_id', $request->po_id);
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }

        $goodsReceipts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => GoodsReceiptResource::collection($goodsReceipts),
            'meta' => [
                'total' => $goodsReceipts->total(),
                'per_page' => $goodsReceipts->perPage(),
                'current_page' => $goodsReceipts->currentPage(),
                'last_page' => $goodsReceipts->lastPage(),
            ],
        ]);
    }

    /**
     * Create goods receipt from purchase order.
     */
    public function createFromPurchaseOrder(CreateGoodsReceiptRequest $request): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::findOrFail($request->po_id);

        try {
            $goodsReceipt = $this->grService->createFromPurchaseOrder($purchaseOrder, $request->validated());

            return response()->json([
                'data' => new GoodsReceiptResource(
                    $goodsReceipt->load(['purchaseOrder', 'vendor', 'items'])
                ),
                'message' => 'Goods receipt created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified goods receipt.
     */
    public function show(GoodsReceiptNote $goodsReceipt): JsonResponse
    {
        return response()->json([
            'data' => new GoodsReceiptResource(
                $goodsReceipt->load(['purchaseOrder', 'vendor', 'items', 'vendorInvoice'])
            ),
        ]);
    }

    /**
     * Confirm goods receipt.
     */
    public function confirm(GoodsReceiptNote $goodsReceipt): JsonResponse
    {
        try {
            $this->grService->confirm($goodsReceipt);

            return response()->json([
                'data' => new GoodsReceiptResource(
                    $goodsReceipt->load(['purchaseOrder', 'vendor', 'items'])
                ),
                'message' => 'Goods receipt confirmed.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject goods receipt.
     */
    public function reject(Request $request, GoodsReceiptNote $goodsReceipt): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->grService->reject($goodsReceipt, $request->reason);

            return response()->json([
                'data' => new GoodsReceiptResource(
                    $goodsReceipt->load(['purchaseOrder', 'vendor', 'items'])
                ),
                'message' => 'Goods receipt rejected.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get receipt summary for purchase order.
     */
    public function receiptSummary(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $summary = $this->grService->getReceiptSummary($purchaseOrder);

        return response()->json([
            'data' => $summary,
        ]);
    }
}