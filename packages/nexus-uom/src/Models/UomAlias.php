<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomAliasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UomAlias extends Model
{
    use HasFactory;

    protected $table = 'uom_aliases';

    protected $guarded = [];

    protected $casts = [
        'is_preferred' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UomUnit::class, 'unit_id');
    }

    protected static function newFactory(): Factory
    {
        return UomAliasFactory::new();
    }
}
