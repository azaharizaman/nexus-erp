<?php

declare(strict_types=1);

namespace Nexus\Procurement\Repositories;

use Nexus\Procurement\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

/**
 * Vendor Repository
 *
 * Data access layer for vendor operations.
 */
class VendorRepository
{
    /**
     * Find vendor by ID.
     */
    public function find(string $id): ?Vendor
    {
        return Vendor::find($id);
    }

    /**
     * Find vendor by code.
     */
    public function findByCode(string $code, string $tenantId): ?Vendor
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('vendor_code', $code)
            ->first();
    }

    /**
     * Get all active vendors for tenant.
     */
    public function getActiveForTenant(string $tenantId): Collection
    {
        return Vendor::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Search vendors by name or code.
     */
    public function search(string $query, string $tenantId): Collection
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
     * Create new vendor.
     */
    public function create(array $data): Vendor
    {
        return Vendor::create($data);
    }

    /**
     * Update vendor.
     */
    public function update(Vendor $vendor, array $data): bool
    {
        return $vendor->update($data);
    }

    /**
     * Delete vendor.
     */
    public function delete(Vendor $vendor): bool
    {
        return $vendor->delete();
    }
}