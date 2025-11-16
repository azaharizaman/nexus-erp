<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Tenancy\Traits\BelongsToTenant;

/**
 * Purchase Requisition Model
 *
 * Represents an internal request for goods/services requiring approval
 * before procurement can begin.
 */
class PurchaseRequisition extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'requisition_number',
        'requester_id',
        'department_id',
        'status',
        'justification',
        'total_estimate',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_estimate' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the requester (user who created the requisition).
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requester_id');
    }

    /**
     * Get the department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(\Nexus\Backoffice\Models\Department::class, 'department_id');
    }

    /**
     * Get the approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the requisition items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'requisition_id');
    }

    /**
     * Get the purchase order created from this requisition.
     */
    public function purchaseOrder(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'requisition_id');
    }

    /**
     * Check if requisition can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    /**
     * Check if requisition can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if requisition can be converted to PO.
     */
    public function canBeConverted(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Calculate total estimate from items.
     */
    public function calculateTotalEstimate(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price_estimate;
        });
    }
}