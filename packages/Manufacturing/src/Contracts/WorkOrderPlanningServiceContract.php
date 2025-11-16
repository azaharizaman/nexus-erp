<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Models\WorkOrder;

interface WorkOrderPlanningServiceContract
{
    /**
     * Create a new work order.
     * 
     * @param array $data
     * @return WorkOrder
     */
    public function createWorkOrder(array $data): WorkOrder;

    /**
     * Release work order to production floor.
     * 
     * @param string $workOrderId
     * @return WorkOrder
     */
    public function releaseWorkOrder(string $workOrderId): WorkOrder;

    /**
     * Calculate material requirements and create allocations.
     * 
     * @param string $workOrderId
     * @return array Material allocations created
     */
    public function allocateMaterials(string $workOrderId): array;

    /**
     * Calculate work order lead time based on BOM and routing.
     * 
     * @param string $productId
     * @param float $quantity
     * @return array ['lead_time_days' => int, 'breakdown' => array]
     */
    public function calculateLeadTime(string $productId, float $quantity): array;

    /**
     * Validate work order can be created (BOM exists, materials available, etc.).
     * 
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateWorkOrder(array $data): array;

    /**
     * Schedule work order (calculate planned dates).
     * 
     * @param string $workOrderId
     * @param \DateTimeInterface|null $startDate
     * @return WorkOrder
     */
    public function scheduleWorkOrder(string $workOrderId, ?\DateTimeInterface $startDate = null): WorkOrder;
}
