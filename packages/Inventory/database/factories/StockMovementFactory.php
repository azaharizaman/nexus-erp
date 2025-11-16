<?php

namespace Nexus\Inventory\Database\Factories;

use Nexus\Inventory\Models\Stock;
use Nexus\Inventory\Models\StockMovement;
use Nexus\Inventory\Models\Transactions\StockIn;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $quantityBefore = $this->faker->randomFloat(4, 0, 250);
        $quantityChange = $this->faker->randomFloat(4, 1, 50);

        return [
            'stock_id' => Stock::factory(),
            'serial_number' => $this->faker->unique()->uuid,
            'transactionable_id' => StockIn::factory(),
            'transactionable_type' => StockIn::class,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityBefore + $quantityChange,
            'reason' => $this->faker->sentence,
        ];
    }
}
