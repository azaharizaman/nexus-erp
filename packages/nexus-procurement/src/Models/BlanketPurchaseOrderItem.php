<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blanket Purchase Order Item
 *
 * Line items for blanket purchase orders with pricing and quantity limits.
 */
class BlanketPurchaseOrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'blanket_po_id',
        'line_number',
        'item_description',
        'specifications',
        'unit_of_measure',
        'max_quantity',
        'unit_price',
        'total_line_value',
        'category_code',
        'gl_account_code',
    ];

    protected $casts = [
        'max_quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total_line_value' => 'decimal:2',
    ];

    /**
     * Get the blanket PO this item belongs to.
     */
    public function blanketPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(BlanketPurchaseOrder::class);
    }

    /**
     * Get the total released quantity for this item.
     */
    public function getTotalReleasedQuantity(): float
    {
        return $this->blanketPurchaseOrder
            ->releases()
            ->join('blanket_po_release_items', 'blanket_po_releases.id', '=', 'blanket_po_release_items.blanket_po_release_id')
            ->where('blanket_po_release_items.blanket_po_item_id', $this->id)
            ->sum('blanket_po_release_items.quantity');
    }

    /**
     * Get the remaining quantity that can be released.
     */
    public function getRemainingQuantity(): float
    {
        return $this->max_quantity - $this->getTotalReleasedQuantity();
    }

    /**
     * Check if a quantity can be released.
     */
    public function canReleaseQuantity(float $quantity): bool
    {
        return $this->getRemainingQuantity() >= $quantity;
    }
}