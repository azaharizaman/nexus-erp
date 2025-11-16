<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\RequestForQuotation;
use Nexus\Procurement\Models\VendorQuote;
use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Services\RFQManagementService;
use Nexus\Procurement\Http\Requests\CreateRFQRequest;
use Nexus\Procurement\Http\Requests\InviteVendorsRequest;
use Nexus\Procurement\Http\Requests\SubmitVendorQuoteRequest;
use Nexus\Procurement\Http\Requests\SelectWinnerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * RFQ Controller
 *
 * Handles Request for Quotation operations including creation, vendor invitation,
 * quote submission, comparison, and winner selection.
 */
class RFQController extends Controller
{
    public function __construct(
        private RFQManagementService $rfqService
    ) {}

    /**
     * List RFQs with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RequestForQuotation::with(['requisition', 'invitedVendors', 'vendorQuotes.vendor'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->requisition_id, fn($q) => $q->where('requisition_id', $request->requisition_id))
            ->when($request->search, function ($q) use ($request) {
                $q->where('rfq_number', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%");
            })
            ->orderBy('created_at', 'desc');

        $rfqs = $query->paginate($request->per_page ?? 15);

        return response()->json($rfqs);
    }

    /**
     * Get specific RFQ with full details.
     */
    public function show(RequestForQuotation $rfq): JsonResponse
    {
        $rfq->load([
            'requisition',
            'items',
            'invitedVendors',
            'vendorQuotes.vendor',
            'vendorQuotes.items.rfqItem',
            'selectedVendor',
            'selectedQuote',
        ]);

        return response()->json($rfq);
    }

    /**
     * Create RFQ from approved requisition.
     */
    public function store(CreateRFQRequest $request): JsonResponse
    {
        $requisition = \Nexus\Procurement\Models\PurchaseRequisition::findOrFail($request->requisition_id);

        $rfq = $this->rfqService->createFromRequisition($requisition, $request->validated());

        return response()->json($rfq->load(['requisition', 'items']), 201);
    }

    /**
     * Invite vendors to RFQ.
     */
    public function inviteVendors(RequestForQuotation $rfq, InviteVendorsRequest $request): JsonResponse
    {
        $this->rfqService->inviteVendors($rfq, $request->vendor_ids);

        return response()->json([
            'message' => 'Vendors invited successfully',
            'invited_count' => count($request->vendor_ids),
        ]);
    }

    /**
     * Send RFQ to invited vendors.
     */
    public function sendToVendors(RequestForQuotation $rfq): JsonResponse
    {
        $this->rfqService->sendToVendors($rfq);

        return response()->json([
            'message' => 'RFQ sent to vendors successfully',
            'rfq' => $rfq->load(['invitedVendors']),
        ]);
    }

    /**
     * Get quote comparison for RFQ.
     */
    public function compareQuotes(RequestForQuotation $rfq): JsonResponse
    {
        $comparison = $this->rfqService->compareQuotes($rfq);

        return response()->json($comparison);
    }

    /**
     * Select winning vendor and create purchase order.
     */
    public function selectWinner(RequestForQuotation $rfq, SelectWinnerRequest $request): JsonResponse
    {
        $winningQuote = VendorQuote::findOrFail($request->winning_quote_id);

        $purchaseOrder = $this->rfqService->selectWinner($rfq, $winningQuote, $request->validated());

        return response()->json([
            'message' => 'Winner selected and purchase order created',
            'rfq' => $rfq->load(['selectedVendor', 'selectedQuote']),
            'purchase_order' => $purchaseOrder->load(['vendor', 'items']),
        ]);
    }

    /**
     * Close RFQ without selection.
     */
    public function close(RequestForQuotation $rfq, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->rfqService->closeRFQ($rfq, $request->reason);

        return response()->json([
            'message' => 'RFQ closed successfully',
            'rfq' => $rfq,
        ]);
    }

    /**
     * Get RFQ statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = [
            'total_rfqs' => RequestForQuotation::count(),
            'open_rfqs' => RequestForQuotation::where('status', 'sent')->count(),
            'closed_rfqs' => RequestForQuotation::where('status', 'closed')->count(),
            'average_quotes_per_rfq' => RequestForQuotation::withCount('vendorQuotes')->get()->avg('vendor_quotes_count'),
            'recent_rfqs' => RequestForQuotation::with(['requisition'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }
}