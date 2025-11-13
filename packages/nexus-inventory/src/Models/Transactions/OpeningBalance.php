<?php

namespace Nexus\Inventory\Models\Transactions;

use Nexus\Inventory\Database\Factories\Transactions\OpeningBalanceFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config as ConfigFacade;

class OpeningBalance extends BaseTransaction
{
    protected $casts = [
        'recorded_at' => 'datetime',
        'initial_quantity' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function configKey(): string
    {
        return 'opening_balances';
    }

    public function stock(): BelongsTo
    {
        $stockModel = ConfigFacade::get('inventory-management.models.stock');

        return $this->belongsTo($stockModel);
    }

    protected static function newFactory(): Factory
    {
        return OpeningBalanceFactory::new();
    }
}
