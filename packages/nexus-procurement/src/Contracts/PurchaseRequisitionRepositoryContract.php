<?php

namespace Nexus\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Nexus\Procurement\Models\PurchaseRequisition;

interface PurchaseRequisitionRepositoryContract
{
    /**
     * Create a new purchase requisition
     *
     * @param array $data
     * @return PurchaseRequisition
     */
    public function create(array $data): PurchaseRequisition;

    /**
     * Find a purchase requisition by ID
     *
     * @param string $id
     * @return PurchaseRequisition|null
     */
    public function find(string $id): ?PurchaseRequisition;

    /**
     * Find purchase requisitions by tenant
     *
     * @param string $tenantId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function findByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find purchase requisitions by requester
     *
     * @param string $requesterId
     * @param array $filters
     * @return Collection
     */
    public function findByRequester(string $requesterId, array $filters = []): Collection;

    /**
     * Find purchase requisitions by department
     *
     * @param string $departmentId
     * @param array $filters
     * @return Collection
     */
    public function findByDepartment(string $departmentId, array $filters = []): Collection;

    /**
     * Update a purchase requisition
     *
     * @param string $id
     * @param array $data
     * @return PurchaseRequisition
     */
    public function update(string $id, array $data): PurchaseRequisition;

    /**
     * Delete a purchase requisition
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Get purchase requisitions pending approval
     *
     * @param string $tenantId
     * @return Collection
     */
    public function getPendingApproval(string $tenantId): Collection;

    /**
     * Get purchase requisitions by status
     *
     * @param string $tenantId
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $tenantId, string $status): Collection;

    /**
     * Get purchase requisitions within date range
     *
     * @param string $tenantId
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getByDateRange(string $tenantId, string $startDate, string $endDate): Collection;

    /**
     * Get purchase requisitions by total amount range
     *
     * @param string $tenantId
     * @param float $minAmount
     * @param float $maxAmount
     * @return Collection
     */
    public function getByAmountRange(string $tenantId, float $minAmount, float $maxAmount): Collection;
}