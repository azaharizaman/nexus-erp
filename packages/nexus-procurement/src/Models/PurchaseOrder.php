<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Purchase Order Model
 *
 * Represents a legally binding commitment to purchase from a vendor.
 */
class PurchaseOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'po_number',
        'requisition_id',
        'vendor_id',
        'created_by',
        'status',
        'order_date',
        'delivery_date',
        'payment_terms',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency_code',
        'exchange_rate',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the requisition this PO was created from.
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }

    /**
     * Get the vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the PO items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id');
    }

    /**
     * Get the goods receipt notes.
     */
    public function goodsReceiptNotes(): HasMany
    {
        return $this->hasMany(GoodsReceiptNote::class, 'po_id');
    }

    /**
     * Get the vendor invoices.
     */
    public function vendorInvoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class, 'po_id');
    }

    /**
     * Check if PO can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft']);
    }

    /**
     * Check if PO can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if PO can be sent to vendor.
     */
    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft', 'approved']);
    }

    /**
     * Check if PO is fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->status === 'fully_received';
    }

    /**
     * Calculate totals from items.
     */
    public function calculateTotals(): array
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $taxAmount = $this->items->sum('tax_amount');
        $totalAmount = $subtotal + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }
}