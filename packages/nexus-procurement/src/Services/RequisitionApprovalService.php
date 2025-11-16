<?php

declare(strict_types=1);

namespace Nexus\Procurement\Services;

use Nexus\Procurement\Models\PurchaseRequisition;
use Nexus\Procurement\Enums\RequisitionStatus;
use Nexus\Workflow\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;

/**
 * Requisition Approval Service
 *
 * Handles approval workflows for purchase requisitions.
 */
class RequisitionApprovalService
{
    public function __construct(
        private WorkflowService $workflowService
    ) {}

    /**
     * Submit requisition for approval.
     */
    public function submitForApproval(PurchaseRequisition $requisition): void
    {
        if ($requisition->status !== RequisitionStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft requisitions can be submitted for approval.');
        }

        // Start workflow process
        $workflowData = [
            'entity_type' => 'purchase_requisition',
            'entity_id' => $requisition->id,
            'initiator_id' => Auth::id(),
            'amount' => $requisition->total_amount,
            'department_id' => $requisition->department_id,
        ];

        $workflowId = $this->workflowService->startProcess('purchase_requisition_approval', $workflowData);

        // Update requisition status
        $requisition->update([
            'status' => RequisitionStatus::PENDING_APPROVAL,
            'workflow_id' => $workflowId,
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
        ]);
    }

    /**
     * Approve requisition.
     */
    public function approve(PurchaseRequisition $requisition, string $comments = null): void
    {
        if ($requisition->status !== RequisitionStatus::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Only pending requisitions can be approved.');
        }

        // Complete workflow step
        $this->workflowService->completeStep($requisition->workflow_id, [
            'action' => 'approve',
            'comments' => $comments,
            'approved_by' => Auth::id(),
        ]);

        // Check if workflow is complete
        if ($this->workflowService->isProcessComplete($requisition->workflow_id)) {
            $requisition->update([
                'status' => RequisitionStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Reject requisition.
     */
    public function reject(PurchaseRequisition $requisition, string $comments): void
    {
        if ($requisition->status !== RequisitionStatus::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Only pending requisitions can be rejected.');
        }

        // Complete workflow step with rejection
        $this->workflowService->completeStep($requisition->workflow_id, [
            'action' => 'reject',
            'comments' => $comments,
            'rejected_by' => Auth::id(),
        ]);

        $requisition->update([
            'status' => RequisitionStatus::REJECTED,
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
            'rejection_reason' => $comments,
        ]);
    }

    /**
     * Cancel requisition.
     */
    public function cancel(PurchaseRequisition $requisition, string $reason): void
    {
        if (in_array($requisition->status, [RequisitionStatus::APPROVED, RequisitionStatus::ORDERED])) {
            throw new \InvalidArgumentException('Approved or ordered requisitions cannot be cancelled.');
        }

        // Cancel workflow if active
        if ($requisition->workflow_id) {
            $this->workflowService->cancelProcess($requisition->workflow_id, $reason);
        }

        $requisition->update([
            'status' => RequisitionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Get approval history for requisition.
     */
    public function getApprovalHistory(PurchaseRequisition $requisition): array
    {
        if (!$requisition->workflow_id) {
            return [];
        }

        return $this->workflowService->getProcessHistory($requisition->workflow_id);
    }
}