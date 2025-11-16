<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomItemFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UomItem extends Model
{
    use HasFactory;

    protected $table = 'uom_items';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(UomUnit::class, 'default_unit_id');
    }

    public function packagings(): HasMany
    {
        return $this->hasMany(UomItemPackaging::class, 'item_id');
    }

    protected static function newFactory(): Factory
    {
        return UomItemFactory::new();
    }
}
