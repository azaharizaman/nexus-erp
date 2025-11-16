<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Three Way Match Result Model
 *
 * Records the result of matching PO, GRN, and Invoice for payment authorization.
 */
class ThreeWayMatchResult extends Model
{
    protected $fillable = [
        'po_id',
        'grn_id',
        'vendor_invoice_id',
        'match_date',
        'match_status',
        'price_variance_pct',
        'quantity_variance_pct',
        'total_variance_amount',
        'tolerance_applied',
        'approved_override',
        'approved_by',
        'variance_details',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'price_variance_pct' => 'decimal:2',
        'quantity_variance_pct' => 'decimal:2',
        'total_variance_amount' => 'decimal:2',
        'tolerance_applied' => 'array',
        'variance_details' => 'array',
        'approved_override' => 'boolean',
    ];

    /**
     * Get the purchase order.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Get the goods receipt note.
     */
    public function goodsReceiptNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptNote::class, 'grn_id');
    }

    /**
     * Get the vendor invoice.
     */
    public function vendorInvoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Check if match was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->match_status === 'success';
    }

    /**
     * Check if match has variance.
     */
    public function hasVariance(): bool
    {
        return in_array($this->match_status, ['price_variance', 'quantity_variance', 'total_variance']);
    }

    /**
     * Check if match was rejected.
     */
    public function isRejected(): bool
    {
        return $this->match_status === 'rejected';
    }

    /**
     * Check if manual override was applied.
     */
    public function hasOverride(): bool
    {
        return $this->approved_override;
    }
}