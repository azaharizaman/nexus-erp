<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomTypeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UomType extends Model
{
    use HasFactory;

    protected $table = 'uom_types';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(UomUnit::class, 'uom_type_id');
    }

    public function compoundUnits(): HasMany
    {
        return $this->hasMany(UomCompoundUnit::class, 'uom_type_id');
    }

    public function customUnits(): HasMany
    {
        return $this->hasMany(UomCustomUnit::class, 'uom_type_id');
    }

    protected static function newFactory(): Factory
    {
        return UomTypeFactory::new();
    }
}
