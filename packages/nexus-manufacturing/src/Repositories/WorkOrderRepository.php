<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Repositories;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Contracts\WorkOrderRepositoryContract;
use Nexus\Manufacturing\Enums\WorkOrderStatus;
use Nexus\Manufacturing\Models\WorkOrder;

class WorkOrderRepository implements WorkOrderRepositoryContract
{
    public function find(string $id): ?WorkOrder
    {
        return WorkOrder::with([
            'product',
            'billOfMaterial.components',
            'materialAllocations.componentProduct',
            'productionReports',
        ])->find($id);
    }

    public function findByNumber(string $workOrderNumber): ?WorkOrder
    {
        return WorkOrder::where('work_order_number', $workOrderNumber)
            ->with(['product', 'billOfMaterial'])
            ->first();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = WorkOrder::with(['product', 'billOfMaterial']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('planned_start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('planned_end_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('planned_start_date', 'desc')->get();
    }

    public function getByStatus(WorkOrderStatus $status): Collection
    {
        return WorkOrder::where('status', $status)
            ->with(['product', 'billOfMaterial'])
            ->orderBy('planned_start_date')
            ->get();
    }

    public function getByProduct(string $productId): Collection
    {
        return WorkOrder::where('product_id', $productId)
            ->with(['billOfMaterial', 'productionReports'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getActive(): Collection
    {
        return WorkOrder::whereIn('status', [
            WorkOrderStatus::RELEASED,
            WorkOrderStatus::IN_PRODUCTION,
            WorkOrderStatus::ON_HOLD,
        ])
            ->with(['product', 'billOfMaterial'])
            ->orderBy('planned_end_date')
            ->get();
    }

    public function getOverdue(): Collection
    {
        return WorkOrder::whereIn('status', [
            WorkOrderStatus::RELEASED,
            WorkOrderStatus::IN_PRODUCTION,
            WorkOrderStatus::ON_HOLD,
        ])
            ->where('planned_end_date', '<', now())
            ->with(['product', 'billOfMaterial'])
            ->orderBy('planned_end_date')
            ->get();
    }

    public function create(array $data): WorkOrder
    {
        return WorkOrder::create($data);
    }

    public function update(string $id, array $data): WorkOrder
    {
        $workOrder = $this->find($id);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$id}");
        }

        $workOrder->update($data);
        return $workOrder->fresh();
    }

    public function delete(string $id): bool
    {
        $workOrder = $this->find($id);
        
        if (!$workOrder) {
            return false;
        }

        // Only allow deletion of planned work orders
        if ($workOrder->status !== WorkOrderStatus::PLANNED) {
            throw new \RuntimeException('Can only delete planned work orders');
        }

        return $workOrder->delete();
    }

    public function changeStatus(string $id, WorkOrderStatus $newStatus): WorkOrder
    {
        $workOrder = $this->find($id);
        
        if (!$workOrder) {
            throw new \RuntimeException("Work order not found: {$id}");
        }

        $workOrder->status = $newStatus;
        
        // Update timestamps based on status
        if ($newStatus === WorkOrderStatus::IN_PRODUCTION && !$workOrder->actual_start_date) {
            $workOrder->actual_start_date = now();
        }
        
        if ($newStatus === WorkOrderStatus::COMPLETED && !$workOrder->actual_end_date) {
            $workOrder->actual_end_date = now();
        }

        $workOrder->save();
        return $workOrder->fresh();
    }

    public function getDueWithinDays(int $days): Collection
    {
        $dueDate = now()->addDays($days);

        return WorkOrder::whereIn('status', [
            WorkOrderStatus::PLANNED,
            WorkOrderStatus::RELEASED,
            WorkOrderStatus::IN_PRODUCTION,
        ])
            ->where('planned_end_date', '<=', $dueDate)
            ->where('planned_end_date', '>=', now())
            ->with(['product', 'billOfMaterial'])
            ->orderBy('planned_end_date')
            ->get();
    }
}
