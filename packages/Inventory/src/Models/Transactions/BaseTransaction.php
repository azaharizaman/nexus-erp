<?php

namespace Nexus\Inventory\Models\Transactions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config as ConfigFacade;

abstract class BaseTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    abstract protected static function configKey(): string;

    public function getTable(): string
    {
        return ConfigFacade::get(
            'inventory-management.table_names.' . static::configKey(),
            parent::getTable()
        );
    }
}
