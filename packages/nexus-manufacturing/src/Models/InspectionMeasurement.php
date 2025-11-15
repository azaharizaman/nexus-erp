<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionMeasurement extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'manufacturing_inspection_measurements';

    protected $fillable = [
        'quality_inspection_id',
        'characteristic_id',
        'measured_value',
        'pass_fail',
        'notes',
    ];

    protected $casts = [
        'pass_fail' => 'boolean',
    ];

    /**
     * Get the parent quality inspection.
     */
    public function qualityInspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    /**
     * Get the inspection characteristic.
     */
    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(InspectionCharacteristic::class, 'characteristic_id');
    }
}
