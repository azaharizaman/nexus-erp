<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blanket PO Release Item
 *
 * Individual line items for a blanket PO release.
 */
class BlanketPOReleaseItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'blanket_po_release_id',
        'blanket_po_item_id',
        'quantity',
        'unit_price',
        'line_total',
        'delivery_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    /**
     * Get the blanket PO release this item belongs to.
     */
    public function blanketPORelease(): BelongsTo
    {
        return $this->belongsTo(BlanketPORelease::class);
    }

    /**
     * Get the blanket PO item this release item is based on.
     */
    public function blanketPOItem(): BelongsTo
    {
        return $this->belongsTo(BlanketPurchaseOrderItem::class);
    }
}