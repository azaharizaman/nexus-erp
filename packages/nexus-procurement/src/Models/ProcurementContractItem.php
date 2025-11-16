<?php

declare(strict_types=1);

namespace Nexus\Procurement\Models;

use Nexus\Tenancy\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Procurement Contract Item
 *
 * Individual line items within a procurement contract.
 */
class ProcurementContractItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'contract_id',
        'line_number',
        'item_description',
        'specifications',
        'quantity',
        'unit_price',
        'total_value',
        'unit_of_measure',
        'category_code',
        'gl_account_code',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total_value' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get the contract this item belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProcurementContract::class);
    }
}