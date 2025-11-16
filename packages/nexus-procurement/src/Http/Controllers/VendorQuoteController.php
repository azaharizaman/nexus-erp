<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\RequestForQuotation;
use Nexus\Procurement\Models\VendorQuote;
use Nexus\Procurement\Models\Vendor;
use Nexus\Procurement\Services\RFQManagementService;
use Nexus\Procurement\Http\Requests\SubmitVendorQuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor Quote Controller
 *
 * Handles vendor quote submission and management operations.
 */
class VendorQuoteController extends Controller
{
    public function __construct(
        private RFQManagementService $rfqService
    ) {}

    /**
     * List quotes for a specific RFQ.
     */
    public function index(RequestForQuotation $rfq, Request $request): JsonResponse
    {
        $query = $rfq->vendorQuotes()
            ->with(['vendor', 'items.rfqItem'])
            ->when($request->vendor_id, fn($q) => $q->where('vendor_id', $request->vendor_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('submitted_at', 'desc');

        $quotes = $query->paginate($request->per_page ?? 15);

        return response()->json($quotes);
    }

    /**
     * Get specific vendor quote with full details.
     */
    public function show(VendorQuote $quote): JsonResponse
    {
        $quote->load([
            'rfq.requisition',
            'vendor',
            'items.rfqItem',
        ]);

        return response()->json($quote);
    }

    /**
     * Submit vendor quote for RFQ.
     */
    public function store(RequestForQuotation $rfq, SubmitVendorQuoteRequest $request): JsonResponse
    {
        // Get vendor from authenticated user or request
        $vendor = Vendor::findOrFail($request->vendor_id);

        $quote = $this->rfqService->submitVendorQuote($rfq, $vendor, $request->validated());

        return response()->json($quote->load(['vendor', 'items.rfqItem']), 201);
    }

    /**
     * Update vendor quote (before RFQ deadline).
     */
    public function update(VendorQuote $quote, SubmitVendorQuoteRequest $request): JsonResponse
    {
        if (!$quote->rfq->isOpen()) {
            return response()->json(['error' => 'RFQ is no longer open for quote updates'], 422);
        }

        // Update quote data
        $quote->update([
            'total_quoted_price' => $request->total_quoted_price,
            'delivery_days' => $request->delivery_days,
            'payment_terms' => $request->payment_terms,
            'validity_days' => $request->validity_days,
            'notes' => $request->notes,
        ]);

        // Update quote items
        foreach ($request->items as $itemData) {
            $quoteItem = $quote->items()->where('rfq_item_id', $itemData['rfq_item_id'])->first();

            if ($quoteItem) {
                $quoteItem->update([
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'delivery_days' => $itemData['delivery_days'] ?? $quote->delivery_days,
                    'alternate_offer' => $itemData['alternate_offer'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'specifications_met' => $itemData['specifications_met'] ?? true,
                ]);
            }
        }

        return response()->json($quote->load(['vendor', 'items.rfqItem']));
    }

    /**
     * Withdraw vendor quote.
     */
    public function destroy(VendorQuote $quote): JsonResponse
    {
        if (!$quote->rfq->isOpen()) {
            return response()->json(['error' => 'Cannot withdraw quote after RFQ deadline'], 422);
        }

        $quote->delete();

        return response()->json(['message' => 'Quote withdrawn successfully']);
    }

    /**
     * Get quotes submitted by a specific vendor.
     */
    public function vendorQuotes(Vendor $vendor, Request $request): JsonResponse
    {
        $query = VendorQuote::where('vendor_id', $vendor->id)
            ->with(['rfq.requisition', 'items.rfqItem'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->rfq_id, fn($q) => $q->where('rfq_id', $request->rfq_id))
            ->orderBy('submitted_at', 'desc');

        $quotes = $query->paginate($request->per_page ?? 15);

        return response()->json($quotes);
    }

    /**
     * Get pending RFQs for vendor to quote on.
     */
    public function pendingRFQs(Vendor $vendor): JsonResponse
    {
        $pendingRfqs = RequestForQuotation::whereHas('invitedVendors', function ($query) use ($vendor) {
                $query->where('vendor_id', $vendor->id)
                      ->where('response_status', 'pending');
            })
            ->where('status', 'sent')
            ->where('quote_deadline', '>', now())
            ->with(['requisition', 'items'])
            ->orderBy('quote_deadline', 'asc')
            ->get();

        return response()->json($pendingRfqs);
    }

    /**
     * Get quote statistics for vendor.
     */
    public function vendorStatistics(Vendor $vendor): JsonResponse
    {
        $stats = [
            'total_quotes' => VendorQuote::where('vendor_id', $vendor->id)->count(),
            'selected_quotes' => VendorQuote::where('vendor_id', $vendor->id)->where('status', 'selected')->count(),
            'rejected_quotes' => VendorQuote::where('vendor_id', $vendor->id)->where('status', 'rejected')->count(),
            'pending_quotes' => VendorQuote::where('vendor_id', $vendor->id)->where('status', 'submitted')->count(),
            'win_rate' => VendorQuote::where('vendor_id', $vendor->id)->count() > 0
                ? (VendorQuote::where('vendor_id', $vendor->id)->where('status', 'selected')->count() /
                   VendorQuote::where('vendor_id', $vendor->id)->count()) * 100
                : 0,
            'recent_quotes' => VendorQuote::where('vendor_id', $vendor->id)
                ->with(['rfq.requisition'])
                ->orderBy('submitted_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }
}