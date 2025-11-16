<?php

declare(strict_types=1);

namespace Nexus\Procurement\Repositories;

use Nexus\Procurement\Contracts\VendorRepositoryContract;
use Nexus\Procurement\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Vendor Repository
 *
 * Data access layer for vendor operations.
 */
class VendorRepository implements VendorRepositoryContract
{
    /**
     * Create a new vendor
     *
     * @param array $data
     * @return Vendor
     */
    public function create(array $data): Vendor
    {
        return Vendor::create($data);
    }

    /**
     * Find a vendor by ID
     *
     * @param string $id
     * @return Vendor|null
     */
    public function find(string $id): ?Vendor
    {
        return Vendor::find($id);
    }

    /**
     * Find vendors by tenant
     *
     * @param string $tenantId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function findByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Vendor::where('tenant_id', $tenantId);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['payment_terms'])) {
            $query->where('payment_terms', $filters['payment_terms']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find vendors by category
     *
     * @param string $tenantId
     * @param string $category
     * @return Collection
     */
    public function findByCategory(string $tenantId, string $category): Collection
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('category', $category)
            ->orderBy('name')
            ->get();
    }

    /**
     * Find vendors by status
     *
     * @param string $tenantId
     * @param string $status
     * @return Collection
     */
    public function findByStatus(string $tenantId, string $status): Collection
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search vendors by name or code
     *
     * @param string $tenantId
     * @param string $query
     * @return Collection
     */
    public function search(string $tenantId, string $query): Collection
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('vendor_code', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Update a vendor
     *
     * @param string $id
     * @param array $data
     * @return Vendor
     */
    public function update(string $id, array $data): Vendor
    {
        $vendor = $this->find($id);
        if ($vendor) {
            $vendor->update($data);
        }
        return $vendor;
    }

    /**
     * Delete a vendor
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $vendor = $this->find($id);
        return $vendor ? $vendor->delete() : false;
    }

    /**
     * Get active vendors
     *
     * @param string $tenantId
     * @return Collection
     */
    public function getActive(string $tenantId): Collection
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get vendors with performance ratings
     *
     * @param string $tenantId
     * @param float $minRating
     * @return Collection
     */
    public function getByPerformanceRating(string $tenantId, float $minRating): Collection
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('performance_rating', '>=', $minRating)
            ->orderBy('performance_rating', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get vendors by payment terms
     *
     * @param string $tenantId
     * @param string $paymentTerms
     * @return Collection
     */
    public function getByPaymentTerms(string $tenantId, string $paymentTerms): Collection
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('payment_terms', $paymentTerms)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get vendor performance statistics
     *
     * @param string $vendorId
     * @return array
     */
    public function getPerformanceStats(string $vendorId): array
    {
        $vendor = $this->find($vendorId);

        if (!$vendor) {
            return [];
        }

        // This would typically aggregate data from related models
        // For now, return basic stats
        return [
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'performance_rating' => $vendor->performance_rating ?? 0,
            'total_orders' => $vendor->purchaseOrders()->count(),
            'on_time_delivery_rate' => $vendor->on_time_delivery_rate ?? 0,
            'quality_rating' => $vendor->quality_rating ?? 0,
            'last_order_date' => $vendor->purchaseOrders()->max('created_at'),
        ];
    }
}