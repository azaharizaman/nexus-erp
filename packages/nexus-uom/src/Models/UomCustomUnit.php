<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomCustomUnitFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UomCustomUnit extends Model
{
    use HasFactory;

    protected $table = 'uom_custom_units';

    protected $guarded = [];

    protected $casts = [
        'conversion_factor' => 'decimal:12',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(UomType::class, 'uom_type_id');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UomCustomConversion::class, 'source_custom_unit_id');
    }

    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UomCustomConversion::class, 'target_custom_unit_id');
    }

    protected static function newFactory(): Factory
    {
        return UomCustomUnitFactory::new();
    }
}
