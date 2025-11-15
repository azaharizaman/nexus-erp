<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchGenealogy extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_batch_genealogy';

    protected $fillable = [
        'tenant_id',
        'finished_goods_lot',
        'work_order_id',
    ];

    /**
     * Get the work order that produced this batch.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get raw material lots used in this batch.
     * This is handled through a pivot table: batch_genealogy_materials
     */
    public function rawMaterialLots()
    {
        return $this->belongsToMany(
            Product::class,
            'batch_genealogy_materials',
            'batch_genealogy_id',
            'raw_material_product_id'
        )->withPivot('raw_material_lot_number', 'quantity_consumed');
    }
}
