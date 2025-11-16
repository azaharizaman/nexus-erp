<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase Order Item Model
 *
 * Represents a line item in a purchase order.
 */
class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'po_id',
        'line_number',
        'item_description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'tax_code',
        'gl_account_code',
        'received_quantity',
        'invoiced_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'invoiced_quantity' => 'decimal:4',
    ];

    /**
     * Get the purchase order this item belongs to.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Calculate line total.
     */
    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Check if item is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Check if item is fully invoiced.
     */
    public function isFullyInvoiced(): bool
    {
        return $this->invoiced_quantity >= $this->quantity;
    }

    /**
     * Get remaining quantity to receive.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->received_quantity);
    }
}