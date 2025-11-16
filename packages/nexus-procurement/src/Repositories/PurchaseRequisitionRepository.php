<?php

declare(strict_types=1);

namespace Nexus\Procurement\Repositories;

use Nexus\Procurement\Contracts\PurchaseRequisitionRepositoryContract;
use Nexus\Procurement\Models\PurchaseRequisition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Purchase Requisition Repository
 *
 * Data access layer for purchase requisition operations.
 */
class PurchaseRequisitionRepository implements PurchaseRequisitionRepositoryContract
{
    /**
     * Create a new purchase requisition
     *
     * @param array $data
     * @return PurchaseRequisition
     */
    public function create(array $data): PurchaseRequisition
    {
        return PurchaseRequisition::create($data);
    }

    /**
     * Find a purchase requisition by ID
     *
     * @param string $id
     * @return PurchaseRequisition|null
     */
    public function find(string $id): ?PurchaseRequisition
    {
        return PurchaseRequisition::with(['items', 'requester', 'department'])->find($id);
    }

    /**
     * Find purchase requisitions by tenant
     *
     * @param string $tenantId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function findByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseRequisition::where('tenant_id', $tenantId)
            ->with(['items', 'requester', 'department']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['requester_id'])) {
            $query->where('requester_id', $filters['requester_id']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find purchase requisitions by requester
     *
     * @param string $requesterId
     * @param array $filters
     * @return Collection
     */
    public function findByRequester(string $requesterId, array $filters = []): Collection
    {
        $query = PurchaseRequisition::where('requester_id', $requesterId)
            ->with(['items', 'department']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Find purchase requisitions by department
     *
     * @param string $departmentId
     * @param array $filters
     * @return Collection
     */
    public function findByDepartment(string $departmentId, array $filters = []): Collection
    {
        $query = PurchaseRequisition::where('department_id', $departmentId)
            ->with(['items', 'requester']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Update a purchase requisition
     *
     * @param string $id
     * @param array $data
     * @return PurchaseRequisition
     */
    public function update(string $id, array $data): PurchaseRequisition
    {
        $requisition = $this->find($id);
        if ($requisition) {
            $requisition->update($data);
        }
        return $requisition;
    }

    /**
     * Delete a purchase requisition
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $requisition = $this->find($id);
        return $requisition ? $requisition->delete() : false;
    }

    /**
     * Get purchase requisitions pending approval
     *
     * @param string $tenantId
     * @return Collection
     */
    public function getPendingApproval(string $tenantId): Collection
    {
        return PurchaseRequisition::where('tenant_id', $tenantId)
            ->where('status', 'pending_approval')
            ->with(['items', 'requester', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get purchase requisitions by status
     *
     * @param string $tenantId
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $tenantId, string $status): Collection
    {
        return PurchaseRequisition::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->with(['items', 'requester', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get purchase requisitions within date range
     *
     * @param string $tenantId
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getByDateRange(string $tenantId, string $startDate, string $endDate): Collection
    {
        return PurchaseRequisition::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['items', 'requester', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get purchase requisitions by total amount range
     *
     * @param string $tenantId
     * @param float $minAmount
     * @param float $maxAmount
     * @return Collection
     */
    public function getByAmountRange(string $tenantId, float $minAmount, float $maxAmount): Collection
    {
        return PurchaseRequisition::where('tenant_id', $tenantId)
            ->whereHas('items', function ($query) use ($minAmount, $maxAmount) {
                $query->selectRaw('SUM(quantity * unit_price) as total')
                      ->havingRaw('SUM(quantity * unit_price) BETWEEN ? AND ?', [$minAmount, $maxAmount]);
            })
            ->with(['items', 'requester', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}