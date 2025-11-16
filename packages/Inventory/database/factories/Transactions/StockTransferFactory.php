<?php

namespace Nexus\Inventory\Database\Factories\Transactions;

use Nexus\Inventory\Models\Location;
use Nexus\Inventory\Models\Transactions\StockTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        return [
            'source_location_id' => Location::factory(),
            'destination_location_id' => Location::factory(),
            'initiated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'note' => $this->faker->optional()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'initiated_by_type' => null,
            'initiated_by_id' => null,
        ];
    }
}
