<?php

declare(strict_types=1);

namespace Nexus\Procurement\Repositories;

use Nexus\Procurement\Models\GoodsReceiptNote;
use Illuminate\Database\Eloquent\Collection;

/**
 * Goods Receipt Repository
 *
 * Data access layer for goods receipt operations.
 */
class GoodsReceiptRepository
{
    /**
     * Find GRN by ID.
     */
    public function find(string $id): ?GoodsReceiptNote
    {
        return GoodsReceiptNote::with(['items', 'purchaseOrder', 'receiver'])->find($id);
    }

    /**
     * Find GRN by number.
     */
    public function findByNumber(string $number, string $tenantId): ?GoodsReceiptNote
    {
        return GoodsReceiptNote::where('tenant_id', $tenantId)
            ->where('grn_number', $number)
            ->with(['items', 'purchaseOrder', 'receiver'])
            ->first();
    }

    /**
     * Get GRNs for PO.
     */
    public function getForPurchaseOrder(string $poId, string $tenantId): Collection
    {
        return GoodsReceiptNote::where('tenant_id', $tenantId)
            ->where('po_id', $poId)
            ->with(['items', 'receiver'])
            ->orderBy('received_at', 'desc')
            ->get();
    }

    /**
     * Get recent GRNs for user.
     */
    public function getRecentForUser(string $userId, string $tenantId, int $limit = 10): Collection
    {
        return GoodsReceiptNote::where('tenant_id', $tenantId)
            ->where('received_by', $userId)
            ->with(['items', 'purchaseOrder'])
            ->orderBy('received_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create new GRN.
     */
    public function create(array $data): GoodsReceiptNote
    {
        return GoodsReceiptNote::create($data);
    }

    /**
     * Update GRN.
     */
    public function update(GoodsReceiptNote $grn, array $data): bool
    {
        return $grn->update($data);
    }

    /**
     * Delete GRN.
     */
    public function delete(GoodsReceiptNote $grn): bool
    {
        return $grn->delete();
    }
}