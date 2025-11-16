<?php

namespace Nexus\Inventory\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config as ConfigFacade;

trait IsLocation
{
    /**
     * Get the stocks for the location.
     */
    public function stocks(): HasMany
    {
        $stockModel = ConfigFacade::get('inventory-management.models.stock');

        return $this->hasMany($stockModel);
    }
}
