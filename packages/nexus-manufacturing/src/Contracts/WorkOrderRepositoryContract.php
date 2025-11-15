<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Models\WorkOrder;

interface WorkOrderRepositoryContract
{
    /**
     * Find work order by ID.
     */
    public function find(string $id): ?WorkOrder;

    /**
     * Find work order by work order number.
     */
    public function findByNumber(string $workOrderNumber): ?WorkOrder;

    /**
     * Get all work orders.
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Get work orders by status.
     */
    public function getByStatus(WorkOrderStatus $status): Collection;

    /**
     * Get work orders by product.
     */
    public function getByProduct(string $productId): Collection;

    /**
     * Get active work orders (released, in production, on hold).
     */
    public function getActive(): Collection;

    /**
     * Get overdue work orders.
     */
    public function getOverdue(): Collection;

    /**
     * Create a new work order.
     */
    public function create(array $data): WorkOrder;

    /**
     * Update a work order.
     */
    public function update(string $id, array $data): WorkOrder;

    /**
     * Delete a work order.
     */
    public function delete(string $id): bool;

    /**
     * Change work order status.
     */
    public function changeStatus(string $id, WorkOrderStatus $newStatus): WorkOrder;

    /**
     * Get work orders due within specified days.
     */
    public function getDueWithinDays(int $days): Collection;
}
