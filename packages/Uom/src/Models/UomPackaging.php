<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomPackagingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UomPackaging extends Model
{
    use HasFactory;

    protected $table = 'uom_packagings';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(UomUnit::class, 'base_unit_id');
    }

    public function packageUnit(): BelongsTo
    {
        return $this->belongsTo(UomUnit::class, 'package_unit_id');
    }

    public function itemPackagings(): HasMany
    {
        return $this->hasMany(UomItemPackaging::class, 'packaging_id');
    }

    protected static function newFactory(): Factory
    {
        return UomPackagingFactory::new();
    }
}
