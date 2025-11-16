<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Nexus\Procurement\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Procurement Contract
 *
 * Formal agreements with vendors for goods/services with terms and conditions.
 */
class ProcurementContract extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'contract_number',
        'vendor_id',
        'contract_type',
        'title',
        'description',
        'contract_value',
        'currency_code',
        'effective_date',
        'expiry_date',
        'auto_renewal',
        'renewal_terms',
        'payment_terms',
        'delivery_terms',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'termination_reason',
        'special_conditions',
        'attachments',
    ];

    protected $casts = [
        'contract_value' => 'decimal:2',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'auto_renewal' => 'boolean',
        'renewal_terms' => 'json',
        'special_conditions' => 'json',
        'attachments' => 'json',
        'status' => ContractStatus::class,
    ];

    /**
     * Get the vendor for this contract.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the user who created this contract.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who approved this contract.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the contract items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProcurementContractItem::class);
    }

    /**
     * Get the amendments to this contract.
     */
    public function amendments(): HasMany
    {
        return $this->hasMany(ContractAmendment::class);
    }

    /**
     * Get the purchase orders linked to this contract.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Check if the contract is active.
     */
    public function isActive(): bool
    {
        return $this->status === ContractStatus::ACTIVE &&
               now()->between($this->effective_date, $this->expiry_date);
    }

    /**
     * Check if the contract is expired.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expiry_date);
    }

    /**
     * Check if contract renewal is due.
     */
    public function isRenewalDue(): bool
    {
        if (!$this->auto_renewal) {
            return false;
        }

        $renewalNoticeDays = $this->renewal_terms['notice_days'] ?? 90;
        return now()->diffInDays($this->expiry_date) <= $renewalNoticeDays;
    }

    /**
     * Get total value of POs against this contract.
     */
    public function getTotalPOValue(): float
    {
        return $this->purchaseOrders()->sum('total_amount');
    }

    /**
     * Get remaining contract value.
     */
    public function getRemainingValue(): float
    {
        return $this->contract_value - $this->getTotalPOValue();
    }

    /**
     * Check if a PO value would exceed contract limits.
     */
    public function canAccommodatePOValue(float $poValue): bool
    {
        return $this->getRemainingValue() >= $poValue;
    }
}