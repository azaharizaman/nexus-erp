<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contract Amendment
 *
 * Changes made to procurement contracts over time.
 */
class ContractAmendment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'contract_id',
        'amendment_number',
        'title',
        'description',
        'changes',
        'effective_date',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'changes' => 'json',
        'effective_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the contract this amendment belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProcurementContract::class);
    }

    /**
     * Get the user who created this amendment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who approved this amendment.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}