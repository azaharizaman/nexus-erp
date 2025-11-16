<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionCosting extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_production_costing';

    protected $fillable = [
        'work_order_id',
        'standard_material_cost',
        'actual_material_cost',
        'standard_labor_cost',
        'actual_labor_cost',
        'standard_overhead_cost',
        'actual_overhead_cost',
        'total_standard_cost',
        'total_actual_cost',
        'variance_total',
        'cost_per_unit',
        'costing_date',
    ];

    protected $casts = [
        'standard_material_cost' => 'decimal:4',
        'actual_material_cost' => 'decimal:4',
        'standard_labor_cost' => 'decimal:4',
        'actual_labor_cost' => 'decimal:4',
        'standard_overhead_cost' => 'decimal:4',
        'actual_overhead_cost' => 'decimal:4',
        'total_standard_cost' => 'decimal:4',
        'total_actual_cost' => 'decimal:4',
        'variance_total' => 'decimal:4',
        'cost_per_unit' => 'decimal:4',
        'costing_date' => 'date',
    ];

    /**
     * Get the work order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    /**
     * Get material variance.
     */
    public function getMaterialVariance(): float
    {
        return $this->actual_material_cost - $this->standard_material_cost;
    }

    /**
     * Get labor variance.
     */
    public function getLaborVariance(): float
    {
        return $this->actual_labor_cost - $this->standard_labor_cost;
    }

    /**
     * Get overhead variance.
     */
    public function getOverheadVariance(): float
    {
        return $this->actual_overhead_cost - $this->standard_overhead_cost;
    }

    /**
     * Check if variance is favorable (actual < standard).
     */
    public function isFavorableVariance(): bool
    {
        return $this->variance_total < 0;
    }

    /**
     * Get variance percentage.
     */
    public function getVariancePercentage(): float
    {
        if ($this->total_standard_cost == 0) {
            return 0;
        }

        return ($this->variance_total / $this->total_standard_cost) * 100;
    }
}
