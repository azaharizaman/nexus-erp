<?php

namespace Nexus\Inventory\Models\Transactions;

use Nexus\Inventory\Database\Factories\Transactions\StockTransferFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config as ConfigFacade;

class StockTransfer extends BaseTransaction
{
    protected $casts = [
        'initiated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function configKey(): string
    {
        return 'stock_transfers';
    }

    public function sourceLocation(): BelongsTo
    {
        $locationModel = ConfigFacade::get('inventory-management.models.location');

        return $this->belongsTo($locationModel, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        $locationModel = ConfigFacade::get('inventory-management.models.location');

        return $this->belongsTo($locationModel, 'destination_location_id');
    }

    public function initiatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): Factory
    {
        return StockTransferFactory::new();
    }
}
