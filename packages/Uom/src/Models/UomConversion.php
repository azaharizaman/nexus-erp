<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomConversionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UomConversion extends Model
{
    use HasFactory;

    protected $table = 'uom_conversions';

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
        return $this->belongsTo(UomUnit::class, 'source_unit_id');
    }

    public function targetUnit(): BelongsTo
    {
        return $this->belongsTo(UomUnit::class, 'target_unit_id');
    }

    protected static function newFactory(): Factory
    {
        return UomConversionFactory::new();
    }
}
