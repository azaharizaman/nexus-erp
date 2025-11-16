<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Repositories;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Contracts\QualityInspectionRepositoryContract;
use Nexus\Manufacturing\Enums\InspectionResult;
use Nexus\Manufacturing\Models\QualityInspection;

class QualityInspectionRepository implements QualityInspectionRepositoryContract
{
    public function find(string $id): ?QualityInspection
    {
        return QualityInspection::with([
            'workOrder.product',
            'inspectionPlan.characteristics',
            'measurements.characteristic',
        ])->find($id);
    }

    public function getByWorkOrder(string $workOrderId): Collection
    {
        return QualityInspection::where('work_order_id', $workOrderId)
            ->with(['inspectionPlan', 'measurements'])
            ->orderBy('inspection_date', 'desc')
            ->get();
    }

    public function getByLotNumber(string $lotNumber): Collection
    {
        return QualityInspection::where('lot_number', $lotNumber)
            ->with(['workOrder.product', 'inspectionPlan', 'measurements'])
            ->orderBy('inspection_date', 'desc')
            ->get();
    }

    public function getFailedPendingDisposition(): Collection
    {
        return QualityInspection::where('result', InspectionResult::FAILED)
            ->whereNull('disposition')
            ->with(['workOrder.product', 'inspectionPlan'])
            ->orderBy('inspection_date')
            ->get();
    }

    public function create(array $data): QualityInspection
    {
        return QualityInspection::create($data);
    }

    public function update(string $id, array $data): QualityInspection
    {
        $inspection = $this->find($id);
        
        if (!$inspection) {
            throw new \RuntimeException("Quality inspection not found: {$id}");
        }

        $inspection->update($data);
        return $inspection->fresh();
    }

    public function delete(string $id): bool
    {
        $inspection = $this->find($id);
        
        if (!$inspection) {
            return false;
        }

        return $inspection->delete();
    }
}
