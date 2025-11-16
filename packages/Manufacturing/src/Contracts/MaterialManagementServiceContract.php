<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Nexus\Manufacturing\Models\MaterialAllocation;

interface MaterialManagementServiceContract
{
    /**
     * Issue materials to a work order.
     * 
     * @param string $workOrderId
     * @param array $materials [['product_id', 'quantity', 'lot_number', 'location'], ...]
     * @return array MaterialAllocation[]
     */
    public function issueMaterials(string $workOrderId, array $materials): array;

    /**
     * Backflush materials (auto-deduct based on production report).
     * 
     * @param string $workOrderId
     * @param float $quantityProduced
     * @return array MaterialAllocation[]
     */
    public function backflushMaterials(string $workOrderId, float $quantityProduced): array;

    /**
     * Return unused materials from work order.
     * 
     * @param string $allocationId
     * @param float $quantity
     * @return MaterialAllocation
     */
    public function returnMaterial(string $allocationId, float $quantity): MaterialAllocation;

    /**
     * Get material consumption variance for a work order.
     * 
     * @param string $workOrderId
     * @return array ['component_id' => ['required', 'consumed', 'variance'], ...]
     */
    public function getMaterialVariance(string $workOrderId): array;

    /**
     * Check material availability for a work order.
     * 
     * @param string $workOrderId
     * @return array ['available' => bool, 'shortages' => array]
     */
    public function checkMaterialAvailability(string $workOrderId): array;
}
