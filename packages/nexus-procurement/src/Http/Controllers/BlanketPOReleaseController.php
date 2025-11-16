<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\BlanketPurchaseOrder;
use Nexus\Procurement\Models\BlanketPORelease;
use Nexus\Procurement\Services\BlanketPurchaseOrderService;
use Nexus\Procurement\Http\Requests\CreateBlanketPOReleaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Blanket PO Release Controller
 *
 * Handles blanket purchase order release operations.
 */
class BlanketPOReleaseController extends Controller
{
    public function __construct(
        private BlanketPurchaseOrderService $blanketPOService
    ) {}

    /**
     * List releases for a blanket PO.
     */
    public function index(BlanketPurchaseOrder $blanketPO, Request $request): JsonResponse
    {
        $query = $blanketPO->releases()
            ->with(['creator', 'approver', 'items.blanketPOItem'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc');

        $releases = $query->paginate($request->per_page ?? 15);

        return response()->json($releases);
    }

    /**
     * Get specific release with full details.
     */
    public function show(BlanketPORelease $release): JsonResponse
    {
        $release->load([
            'blanketPurchaseOrder.vendor',
            'creator',
            'approver',
            'items.blanketPOItem',
            'purchaseOrder',
        ]);

        return response()->json($release);
    }

    /**
     * Create a new release against a blanket PO.
     */
    public function store(BlanketPurchaseOrder $blanketPO, CreateBlanketPOReleaseRequest $request): JsonResponse
    {
        $release = $this->blanketPOService->createRelease($blanketPO, $request->validated());

        return response()->json($release->load(['creator', 'items.blanketPOItem']), 201);
    }

    /**
     * Submit release for approval.
     */
    public function submitForApproval(BlanketPORelease $release): JsonResponse
    {
        $this->blanketPOService->submitReleaseForApproval($release);

        return response()->json([
            'message' => 'Release submitted for approval',
            'release' => $release,
        ]);
    }

    /**
     * Approve a release.
     */
    public function approve(BlanketPORelease $release): JsonResponse
    {
        $this->blanketPOService->approveRelease($release);

        return response()->json([
            'message' => 'Release approved successfully',
            'release' => $release->load(['approver']),
        ]);
    }

    /**
     * Reject a release.
     */
    public function reject(BlanketPORelease $release, Request $request): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $release->update([
            'status' => \Nexus\Procurement\Enums\BlanketPOReleaseStatus::REJECTED,
            'notes' => ($release->notes ? $release->notes . "\n\n" : '') .
                      "Rejected: {$request->rejection_reason}",
        ]);

        return response()->json([
            'message' => 'Release rejected',
            'release' => $release,
        ]);
    }

    /**
     * Convert approved release to purchase order.
     */
    public function convertToPO(BlanketPORelease $release): JsonResponse
    {
        $purchaseOrder = $this->blanketPOService->convertReleaseToPO($release);

        return response()->json([
            'message' => 'Release converted to purchase order',
            'release' => $release->load(['purchaseOrder']),
            'purchase_order' => $purchaseOrder->load(['vendor', 'items']),
        ]);
    }

    /**
     * Cancel a release.
     */
    public function cancel(BlanketPORelease $release, Request $request): JsonResponse
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if ($release->status === \Nexus\Procurement\Enums\BlanketPOReleaseStatus::CONVERTED_TO_PO) {
            return response()->json(['error' => 'Cannot cancel release that has been converted to PO'], 422);
        }

        $release->update([
            'status' => \Nexus\Procurement\Enums\BlanketPOReleaseStatus::CANCELLED,
            'notes' => ($release->notes ? $release->notes . "\n\n" : '') .
                      "Cancelled: {$request->cancellation_reason}",
        ]);

        return response()->json([
            'message' => 'Release cancelled successfully',
            'release' => $release,
        ]);
    }

    /**
     * Get pending releases for approval.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $query = BlanketPORelease::with(['blanketPurchaseOrder.vendor', 'creator', 'items'])
            ->where('status', \Nexus\Procurement\Enums\BlanketPOReleaseStatus::PENDING_APPROVAL)
            ->orderBy('created_at', 'asc');

        $releases = $query->paginate($request->per_page ?? 15);

        return response()->json($releases);
    }
}