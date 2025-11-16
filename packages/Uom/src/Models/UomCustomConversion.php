<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomCustomConversionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UomCustomConversion extends Model
{
    use HasFactory;

    protected $table = 'uom_custom_conversions';

    protected $guarded = [];

    protected $casts = [
        'factor' => 'decimal:12',
        'offset' => 'decimal:12',
        'is_linear' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sourceUnit(): BelongsTo
    {
        return $this->belongsTo(UomCustomUnit::class, 'source_custom_unit_id');
    }

    public function targetUnit(): BelongsTo
    {
        return $this->belongsTo(UomCustomUnit::class, 'target_custom_unit_id');
    }

    protected static function newFactory(): Factory
    {
        return UomCustomConversionFactory::new();
    }
}
