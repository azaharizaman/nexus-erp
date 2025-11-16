<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vendor Invoice Item Model
 *
 * Represents a line item in a vendor invoice.
 */
class VendorInvoiceItem extends Model
{
    protected $fillable = [
        'vendor_invoice_id',
        'po_item_id',
        'line_number',
        'description',
        'quantity',
        'unit_price',
        'tax_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    /**
     * Get the vendor invoice this item belongs to.
     */
    public function vendorInvoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }

    /**
     * Get the PO item this invoice item matches to.
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }
}