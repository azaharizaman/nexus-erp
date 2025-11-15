<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Models\BillOfMaterial;

interface BillOfMaterialRepositoryContract
{
    /**
     * Find BOM by ID.
     */
    public function find(string $id): ?BillOfMaterial;

    /**
     * Find active BOM for a product.
     */
    public function findActiveForProduct(string $productId): ?BillOfMaterial;

    /**
     * Get all BOMs for a product.
     */
    public function getByProduct(string $productId): Collection;

    /**
     * Create a new BOM.
     */
    public function create(array $data): BillOfMaterial;

    /**
     * Update a BOM.
     */
    public function update(string $id, array $data): BillOfMaterial;

    /**
     * Delete a BOM.
     */
    public function delete(string $id): bool;

    /**
     * Explode BOM to get all components (multi-level).
     */
    public function explode(string $bomId, float $quantity = 1.0): Collection;

    /**
     * Get where-used for a component (which BOMs use this component).
     */
    public function getWhereUsed(string $componentProductId): Collection;

    /**
     * Check for circular references in BOM.
     */
    public function hasCircularReference(string $bomId): bool;
}
