<?php

declare(strict_types=1);

namespace Nexus\Procurement\Rules;

use Nexus\Procurement\Models\PurchaseRequisition;
use Nexus\Procurement\Models\PurchaseOrder;
use Nexus\Procurement\Models\GoodsReceiptNote;
use Nexus\Procurement\Models\VendorInvoice;
use Illuminate\Support\Facades\Auth;

/**
 * Separation of Duties Rules
 *
 * Enforces SOD (Separation of Duties) to prevent conflicts of interest
 * and ensure proper internal controls in procurement processes.
 */
class SeparationOfDutiesRules
{
    /**
     * Check if user can approve a requisition.
     */
    public function canApproveRequisition(PurchaseRequisition $requisition, int $userId): array
    {
        $violations = [];

        // Requester cannot approve their own requisition
        if ($requisition->requester_id === $userId) {
            $violations[] = [
                'rule' => 'requester_cannot_approve',
                'message' => 'Requester cannot approve their own requisition',
                'severity' => 'critical',
            ];
        }

        // Department manager cannot approve if they are the requester
        if ($requisition->requester && $requisition->requester->department_id) {
            $userDepartment = $this->getUserDepartment($userId);
            if ($userDepartment === $requisition->requester->department_id) {
                $violations[] = [
                    'rule' => 'department_manager_cannot_approve_own_dept',
                    'message' => 'Department manager cannot approve requisitions from their own department',
                    'severity' => 'warning',
                ];
            }
        }

        return $violations;
    }

    /**
     * Check if user can approve a purchase order.
     */
    public function canApprovePurchaseOrder(PurchaseOrder $purchaseOrder, int $userId): array
    {
        $violations = [];

        // PO creator cannot approve their own PO
        if ($purchaseOrder->created_by === $userId) {
            $violations[] = [
                'rule' => 'creator_cannot_approve_po',
                'message' => 'Purchase order creator cannot approve their own PO',
                'severity' => 'critical',
            ];
        }

        // If PO came from requisition, check requisition requester
        if ($purchaseOrder->requisition && $purchaseOrder->requisition->requester_id === $userId) {
            $violations[] = [
                'rule' => 'requisition_requester_cannot_approve_po',
                'message' => 'Original requisition requester cannot approve the resulting PO',
                'severity' => 'critical',
            ];
        }

        return $violations;
    }

    /**
     * Check if user can create goods receipt for a PO.
     */
    public function canCreateGoodsReceipt(PurchaseOrder $purchaseOrder, int $userId): array
    {
        $violations = [];

        // PO creator cannot receive goods
        if ($purchaseOrder->created_by === $userId) {
            $violations[] = [
                'rule' => 'creator_cannot_receive',
                'message' => 'Purchase order creator cannot receive goods',
                'severity' => 'critical',
            ];
        }

        // PO approver cannot receive goods
        if ($purchaseOrder->approved_by === $userId) {
            $violations[] = [
                'rule' => 'approver_cannot_receive',
                'message' => 'Purchase order approver cannot receive goods',
                'severity' => 'critical',
            ];
        }

        return $violations;
    }

    /**
     * Check if user can authorize payment for an invoice.
     */
    public function canAuthorizePayment(VendorInvoice $invoice, int $userId): array
    {
        $violations = [];

        // Invoice submitter cannot authorize payment (if applicable)
        // This would be relevant if we track who submitted the invoice

        // PO creator cannot authorize payment
        if ($invoice->purchaseOrder && $invoice->purchaseOrder->created_by === $userId) {
            $violations[] = [
                'rule' => 'po_creator_cannot_authorize_payment',
                'message' => 'Purchase order creator cannot authorize payment',
                'severity' => 'critical',
            ];
        }

        // GRN creator cannot authorize payment
        if ($invoice->goodsReceiptNote && $invoice->goodsReceiptNote->received_by === $userId) {
            $violations[] = [
                'rule' => 'grn_creator_cannot_authorize_payment',
                'message' => 'Goods receipt creator cannot authorize payment',
                'severity' => 'critical',
            ];
        }

        return $violations;
    }

    /**
     * Check if user can create a vendor quote for an RFQ.
     */
    public function canSubmitVendorQuote(\Nexus\Procurement\Models\RequestForQuotation $rfq, int $userId): array
    {
        $violations = [];

        // RFQ creator cannot submit quotes for their own RFQ
        if ($rfq->created_by === $userId) {
            $violations[] = [
                'rule' => 'rfq_creator_cannot_quote',
                'message' => 'RFQ creator cannot submit quotes for their own RFQ',
                'severity' => 'critical',
            ];
        }

        return $violations;
    }

    /**
     * Check if user can evaluate/select winner for an RFQ.
     */
    public function canSelectRFQWinner(\Nexus\Procurement\Models\RequestForQuotation $rfq, int $userId): array
    {
        $violations = [];

        // RFQ creator cannot select winner
        if ($rfq->created_by === $userId) {
            $violations[] = [
                'rule' => 'rfq_creator_cannot_select_winner',
                'message' => 'RFQ creator cannot select the winning vendor',
                'severity' => 'critical',
            ];
        }

        return $violations;
    }

    /**
     * Get all SOD violations for a user across all procurement activities.
     */
    public function getAllViolationsForUser(int $userId): array
    {
        $violations = [];

        // Check requisitions where user is approver but also requester
        $requisitions = PurchaseRequisition::where('approved_by', $userId)
            ->where('requester_id', $userId)
            ->get();

        foreach ($requisitions as $requisition) {
            $violations[] = [
                'type' => 'requisition',
                'entity_id' => $requisition->id,
                'entity_number' => $requisition->requisition_number,
                'violations' => $this->canApproveRequisition($requisition, $userId),
            ];
        }

        // Check POs where user is approver but also creator
        $pos = PurchaseOrder::where('approved_by', $userId)
            ->where('created_by', $userId)
            ->get();

        foreach ($pos as $po) {
            $violations[] = [
                'type' => 'purchase_order',
                'entity_id' => $po->id,
                'entity_number' => $po->po_number,
                'violations' => $this->canApprovePurchaseOrder($po, $userId),
            ];
        }

        // Check GRNs where user is receiver but also PO creator/approver
        $grns = GoodsReceiptNote::where('received_by', $userId)
            ->whereHas('purchaseOrder', function ($query) use ($userId) {
                $query->where('created_by', $userId)
                      ->orWhere('approved_by', $userId);
            })
            ->with('purchaseOrder')
            ->get();

        foreach ($grns as $grn) {
            $violations[] = [
                'type' => 'goods_receipt',
                'entity_id' => $grn->id,
                'entity_number' => $grn->grn_number,
                'violations' => $this->canCreateGoodsReceipt($grn->purchaseOrder, $userId),
            ];
        }

        return $violations;
    }

    /**
     * Check if any critical SOD violations exist for an action.
     */
    public function hasCriticalViolations(array $violations): bool
    {
        return collect($violations)->contains(function ($violation) {
            return ($violation['severity'] ?? 'warning') === 'critical';
        });
    }

    /**
     * Get user department (helper method).
     */
    private function getUserDepartment(int $userId): ?int
    {
        // This would integrate with nexus-backoffice to get user's department
        // For now, return null - implement when backoffice integration is available
        return null;
    }

    /**
     * Log SOD violation attempt.
     */
    public function logViolationAttempt(string $action, array $violations, int $userId, ?int $entityId = null): void
    {
        // Log to audit system
        \Nexus\AuditLog\Facades\AuditLog::create([
            'action' => 'sod_violation_attempt',
            'entity_type' => 'procurement_' . $action,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'old_values' => null,
            'new_values' => [
                'violations' => $violations,
                'action_attempted' => $action,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}