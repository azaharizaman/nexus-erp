<?php

namespace Nexus\Inventory\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface Item
{
    /**
     * Get the stocks for the item.
     */
    public function stocks(): MorphMany;

    /**
     * Get the SKU for the item.
     */
    public function getSku(): string;

    /**
     * Get the unit of measure for the item.
     */
    public function uom(): BelongsTo;
}
