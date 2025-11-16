<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * RFQ Item Model
 *
 * Represents individual line items in a Request for Quotation.
 */
class RFQItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'requisition_item_id',
        'line_number',
        'item_description',
        'quantity',
        'unit_of_measure',
        'specifications',
        'estimated_unit_price',
        'required_delivery_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'required_delivery_date' => 'date',
    ];

    /**
     * Get the RFQ this item belongs to.
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    /**
     * Get the original requisition item.
     */
    public function requisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class, 'requisition_item_id');
    }

    /**
     * Get all vendor quotes for this item.
     */
    public function vendorQuoteItems(): HasMany
    {
        return $this->hasMany(VendorQuoteItem::class, 'rfq_item_id');
    }

    /**
     * Get the lowest quoted price for this item.
     */
    public function getLowestQuotedPrice(): ?float
    {
        return $this->vendorQuoteItems()
            ->join('vendor_quotes', 'vendor_quotes.id', '=', 'vendor_quote_items.vendor_quote_id')
            ->where('vendor_quotes.status', 'submitted')
            ->min('vendor_quote_items.unit_price');
    }

    /**
     * Get the average quoted price for this item.
     */
    public function getAverageQuotedPrice(): ?float
    {
        return $this->vendorQuoteItems()
            ->join('vendor_quotes', 'vendor_quotes.id', '=', 'vendor_quote_items.vendor_quote_id')
            ->where('vendor_quotes.status', 'submitted')
            ->avg('vendor_quote_items.unit_price');
    }
}