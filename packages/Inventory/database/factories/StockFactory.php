<?php

namespace Nexus\Inventory\Database\Factories;

use Nexus\Inventory\Models\Item;
use Nexus\Inventory\Models\Location;
use Nexus\Inventory\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'itemable_id' => Item::factory(),
            'itemable_type' => Item::class,
            'location_id' => Location::factory(),
            'quantity' => $this->faker->randomFloat(4, 0, 500),
        ];
    }
}
