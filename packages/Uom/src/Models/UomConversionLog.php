<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomConversionLogFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UomConversionLog extends Model
{
    use HasFactory;

    protected $table = 'uom_conversion_logs';

    protected $guarded = [];

    protected $casts = [
        'factor_used' => 'decimal:12',
        'value' => 'decimal:18',
        'result' => 'decimal:18',
        'metadata' => 'array',
        'performed_at' => 'datetime',
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

    public function performedBy(): MorphTo
    {
        return $this->morphTo('performed_by');
    }

    protected static function newFactory(): Factory
    {
        return UomConversionLogFactory::new();
    }
}
