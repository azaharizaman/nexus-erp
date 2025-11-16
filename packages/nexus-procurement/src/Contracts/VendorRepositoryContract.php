<?php

namespace Nexus\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Nexus\Procurement\Models\Vendor;

interface VendorRepositoryContract
{
    /**
     * Create a new vendor
     *
     * @param array $data
     * @return Vendor
     */
    public function create(array $data): Vendor;

    /**
     * Find a vendor by ID
     *
     * @param string $id
     * @return Vendor|null
     */
    public function find(string $id): ?Vendor;

    /**
     * Find vendors by tenant
     *
     * @param string $tenantId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function findByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find vendors by category
     *
     * @param string $tenantId
     * @param string $category
     * @return Collection
     */
    public function findByCategory(string $tenantId, string $category): Collection;

    /**
     * Find vendors by status
     *
     * @param string $tenantId
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $tenantId, string $status): Collection;

    /**
     * Search vendors by name or code
     *
     * @param string $tenantId
     * @param string $query
     * @return Collection
     */
    public function search(string $tenantId, string $query): Collection;

    /**
     * Update a vendor
     *
     * @param string $id
     * @param array $data
     * @return Vendor
     */
    public function update(string $id, array $data): Vendor;

    /**
     * Delete a vendor
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Get active vendors
     *
     * @param string $tenantId
     * @return Collection
     */
    public function getActive(string $tenantId): Collection;

    /**
     * Get vendors with performance ratings
     *
     * @param string $tenantId
     * @param float $minRating
     * @return Collection
     */
    public function getByPerformanceRating(string $tenantId, float $minRating): Collection;

    /**
     * Get vendors by payment terms
     *
     * @param string $tenantId
     * @param string $paymentTerms
     * @return Collection
     */
    public function getByPaymentTerms(string $tenantId, string $paymentTerms): Collection;

    /**
     * Get vendor performance statistics
     *
     * @param string $vendorId
     * @return array
     */
    public function getPerformanceStats(string $vendorId): array;
}