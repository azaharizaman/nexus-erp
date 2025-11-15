<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionCharacteristic extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_inspection_characteristics';

    protected $fillable = [
        'inspection_plan_id',
        'characteristic_name',
        'specification',
        'measurement_method',
        'pass_fail_criteria',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Get the parent inspection plan.
     */
    public function inspectionPlan(): BelongsTo
    {
        return $this->belongsTo(InspectionPlan::class, 'inspection_plan_id');
    }
}
