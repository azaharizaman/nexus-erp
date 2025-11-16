<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Goods Receipt Item Model
 *
 * Represents quantities received for a specific PO item.
 */
class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'grn_id',
        'po_item_id',
        'quantity_received',
        'quantity_accepted',
        'quantity_rejected',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
        'quantity_accepted' => 'decimal:4',
        'quantity_rejected' => 'decimal:4',
    ];

    /**
     * Get the GRN this item belongs to.
     */
    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id');
    }

    /**
     * Get the PO item.
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }

    /**
     * Check if item has rejections.
     */
    public function hasRejections(): bool
    {
        return $this->quantity_rejected > 0;
    }

    /**
     * Get acceptance rate.
     */
    public function getAcceptanceRateAttribute(): float
    {
        if ($this->quantity_received == 0) {
            return 0;
        }

        return ($this->quantity_accepted / $this->quantity_received) * 100;
    }
}