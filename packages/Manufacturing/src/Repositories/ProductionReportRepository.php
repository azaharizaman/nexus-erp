<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Repositories;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Contracts\ProductionReportRepositoryContract;
use Nexus\Manufacturing\Models\ProductionReport;

class ProductionReportRepository implements ProductionReportRepositoryContract
{
    public function find(string $id): ?ProductionReport
    {
        return ProductionReport::with(['workOrder', 'operation'])->find($id);
    }

    public function getByWorkOrder(string $workOrderId): Collection
    {
        return ProductionReport::where('work_order_id', $workOrderId)
            ->with(['operation'])
            ->orderBy('report_date', 'desc')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return ProductionReport::whereBetween('report_date', [$startDate, $endDate])
            ->with(['workOrder.product'])
            ->orderBy('report_date', 'desc')
            ->get();
    }

    public function create(array $data): ProductionReport
    {
        return ProductionReport::create($data);
    }

    public function update(string $id, array $data): ProductionReport
    {
        $report = $this->find($id);
        
        if (!$report) {
            throw new \RuntimeException("Production report not found: {$id}");
        }

        $report->update($data);
        return $report->fresh();
    }

    public function delete(string $id): bool
    {
        $report = $this->find($id);
        
        if (!$report) {
            return false;
        }

        return $report->delete();
    }

    public function getTotalProduction(string $workOrderId): array
    {
        $reports = $this->getByWorkOrder($workOrderId);

        return [
            'total_completed' => $reports->sum('quantity_completed'),
            'total_scrapped' => $reports->sum('quantity_scrapped'),
            'total_produced' => $reports->sum(fn($r) => $r->getTotalQuantity()),
            'total_labor_hours' => $reports->sum('labor_hours'),
            'report_count' => $reports->count(),
            'average_scrap_pct' => $reports->avg(fn($r) => $r->getScrapPercentage()),
        ];
    }
}
