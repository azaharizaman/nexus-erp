<?php

declare(strict_types=1);

namespace Nexus\Procurement\Repositories;

use Nexus\Procurement\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Purchase Order Repository
 *
 * Data access layer for purchase order operations.
 */
class PurchaseOrderRepository
{
    /**
     * Find PO by ID.
     */
    public function find(string $id): ?PurchaseOrder
    {
        return PurchaseOrder::with(['items', 'vendor', 'requisition'])->find($id);
    }

    /**
     * Find PO by number.
     */
    public function findByNumber(string $number, string $tenantId): ?PurchaseOrder
    {
        return PurchaseOrder::where('tenant_id', $tenantId)
            ->where('po_number', $number)
            ->with(['items', 'vendor', 'requisition'])
            ->first();
    }

    /**
     * Get POs for vendor.
     */
    public function getForVendor(string $vendorId, string $tenantId): Collection
    {
        return PurchaseOrder::where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId)
            ->with(['items', 'requisition'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending POs for approval.
     */
    public function getPendingForApproval(string $userId, string $tenantId): Collection
    {
        // This would be more complex in real implementation
        return PurchaseOrder::where('tenant_id', $tenantId)
            ->where('status', 'pending_approval')
            ->with(['items', 'vendor', 'requisition'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get POs pending receipt.
     */
    public function getPendingReceipt(string $tenantId): Collection
    {
        return PurchaseOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'partially_received'])
            ->with(['items', 'vendor'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    /**
     * Create new PO.
     */
    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }

    /**
     * Update PO.
     */
    public function update(PurchaseOrder $po, array $data): bool
    {
        return $po->update($data);
    }

    /**
     * Delete PO.
     */
    public function delete(PurchaseOrder $po): bool
    {
        return $po->delete();
    }
}