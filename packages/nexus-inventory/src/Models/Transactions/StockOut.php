<?php

namespace Nexus\Inventory\Models\Transactions;

use Nexus\Inventory\Database\Factories\Transactions\StockOutFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config as ConfigFacade;

class StockOut extends BaseTransaction
{
    protected $casts = [
        'dispatched_at' => 'datetime',
        'expected_quantity' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function configKey(): string
    {
        return 'stock_outs';
    }

    public function stock(): BelongsTo
    {
        $stockModel = ConfigFacade::get('inventory-management.models.stock');

        return $this->belongsTo($stockModel);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): Factory
    {
        return StockOutFactory::new();
    }
}
