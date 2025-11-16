<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Models\QualityInspection;

interface QualityInspectionRepositoryContract
{
    /**
     * Find quality inspection by ID.
     */
    public function find(string $id): ?QualityInspection;

    /**
     * Get all quality inspections for a work order.
     */
    public function getByWorkOrder(string $workOrderId): Collection;

    /**
     * Get quality inspections by lot number.
     */
    public function getByLotNumber(string $lotNumber): Collection;

    /**
     * Get failed inspections requiring disposition.
     */
    public function getFailedPendingDisposition(): Collection;

    /**
     * Create a new quality inspection.
     */
    public function create(array $data): QualityInspection;

    /**
     * Update a quality inspection.
     */
    public function update(string $id, array $data): QualityInspection;

    /**
     * Delete a quality inspection.
     */
    public function delete(string $id): bool;
}
