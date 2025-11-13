<?php

namespace Nexus\Inventory\Models;

use Nexus\Inventory\Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config as ConfigFacade;

class StockMovement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quantity_before' => 'decimal:4',
        'quantity_change' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function stock(): BelongsTo
    {
        $stockModel = ConfigFacade::get('inventory-management.models.stock');

        return $this->belongsTo($stockModel);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTable(): string
    {
        return ConfigFacade::get('inventory-management.table_names.stock_movements', parent::getTable());
    }

    protected static function newFactory(): Factory
    {
        return StockMovementFactory::new();
    }
}
