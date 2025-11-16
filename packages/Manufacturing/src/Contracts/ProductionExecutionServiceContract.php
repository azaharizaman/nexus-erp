<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Models\ProductionReport;
use Nexus\Manufacturing\Models\WorkOrder;

interface ProductionExecutionServiceContract
{
    /**
     * Start production on a work order.
     * 
     * @param string $workOrderId
     * @return WorkOrder
     */
    public function startProduction(string $workOrderId): WorkOrder;

    /**
     * Report production output.
     * 
     * @param string $workOrderId
     * @param array $data ['quantity_completed', 'quantity_scrapped', 'labor_hours', etc.]
     * @return ProductionReport
     */
    public function reportProduction(string $workOrderId, array $data): ProductionReport;

    /**
     * Complete a work order.
     * 
     * @param string $workOrderId
     * @return WorkOrder
     */
    public function completeWorkOrder(string $workOrderId): WorkOrder;

    /**
     * Pause/hold a work order.
     * 
     * @param string $workOrderId
     * @param string $reason
     * @return WorkOrder
     */
    public function pauseWorkOrder(string $workOrderId, string $reason): WorkOrder;

    /**
     * Resume a paused work order.
     * 
     * @param string $workOrderId
     * @return WorkOrder
     */
    public function resumeWorkOrder(string $workOrderId): WorkOrder;

    /**
     * Cancel a work order.
     * 
     * @param string $workOrderId
     * @param string $reason
     * @return WorkOrder
     */
    public function cancelWorkOrder(string $workOrderId, string $reason): WorkOrder;

    /**
     * Get work order progress/status.
     * 
     * @param string $workOrderId
     * @return array ['completion_pct', 'remaining_qty', 'status', etc.]
     */
    public function getWorkOrderProgress(string $workOrderId): array;
}
