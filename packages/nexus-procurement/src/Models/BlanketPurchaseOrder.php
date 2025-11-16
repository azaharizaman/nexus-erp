<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Nexus\Procurement\Enums\BlanketPurchaseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blanket Purchase Order
 *
 * A long-term purchase commitment with a vendor for recurring purchases.
 * Releases are created against the blanket PO as needed.
 */
class BlanketPurchaseOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'blanket_po_number',
        'vendor_id',
        'created_by',
        'title',
        'description',
        'total_committed_value',
        'currency_code',
        'valid_from',
        'valid_until',
        'payment_terms',
        'status',
        'auto_approval_limit',
        'utilization_alert_threshold',
        'notes',
    ];

    protected $casts = [
        'total_committed_value' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'auto_approval_limit' => 'decimal:2',
        'utilization_alert_threshold' => 'decimal:2',
        'status' => BlanketPurchaseOrderStatus::class,
    ];

    /**
     * Get the vendor for this blanket PO.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the user who created this blanket PO.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the blanket PO items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BlanketPurchaseOrderItem::class);
    }

    /**
     * Get the releases against this blanket PO.
     */
    public function releases(): HasMany
    {
        return $this->hasMany(BlanketPORelease::class);
    }

    /**
     * Check if the blanket PO is active.
     */
    public function isActive(): bool
    {
        return $this->status === BlanketPurchaseOrderStatus::ACTIVE &&
               now()->between($this->valid_from, $this->valid_until);
    }

    /**
     * Check if the blanket PO is expired.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->valid_until);
    }

    /**
     * Get the total released value.
     */
    public function getTotalReleasedValue(): float
    {
        return $this->releases()->sum('total_release_value');
    }

    /**
     * Get the remaining committed value.
     */
    public function getRemainingValue(): float
    {
        return $this->total_committed_value - $this->getTotalReleasedValue();
    }

    /**
     * Get the utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->total_committed_value == 0) {
            return 0;
        }

        return ($this->getTotalReleasedValue() / $this->total_committed_value) * 100;
    }

    /**
     * Check if utilization alert should be triggered.
     */
    public function shouldTriggerUtilizationAlert(): bool
    {
        return $this->getUtilizationPercentage() >= ($this->utilization_alert_threshold * 100);
    }

    /**
     * Check if a release value is within the remaining committed value.
     */
    public function canReleaseValue(float $releaseValue): bool
    {
        return $this->getRemainingValue() >= $releaseValue;
    }
}