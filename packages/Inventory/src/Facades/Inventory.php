<?php

namespace Nexus\Inventory\Facades;

use Nexus\Inventory\Services\InventoryService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 */
class Inventory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return InventoryService::class;
    }
}
