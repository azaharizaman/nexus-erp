<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\PurchaseRequisition;
use Nexus\Procurement\Services\RequisitionApprovalService;
use Nexus\Procurement\Http\Requests\StorePurchaseRequisitionRequest;
use Nexus\Procurement\Http\Requests\UpdatePurchaseRequisitionRequest;
use Nexus\Procurement\Http\Resources\PurchaseRequisitionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Purchase Requisition Controller
 *
 * API endpoints for purchase requisition management.
 */
class PurchaseRequisitionController
{
    public function __construct(
        private RequisitionApprovalService $approvalService
    ) {}

    /**
     * Display a listing of purchase requisitions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseRequisition::with(['department', 'requestedBy', 'items']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requisitions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => PurchaseRequisitionResource::collection($requisitions),
            'meta' => [
                'total' => $requisitions->total(),
                'per_page' => $requisitions->perPage(),
                'current_page' => $requisitions->currentPage(),
                'last_page' => $requisitions->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created purchase requisition.
     */
    public function store(StorePurchaseRequisitionRequest $request): JsonResponse
    {
        $requisition = PurchaseRequisition::create([
            ...$request->validated(),
            'requisition_number' => app(\Nexus\Sequencing\Services\SequencingService::class)
                ->generateNumber('purchase_requisition'),
            'requested_by' => auth()->id(),
        ]);

        return response()->json([
            'data' => new PurchaseRequisitionResource($requisition->load(['department', 'requestedBy', 'items'])),
            'message' => 'Purchase requisition created successfully.',
        ], 201);
    }

    /**
     * Display the specified purchase requisition.
     */
    public function show(PurchaseRequisition $requisition): JsonResponse
    {
        return response()->json([
            'data' => new PurchaseRequisitionResource(
                $requisition->load(['department', 'requestedBy', 'items', 'purchaseOrder'])
            ),
        ]);
    }

    /**
     * Update the specified purchase requisition.
     */
    public function update(UpdatePurchaseRequisitionRequest $request, PurchaseRequisition $requisition): JsonResponse
    {
        if ($requisition->status !== \Nexus\Procurement\Enums\RequisitionStatus::DRAFT) {
            return response()->json([
                'message' => 'Only draft requisitions can be updated.',
            ], 422);
        }

        $requisition->update($request->validated());

        return response()->json([
            'data' => new PurchaseRequisitionResource($requisition->load(['department', 'requestedBy', 'items'])),
            'message' => 'Purchase requisition updated successfully.',
        ]);
    }

    /**
     * Remove the specified purchase requisition.
     */
    public function destroy(PurchaseRequisition $requisition): JsonResponse
    {
        if (!in_array($requisition->status, [
            \Nexus\Procurement\Enums\RequisitionStatus::DRAFT,
            \Nexus\Procurement\Enums\RequisitionStatus::REJECTED,
            \Nexus\Procurement\Enums\RequisitionStatus::CANCELLED
        ])) {
            return response()->json([
                'message' => 'Cannot delete requisition in current status.',
            ], 422);
        }

        $requisition->delete();

        return response()->json([
            'message' => 'Purchase requisition deleted successfully.',
        ]);
    }

    /**
     * Submit requisition for approval.
     */
    public function submitForApproval(PurchaseRequisition $requisition): JsonResponse
    {
        try {
            $this->approvalService->submitForApproval($requisition);

            return response()->json([
                'data' => new PurchaseRequisitionResource(
                    $requisition->load(['department', 'requestedBy', 'items'])
                ),
                'message' => 'Purchase requisition submitted for approval.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve requisition.
     */
    public function approve(Request $request, PurchaseRequisition $requisition): JsonResponse
    {
        try {
            $this->approvalService->approve($requisition, $request->comments);

            return response()->json([
                'data' => new PurchaseRequisitionResource(
                    $requisition->load(['department', 'requestedBy', 'items'])
                ),
                'message' => 'Purchase requisition approved.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject requisition.
     */
    public function reject(Request $request, PurchaseRequisition $requisition): JsonResponse
    {
        $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        try {
            $this->approvalService->reject($requisition, $request->comments);

            return response()->json([
                'data' => new PurchaseRequisitionResource(
                    $requisition->load(['department', 'requestedBy', 'items'])
                ),
                'message' => 'Purchase requisition rejected.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel requisition.
     */
    public function cancel(Request $request, PurchaseRequisition $requisition): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->approvalService->cancel($requisition, $request->reason);

            return response()->json([
                'data' => new PurchaseRequisitionResource(
                    $requisition->load(['department', 'requestedBy', 'items'])
                ),
                'message' => 'Purchase requisition cancelled.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get approval history.
     */
    public function approvalHistory(PurchaseRequisition $requisition): JsonResponse
    {
        $history = $this->approvalService->getApprovalHistory($requisition);

        return response()->json([
            'data' => $history,
        ]);
    }
}