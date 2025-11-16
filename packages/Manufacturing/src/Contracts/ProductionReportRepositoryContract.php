<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

use Illuminate\Support\Collection;
use Nexus\Manufacturing\Models\ProductionReport;

interface ProductionReportRepositoryContract
{
    /**
     * Find production report by ID.
     */
    public function find(string $id): ?ProductionReport;

    /**
     * Get all production reports for a work order.
     */
    public function getByWorkOrder(string $workOrderId): Collection;

    /**
     * Get production reports by date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Create a new production report.
     */
    public function create(array $data): ProductionReport;

    /**
     * Update a production report.
     */
    public function update(string $id, array $data): ProductionReport;

    /**
     * Delete a production report.
     */
    public function delete(string $id): bool;

    /**
     * Get total production for a work order.
     */
    public function getTotalProduction(string $workOrderId): array;
}
