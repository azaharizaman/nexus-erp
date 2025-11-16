<?php

declare(strict_types=1);

namespace Nexus\Procurement\Repositories;

use Nexus\Procurement\Models\PurchaseRequisition;
use Illuminate\Database\Eloquent\Collection;

/**
 * Purchase Requisition Repository
 *
 * Data access layer for purchase requisition operations.
 */
class PurchaseRequisitionRepository
{
    /**
     * Find requisition by ID.
     */
    public function find(string $id): ?PurchaseRequisition
    {
        return PurchaseRequisition::with(['items', 'requester', 'department'])->find($id);
    }

    /**
     * Find requisition by number.
     */
    public function findByNumber(string $number, string $tenantId): ?PurchaseRequisition
    {
        return PurchaseRequisition::where('tenant_id', $tenantId)
            ->where('requisition_number', $number)
            ->with(['items', 'requester', 'department'])
            ->first();
    }

    /**
     * Get requisitions for user.
     */
    public function getForUser(string $userId, string $tenantId): Collection
    {
        return PurchaseRequisition::where('tenant_id', $tenantId)
            ->where('requester_id', $userId)
            ->with(['items', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending requisitions for approval.
     */
    public function getPendingForApproval(string $userId, string $tenantId): Collection
    {
        // This would be more complex in real implementation
        // based on approval matrix and user permissions
        return PurchaseRequisition::where('tenant_id', $tenantId)
            ->where('status', 'pending_approval')
            ->with(['items', 'requester', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create new requisition.
     */
    public function create(array $data): PurchaseRequisition
    {
        return PurchaseRequisition::create($data);
    }

    /**
     * Update requisition.
     */
    public function update(PurchaseRequisition $requisition, array $data): bool
    {
        return $requisition->update($data);
    }

    /**
     * Delete requisition.
     */
    public function delete(PurchaseRequisition $requisition): bool
    {
        return $requisition->delete();
    }
}