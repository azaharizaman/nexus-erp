<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BOMItem extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_bom_items';

    protected $fillable = [
        'bom_id',
        'component_product_id',
        'quantity_required',
        'unit_of_measure',
        'scrap_allowance_pct',
        'line_number',
        'component_type',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:6',
        'scrap_allowance_pct' => 'decimal:2',
        'line_number' => 'integer',
    ];

    /**
     * Get the parent BOM.
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the component product.
     */
    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    /**
     * Calculate total quantity needed including scrap allowance.
     */
    public function getTotalQuantityNeeded(float $parentQuantity = 1.0): float
    {
        $baseQuantity = $this->quantity_required * $parentQuantity;
        $scrapAmount = $baseQuantity * ($this->scrap_allowance_pct / 100);
        
        return $baseQuantity + $scrapAmount;
    }

    /**
     * Check if this is a phantom component (transient sub-assembly).
     */
    public function isPhantom(): bool
    {
        return $this->component_type === 'phantom';
    }

    /**
     * Check if this is a reference component (for documentation only).
     */
    public function isReference(): bool
    {
        return $this->component_type === 'reference';
    }
}
