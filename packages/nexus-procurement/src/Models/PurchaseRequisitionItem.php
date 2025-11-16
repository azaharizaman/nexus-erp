<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase Requisition Item Model
 *
 * Represents a line item in a purchase requisition.
 */
class PurchaseRequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id',
        'line_number',
        'item_description',
        'quantity',
        'unit_of_measure',
        'unit_price_estimate',
        'gl_account_code',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price_estimate' => 'decimal:4',
    ];

    /**
     * Get the requisition this item belongs to.
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }

    /**
     * Calculate line total.
     */
    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price_estimate;
    }
}