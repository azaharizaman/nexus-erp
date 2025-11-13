<?php

namespace Nexus\Inventory\Models\Transactions;

use Nexus\Inventory\Database\Factories\Transactions\StockAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config as ConfigFacade;

class StockAdjustment extends BaseTransaction
{
    protected $casts = [
        'adjusted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function configKey(): string
    {
        return 'stock_adjustments';
    }

    public function stock(): BelongsTo
    {
        $stockModel = ConfigFacade::get('inventory-management.models.stock');

        return $this->belongsTo($stockModel);
    }

    public function adjustedBy(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): Factory
    {
        return StockAdjustmentFactory::new();
    }
}
