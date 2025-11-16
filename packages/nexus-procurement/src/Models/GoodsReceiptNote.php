<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Goods Receipt Note Model
 *
 * Records the receipt of goods against a purchase order.
 */
class GoodsReceiptNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'grn_number',
        'po_id',
        'received_by',
        'received_at',
        'delivery_note_number',
        'status',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    /**
     * Get the purchase order.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Get the receiver.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    /**
     * Get the GRN items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'grn_id');
    }

    /**
     * Check if GRN is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Calculate total received quantities.
     */
    public function getTotalReceivedAttribute(): float
    {
        return $this->items->sum('quantity_received');
    }

    /**
     * Calculate total accepted quantities.
     */
    public function getTotalAcceptedAttribute(): float
    {
        return $this->items->sum('quantity_accepted');
    }
}