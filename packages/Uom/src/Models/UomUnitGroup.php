<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomUnitGroupFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UomUnitGroup extends Model
{
    use HasFactory;

    protected $table = 'uom_unit_groups';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(UomUnit::class, 'uom_unit_group_unit', 'unit_group_id', 'unit_id')->withTimestamps();
    }

    protected static function newFactory(): Factory
    {
        return UomUnitGroupFactory::new();
    }
}
