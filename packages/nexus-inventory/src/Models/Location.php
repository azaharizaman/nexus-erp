<?php

namespace Nexus\Inventory\Models;

use Nexus\Inventory\Concerns\IsLocation;
use Nexus\Inventory\Contracts\Location as LocationContract;
use Nexus\Inventory\Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config as ConfigFacade;

class Location extends Model implements LocationContract
{
    use HasFactory;
    use IsLocation;

    protected $guarded = [];

    public function getLocationName(): string
    {
        return $this->name;
    }

    public function getTable(): string
    {
        return ConfigFacade::get('inventory-management.table_names.locations', parent::getTable());
    }

    protected static function newFactory(): Factory
    {
        return LocationFactory::new();
    }
}
