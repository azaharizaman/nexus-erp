<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Repositories;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Contracts\BillOfMaterialRepositoryContract;
use Nexus\Manufacturing\Models\BillOfMaterial;
use Nexus\Manufacturing\Models\BOMItem;

class BillOfMaterialRepository implements BillOfMaterialRepositoryContract
{
    public function find(string $id): ?BillOfMaterial
    {
        return BillOfMaterial::with(['components.componentProduct', 'product'])->find($id);
    }

    public function findActiveForProduct(string $productId): ?BillOfMaterial
    {
        return BillOfMaterial::where('product_id', $productId)
            ->where('status', 'active')
            ->with(['components.componentProduct'])
            ->latest('effective_date')
            ->first();
    }

    public function getByProduct(string $productId): Collection
    {
        return BillOfMaterial::where('product_id', $productId)
            ->with(['components.componentProduct'])
            ->orderBy('effective_date', 'desc')
            ->get();
    }

    public function create(array $data): BillOfMaterial
    {
        return BillOfMaterial::create($data);
    }

    public function update(string $id, array $data): BillOfMaterial
    {
        $bom = $this->find($id);
        
        if (!$bom) {
            throw new \RuntimeException("BOM not found: {$id}");
        }

        $bom->update($data);
        return $bom->fresh();
    }

    public function delete(string $id): bool
    {
        $bom = $this->find($id);
        
        if (!$bom) {
            return false;
        }

        return $bom->delete();
    }

    public function explode(string $bomId, float $quantity = 1.0): Collection
    {
        $exploded = collect();
        $this->explodeRecursive($bomId, $quantity, $exploded);
        
        // Group by component and sum quantities
        return $exploded->groupBy('component_product_id')->map(function ($items) {
            return [
                'component_product_id' => $items->first()['component_product_id'],
                'product' => $items->first()['product'],
                'total_quantity_required' => $items->sum('quantity_required'),
            ];
        })->values();
    }

    protected function explodeRecursive(string $bomId, float $quantity, Collection $exploded, int $depth = 0, array $visited = []): void
    {
        if ($depth > 50) {
            throw new \RuntimeException('BOM explosion depth exceeded (possible circular reference)');
        }

        if (in_array($bomId, $visited)) {
            throw new \RuntimeException('Circular reference detected in BOM');
        }

        $visited[] = $bomId;

        $bom = $this->find($bomId);
        
        if (!$bom) {
            return;
        }

        foreach ($bom->components as $component) {
            $requiredQuantity = $component->getTotalQuantityNeeded($quantity);

            // Check if component has its own BOM (sub-assembly)
            $subBOM = $this->findActiveForProduct($component->component_product_id);

            if ($subBOM && !$component->isPhantom()) {
                // Recursively explode sub-assembly
                $this->explodeRecursive($subBOM->id, $requiredQuantity, $exploded, $depth + 1, $visited);
            } else {
                // Add to exploded list (leaf component or phantom)
                $exploded->push([
                    'component_product_id' => $component->component_product_id,
                    'product' => $component->componentProduct,
                    'quantity_required' => $requiredQuantity,
                    'unit_of_measure' => $component->unit_of_measure,
                    'bom_item_id' => $component->id,
                ]);
            }
        }
    }

    public function getWhereUsed(string $componentProductId): Collection
    {
        return BOMItem::where('component_product_id', $componentProductId)
            ->with(['billOfMaterial.product'])
            ->get()
            ->map(function ($bomItem) {
                return [
                    'bom_id' => $bomItem->bom_id,
                    'bom' => $bomItem->billOfMaterial,
                    'parent_product' => $bomItem->billOfMaterial->product,
                    'quantity_required' => $bomItem->quantity_required,
                ];
            });
    }

    public function hasCircularReference(string $bomId): bool
    {
        try {
            $this->explode($bomId, 1.0);
            return false;
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'circular reference')) {
                return true;
            }
            throw $e;
        }
    }
}
