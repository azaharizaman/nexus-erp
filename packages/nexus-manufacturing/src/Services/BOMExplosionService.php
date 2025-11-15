<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Services;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Contracts\BillOfMaterialRepositoryContract;
use Nexus\Manufacturing\Contracts\BOMExplosionServiceContract;

class BOMExplosionService implements BOMExplosionServiceContract
{
    public function __construct(
        protected BillOfMaterialRepositoryContract $bomRepository
    ) {}

    public function explode(string $bomId, float $quantity = 1.0, int $maxDepth = 50): Collection
    {
        return $this->bomRepository->explode($bomId, $quantity);
    }

    public function calculateNetRequirements(string $bomId, float $quantity): Collection
    {
        $grossRequirements = $this->explode($bomId, $quantity);

        // For now, return gross requirements
        // In full implementation, this would:
        // 1. Check on-hand inventory
        // 2. Check on-order quantities (from purchase orders)
        // 3. Calculate net requirements (gross - on hand - on order)
        // 4. Consider safety stock
        
        return $grossRequirements->map(function ($item) {
            return array_merge($item, [
                'on_hand' => 0, // Would query inventory
                'on_order' => 0, // Would query purchase orders
                'net_requirement' => $item['total_quantity_required'],
            ]);
        });
    }

    public function getWhereUsed(string $componentProductId): Collection
    {
        return $this->bomRepository->getWhereUsed($componentProductId);
    }

    public function validateNoCircularReferences(string $bomId): bool
    {
        return !$this->bomRepository->hasCircularReference($bomId);
    }

    public function calculateBOMCost(string $bomId, float $quantity = 1.0): array
    {
        $explodedComponents = $this->explode($bomId, $quantity);

        $totalCost = 0;
        $breakdown = [];

        foreach ($explodedComponents as $component) {
            $componentCost = ($component['product']->standard_cost ?? 0) * $component['total_quantity_required'];
            $totalCost += $componentCost;

            $breakdown[] = [
                'product_id' => $component['component_product_id'],
                'product_code' => $component['product']->product_code,
                'description' => $component['product']->description,
                'quantity' => $component['total_quantity_required'],
                'unit_cost' => $component['product']->standard_cost ?? 0,
                'total_cost' => $componentCost,
            ];
        }

        return [
            'material_cost' => $totalCost,
            'component_breakdown' => $breakdown,
        ];
    }
}
