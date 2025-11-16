<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Nexus\Procurement\Enums\BlanketPOReleaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Blanket PO Release
 *
 * A release against a blanket purchase order, creating an actual purchase order.
 */
class BlanketPORelease extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'blanket_po_id',
        'release_number',
        'created_by',
        'title',
        'description',
        'total_release_value',
        'required_delivery_date',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'total_release_value' => 'decimal:2',
        'required_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'status' => BlanketPOReleaseStatus::class,
    ];

    /**
     * Get the blanket PO this release belongs to.
     */
    public function blanketPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(BlanketPurchaseOrder::class);
    }

    /**
     * Get the user who created this release.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who approved this release.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the release items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BlanketPOReleaseItem::class);
    }

    /**
     * Get the purchase order created from this release.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Check if the release is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === BlanketPOReleaseStatus::APPROVED;
    }

    /**
     * Check if the release can be auto-approved based on blanket PO settings.
     */
    public function canAutoApprove(): bool
    {
        return $this->total_release_value <= $this->blanketPurchaseOrder->auto_approval_limit;
    }
}