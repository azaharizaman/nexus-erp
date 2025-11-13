<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomCompoundUnitFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UomCompoundUnit extends Model
{
    use HasFactory;

    protected $table = 'uom_compound_units';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(UomType::class, 'uom_type_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(UomCompoundComponent::class, 'compound_unit_id');
    }

    protected static function newFactory(): Factory
    {
        return UomCompoundUnitFactory::new();
    }
}
