<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Vendor Quote Model
 *
 * Represents a vendor's response to an RFQ.
 */
class VendorQuote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'vendor_id',
        'submitted_at',
        'status',
        'total_quoted_price',
        'delivery_days',
        'payment_terms',
        'validity_days',
        'notes',
        'evaluation_score',
        'evaluation_notes',
        'rank',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'total_quoted_price' => 'decimal:2',
        'evaluation_score' => 'decimal:1',
    ];

    /**
     * Get the RFQ this quote is for.
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    /**
     * Get the vendor who submitted this quote.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the quote line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(VendorQuoteItem::class, 'vendor_quote_id');
    }

    /**
     * Check if quote is still valid.
     */
    public function isValid(): bool
    {
        if (!$this->validity_days || !$this->submitted_at) {
            return true; // No validity period set
        }

        return $this->submitted_at->addDays($this->validity_days)->isFuture();
    }

    /**
     * Calculate total quoted price from line items.
     */
    public function calculateTotalPrice(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    /**
     * Get the price competitiveness score (lower is better).
     */
    public function getPriceScore(): float
    {
        $rfqLowest = $this->rfq->getLowestQuoteAmount();
        if (!$rfqLowest || $rfqLowest == 0) {
            return 0;
        }

        return ($this->total_quoted_price / $rfqLowest) * 100;
    }

    /**
     * Get delivery score (lower delivery days is better).
     */
    public function getDeliveryScore(): int
    {
        return $this->delivery_days ?? 999;
    }

    /**
     * Check if this quote meets all RFQ requirements.
     */
    public function meetsRequirements(): bool
    {
        // Check if all RFQ items have been quoted
        $rfqItemCount = $this->rfq->items()->count();
        $quotedItemCount = $this->items()->count();

        return $quotedItemCount >= $rfqItemCount;
    }
}