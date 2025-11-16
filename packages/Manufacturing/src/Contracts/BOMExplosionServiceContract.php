<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Illuminate\Support\Collection;

interface BOMExplosionServiceContract
{
    /**
     * Explode a BOM to get all component requirements.
     * 
     * @param string $bomId
     * @param float $quantity Quantity to produce
     * @param int $maxDepth Maximum levels to explode (prevent infinite loops)
     * @return Collection Collection of exploded components with quantities
     */
    public function explode(string $bomId, float $quantity = 1.0, int $maxDepth = 50): Collection;

    /**
     * Calculate net material requirements for a BOM.
     * 
     * @param string $bomId
     * @param float $quantity
     * @return Collection Net requirements after considering on-hand inventory
     */
    public function calculateNetRequirements(string $bomId, float $quantity): Collection;

    /**
     * Get where-used report (which BOMs use a specific component).
     * 
     * @param string $componentProductId
     * @return Collection
     */
    public function getWhereUsed(string $componentProductId): Collection;

    /**
     * Validate BOM for circular references.
     * 
     * @param string $bomId
     * @return bool
     */
    public function validateNoCircularReferences(string $bomId): bool;

    /**
     * Calculate total BOM cost (material cost rollup).
     * 
     * @param string $bomId
     * @param float $quantity
     * @return array ['material_cost' => float, 'component_breakdown' => array]
     */
    public function calculateBOMCost(string $bomId, float $quantity = 1.0): array;
}
