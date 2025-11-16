<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Vendor Quote Item Model
 *
 * Represents individual line items in a vendor's quote.
 */
class VendorQuoteItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'vendor_quote_id',
        'rfq_item_id',
        'quantity',
        'unit_price',
        'total_price',
        'delivery_days',
        'alternate_offer',
        'notes',
        'specifications_met',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'specifications_met' => 'boolean',
    ];

    /**
     * Get the vendor quote this item belongs to.
     */
    public function vendorQuote(): BelongsTo
    {
        return $this->belongsTo(VendorQuote::class, 'vendor_quote_id');
    }

    /**
     * Get the RFQ item this quote is for.
     */
    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RFQItem::class, 'rfq_item_id');
    }

    /**
     * Calculate total price for this line item.
     */
    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Get the price variance from RFQ estimated price.
     */
    public function getPriceVariance(): ?float
    {
        $estimated = $this->rfqItem->estimated_unit_price;
        if (!$estimated || $estimated == 0) {
            return null;
        }

        return (($this->unit_price - $estimated) / $estimated) * 100;
    }

    /**
     * Check if quantity matches RFQ requirement.
     */
    public function quantityMatches(): bool
    {
        return abs($this->quantity - $this->rfqItem->quantity) < 0.01;
    }
}