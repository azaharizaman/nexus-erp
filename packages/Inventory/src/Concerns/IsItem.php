<?php

namespace Nexus\Inventory\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Config as ConfigFacade;

trait IsItem
{
    /**
     * Get the stocks for the item.
     */
    public function stocks(): MorphMany
    {
    $stockModel = ConfigFacade::get('inventory-management.models.stock');

    return $this->morphMany($stockModel, 'itemable');
    }

    /**
     * Get the unit of measure for the item.
     */
    public function uom(): BelongsTo
    {
    $unitModel = ConfigFacade::get('inventory-management.models.unit');

    return $this->belongsTo($unitModel, 'uom_id');
    }
}
