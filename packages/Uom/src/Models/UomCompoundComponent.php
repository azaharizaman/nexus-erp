<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomCompoundComponentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UomCompoundComponent extends Model
{
    use HasFactory;

    protected $table = 'uom_compound_components';

    protected $guarded = [];

    protected $casts = [
        'exponent' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function compoundUnit(): BelongsTo
    {
        return $this->belongsTo(UomCompoundUnit::class, 'compound_unit_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UomUnit::class, 'unit_id');
    }

    protected static function newFactory(): Factory
    {
        return UomCompoundComponentFactory::new();
    }
}
