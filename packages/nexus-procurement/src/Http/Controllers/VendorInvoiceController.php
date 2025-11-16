<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\VendorInvoice;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Services\ThreeWayMatchService;
use Nexus\Procurement\Http\Requests\CreateVendorInvoiceRequest;
use Nexus\Procurement\Http\Resources\VendorInvoiceResource;
use Nexus\Procurement\Http\Resources\ThreeWayMatchResultResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor Invoice Controller
 *
 * API endpoints for vendor invoice management and three-way matching.
 */
class VendorInvoiceController
{
    public function __construct(
        private ThreeWayMatchService $matchService
    ) {}

    /**
     * Display a listing of vendor invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VendorInvoice::with(['purchaseOrder', 'goodsReceiptNote', 'vendor']);

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
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $invoices = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => VendorInvoiceResource::collection($invoices),
            'meta' => [
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    /**
     * Create vendor invoice.
     */
    public function store(CreateVendorInvoiceRequest $request): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::findOrFail($request->po_id);
        $goodsReceiptNote = GoodsReceiptNote::findOrFail($request->grn_id);

        $invoice = VendorInvoice::create([
            ...$request->validated(),
            'invoice_number' => app(\Nexus\Sequencing\Services\SequencingService::class)
                ->generateNumber('vendor_invoice'),
            'status' => \Nexus\Procurement\Enums\InvoiceStatus::RECEIVED,
        ]);

        return response()->json([
            'data' => new VendorInvoiceResource(
                $invoice->load(['purchaseOrder', 'goodsReceiptNote', 'vendor', 'items'])
            ),
            'message' => 'Vendor invoice created successfully.',
        ], 201);
    }

    /**
     * Display the specified vendor invoice.
     */
    public function show(VendorInvoice $vendorInvoice): JsonResponse
    {
        return response()->json([
            'data' => new VendorInvoiceResource(
                $vendorInvoice->load([
                    'purchaseOrder',
                    'goodsReceiptNote',
                    'vendor',
                    'items',
                    'threeWayMatchResult'
                ])
            ),
        ]);
    }

    /**
     * Perform three-way match.
     */
    public function performThreeWayMatch(VendorInvoice $vendorInvoice): JsonResponse
    {
        try {
            $matchResult = $this->matchService->performMatch($vendorInvoice);

            return response()->json([
                'data' => new ThreeWayMatchResultResource($matchResult),
                'message' => 'Three-way match completed.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get three-way match result.
     */
    public function matchResult(VendorInvoice $vendorInvoice): JsonResponse
    {
        $matchResult = $vendorInvoice->threeWayMatchResult;

        if (!$matchResult) {
            return response()->json([
                'message' => 'No match result found.',
            ], 404);
        }

        return response()->json([
            'data' => new ThreeWayMatchResultResource($matchResult),
        ]);
    }

    /**
     * Approve payment for matched invoice.
     */
    public function approvePayment(VendorInvoice $vendorInvoice): JsonResponse
    {
        $matchResult = $vendorInvoice->threeWayMatchResult;

        if (!$matchResult) {
            return response()->json([
                'message' => 'Three-way match must be performed before payment approval.',
            ], 422);
        }

        if (!$this->matchService->canAuthorizePayment($matchResult)) {
            return response()->json([
                'message' => 'Invoice does not meet payment authorization criteria.',
            ], 422);
        }

        $vendorInvoice->update([
            'status' => \Nexus\Procurement\Enums\InvoiceStatus::PAYMENT_APPROVED,
            'payment_approved_at' => now(),
            'payment_approved_by' => auth()->id(),
        ]);

        return response()->json([
            'data' => new VendorInvoiceResource(
                $vendorInvoice->load(['purchaseOrder', 'goodsReceiptNote', 'vendor', 'items'])
            ),
            'message' => 'Payment approved for vendor invoice.',
        ]);
    }

    /**
     * Reject payment for invoice.
     */
    public function rejectPayment(Request $request, VendorInvoice $vendorInvoice): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $vendorInvoice->update([
            'status' => \Nexus\Procurement\Enums\InvoiceStatus::PAYMENT_REJECTED,
            'payment_rejected_at' => now(),
            'payment_rejected_by' => auth()->id(),
            'payment_rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'data' => new VendorInvoiceResource(
                $vendorInvoice->load(['purchaseOrder', 'goodsReceiptNote', 'vendor', 'items'])
            ),
            'message' => 'Payment rejected for vendor invoice.',
        ]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Request $request, VendorInvoice $vendorInvoice): JsonResponse
    {
        if ($vendorInvoice->status !== \Nexus\Procurement\Enums\InvoiceStatus::PAYMENT_APPROVED) {
            return response()->json([
                'message' => 'Only payment approved invoices can be marked as paid.',
            ], 422);
        }

        $request->validate([
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
            'payment_method' => 'required|string|max:100',
        ]);

        $vendorInvoice->update([
            'status' => \Nexus\Procurement\Enums\InvoiceStatus::PAID,
            'payment_date' => $request->payment_date,
            'payment_reference' => $request->payment_reference,
            'payment_method' => $request->payment_method,
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);

        return response()->json([
            'data' => new VendorInvoiceResource(
                $vendorInvoice->load(['purchaseOrder', 'goodsReceiptNote', 'vendor', 'items'])
            ),
            'message' => 'Vendor invoice marked as paid.',
        ]);
    }
}