<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Vendor Model
 *
 * Represents a procurement vendor/supplier with contact details,
 * payment terms, and performance tracking.
 */
class Vendor extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'vendor_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_id',
        'bank_account',
        'payment_terms',
        'currency_code',
        'vendor_category',
        'status',
        'performance_metrics',
    ];

    protected $casts = [
        'address' => 'array',
        'performance_metrics' => 'array',
    ];

    /**
     * Get the purchase orders for this vendor.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get the vendor invoices for this vendor.
     */
    public function vendorInvoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class);
    }

    /**
     * Check if vendor is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get vendor's full address as string.
     */
    public function getFullAddressAttribute(): string
    {
        if (!$this->address) {
            return '';
        }

        $address = $this->address;
        return trim(implode(', ', array_filter([
            $address['street'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? '',
        ])));
    }
}