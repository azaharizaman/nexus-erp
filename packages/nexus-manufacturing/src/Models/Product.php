<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nexus\Manufacturing\Enums\ProductType;

class Product extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_products';

    protected $fillable = [
        'tenant_id',
        'product_code',
        'description',
        'product_type',
        'unit_of_measure',
        'standard_cost',
        'lead_time_days',
    ];

    protected $casts = [
        'product_type' => ProductType::class,
        'standard_cost' => 'decimal:4',
        'lead_time_days' => 'integer',
    ];

    /**
     * Get the bill of materials for this product.
     */
    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'product_id');
    }

    /**
     * Get the active BOM for this product.
     */
    public function activeBOM(): HasOne
    {
        return $this->hasOne(BillOfMaterial::class, 'product_id')
            ->where('status', 'active')
            ->latest('effective_date');
    }

    /**
     * Get work orders for this product.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'product_id');
    }

    /**
     * Check if product can have a BOM.
     */
    public function canHaveBOM(): bool
    {
        return $this->product_type->canHaveBOM();
    }

    /**
     * Check if product can be produced.
     */
    public function canBeProduced(): bool
    {
        return $this->product_type->canBeProduced();
    }

    /**
     * Check if product can be purchased.
     */
    public function canBePurchased(): bool
    {
        return $this->product_type->canBePurchased();
    }
}
