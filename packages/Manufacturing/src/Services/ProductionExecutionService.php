<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Nexus\Manufacturing\Contracts\ProductionExecutionServiceContract;
use Nexus\Manufacturing\Contracts\ProductionReportRepositoryContract;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Models\ProductionReport;
use Nexus\Manufacturing\Models\WorkOrder;

class ProductionExecutionService implements ProductionExecutionServiceContract
{
    public function __construct(
        protected WorkOrderRepositoryContract $workOrderRepository,
        protected ProductionReportRepositoryContract $productionReportRepository
    ) {}

    public function startProduction(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->canStartProduction()) {
            throw new \RuntimeException('Work order cannot start production in current status');
        }

        return $this->workOrderRepository->changeStatus($workOrderId, WorkOrderStatus::IN_PRODUCTION);
    }

    public function reportProduction(string $workOrderId, array $data): ProductionReport
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->status->isActive()) {
            throw new \RuntimeException('Cannot report production on inactive work order');
        }

        // Create production report
        $data['work_order_id'] = $workOrderId;
        $data['report_date'] = $data['report_date'] ?? now();
        
        $report = $this->productionReportRepository->create($data);

        // Update work order quantities
        $workOrder->quantity_completed += $data['quantity_completed'] ?? 0;
        $workOrder->quantity_scrapped += $data['quantity_scrapped'] ?? 0;
        $workOrder->save();

        return $report;
    }

    public function completeWorkOrder(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->canComplete()) {
            throw new \RuntimeException('Work order cannot be completed. Check status and quantity produced.');
        }

        return $this->workOrderRepository->changeStatus($workOrderId, WorkOrderStatus::COMPLETED);
    }

    public function pauseWorkOrder(string $workOrderId, string $reason): WorkOrder
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->canPause()) {
            throw new \RuntimeException('Work order cannot be paused in current status');
        }

        // Store reason in notes or separate field
        $this->workOrderRepository->update($workOrderId, [
            'notes' => ($workOrder->notes ?? '') . "\n[PAUSED] {$reason}",
        ]);

        return $this->workOrderRepository->changeStatus($workOrderId, WorkOrderStatus::ON_HOLD);
    }

    public function resumeWorkOrder(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->canResume()) {
            throw new \RuntimeException('Work order cannot be resumed in current status');
        }

        return $this->workOrderRepository->changeStatus($workOrderId, WorkOrderStatus::IN_PRODUCTION);
    }

    public function cancelWorkOrder(string $workOrderId, string $reason): WorkOrder
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        if (!$workOrder->canCancel()) {
            throw new \RuntimeException('Work order cannot be cancelled in current status');
        }

        // Store cancellation reason
        $this->workOrderRepository->update($workOrderId, [
            'notes' => ($workOrder->notes ?? '') . "\n[CANCELLED] {$reason}",
        ]);

        return $this->workOrderRepository->changeStatus($workOrderId, WorkOrderStatus::CANCELLED);
    }

    public function getWorkOrderProgress(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->find($workOrderId);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$workOrderId}");
        }

        $productionStats = $this->productionReportRepository->getTotalProduction($workOrderId);

        return [
            'work_order_number' => $workOrder->work_order_number,
            'status' => $workOrder->status->value,
            'status_label' => $workOrder->status->label(),
            'quantity_ordered' => $workOrder->quantity_ordered,
            'quantity_completed' => $workOrder->quantity_completed,
            'quantity_scrapped' => $workOrder->quantity_scrapped,
            'remaining_qty' => $workOrder->getRemainingQuantity(),
            'completion_pct' => $workOrder->getCompletionPercentage(),
            'is_overdue' => $workOrder->isOverdue(),
            'planned_start' => $workOrder->planned_start_date?->format('Y-m-d'),
            'planned_end' => $workOrder->planned_end_date?->format('Y-m-d'),
            'actual_start' => $workOrder->actual_start_date?->format('Y-m-d'),
            'actual_end' => $workOrder->actual_end_date?->format('Y-m-d'),
            'production_reports_count' => $productionStats['report_count'],
            'total_labor_hours' => $productionStats['total_labor_hours'],
            'average_scrap_pct' => round($productionStats['average_scrap_pct'], 2),
        ];
    }
}
