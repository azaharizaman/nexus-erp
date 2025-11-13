<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomUnitFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UomUnit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'uom_units';

    protected $guarded = [];

    protected $casts = [
        'conversion_factor' => 'decimal:12',
        'offset' => 'decimal:12',
        'precision' => 'integer',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(UomType::class, 'uom_type_id');
    }

    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UomConversion::class, 'source_unit_id');
    }

    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UomConversion::class, 'target_unit_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(UomAlias::class, 'unit_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(UomUnitGroup::class, 'uom_unit_group_unit', 'unit_id', 'unit_group_id')->withTimestamps();
    }

    public function compoundComponents(): HasMany
    {
        return $this->hasMany(UomCompoundComponent::class, 'unit_id');
    }

    public function packagingAsBase(): HasMany
    {
        return $this->hasMany(UomPackaging::class, 'base_unit_id');
    }

    public function packagingAsPackage(): HasMany
    {
        return $this->hasMany(UomPackaging::class, 'package_unit_id');
    }

    public function conversionLogsAsSource(): HasMany
    {
        return $this->hasMany(UomConversionLog::class, 'source_unit_id');
    }

    public function conversionLogsAsTarget(): HasMany
    {
        return $this->hasMany(UomConversionLog::class, 'target_unit_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): Factory
    {
        return UomUnitFactory::new();
    }
}
