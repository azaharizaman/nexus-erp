<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Models\ProductionCosting;

interface ProductionCostingServiceContract
{
    /**
     * Calculate production costing for a work order.
     * 
     * @param string $workOrderId
     * @return ProductionCosting
     */
    public function calculateCosting(string $workOrderId): ProductionCosting;

    /**
     * Get standard cost for a work order (before production).
     * 
     * @param string $workOrderId
     * @return array ['material', 'labor', 'overhead', 'total']
     */
    public function getStandardCost(string $workOrderId): array;

    /**
     * Get actual cost for a work order (after production).
     * 
     * @param string $workOrderId
     * @return array ['material', 'labor', 'overhead', 'total']
     */
    public function getActualCost(string $workOrderId): array;

    /**
     * Get cost variance analysis.
     * 
     * @param string $workOrderId
     * @return array ['material_variance', 'labor_variance', 'overhead_variance', 'total_variance']
     */
    public function getVarianceAnalysis(string $workOrderId): array;

    /**
     * Calculate cost per unit.
     * 
     * @param string $workOrderId
     * @return float
     */
    public function getCostPerUnit(string $workOrderId): float;
}
