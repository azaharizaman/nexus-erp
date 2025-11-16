<?php

namespace Nexus\Uom\Models;

use Nexus\Uom\Database\Factories\UomItemPackagingFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UomItemPackaging extends Model
{
    use HasFactory;

    protected $table = 'uom_item_packagings';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(UomItem::class, 'item_id');
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(UomPackaging::class, 'packaging_id');
    }

    protected static function newFactory(): Factory
    {
        return UomItemPackagingFactory::new();
    }
}
