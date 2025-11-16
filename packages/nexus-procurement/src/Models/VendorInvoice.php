<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Vendor Invoice Model
 *
 * Represents an invoice received from a vendor for payment processing.
 */
class VendorInvoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'vendor_id',
        'po_id',
        'grn_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency_code',
        'match_status',
        'payment_status',
        'payment_authorized_by',
        'payment_authorized_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_authorized_at' => 'datetime',
    ];

    /**
     * Get the vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

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
     * Get the payment authorizer.
     */
    public function paymentAuthorizer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'payment_authorized_by');
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(VendorInvoiceItem::class, 'vendor_invoice_id');
    }

    /**
     * Check if invoice is matched.
     */
    public function isMatched(): bool
    {
        return $this->match_status === 'matched';
    }

    /**
     * Check if payment is authorized.
     */
    public function isPaymentAuthorized(): bool
    {
        return $this->payment_status === 'authorized';
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && !$this->isPaid();
    }
}