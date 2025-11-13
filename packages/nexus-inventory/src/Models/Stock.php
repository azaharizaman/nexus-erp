<?php

namespace Nexus\Inventory\Models;

use Nexus\Inventory\Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config as ConfigFacade;

class Stock extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function location(): BelongsTo
    {
        $locationModel = ConfigFacade::get('inventory-management.models.location');

        return $this->belongsTo($locationModel);
    }

    public function movements(): HasMany
    {
        $movementModel = ConfigFacade::get('inventory-management.models.stock_movement');

        return $this->hasMany($movementModel);
    }

    public function getTable(): string
    {
        return ConfigFacade::get('inventory-management.table_names.stocks', parent::getTable());
    }

    protected static function newFactory(): Factory
    {
        return StockFactory::new();
    }
}
