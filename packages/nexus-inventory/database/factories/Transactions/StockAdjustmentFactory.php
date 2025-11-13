<?php

namespace Nexus\Inventory\Database\Factories\Transactions;

use Nexus\Inventory\Models\Stock;
use Nexus\Inventory\Models\Transactions\StockAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

    public function definition(): array
    {
        return [
            'stock_id' => Stock::factory(),
            'reason_code' => $this->faker->randomElement(['CYCLE_COUNT', 'DAMAGE', 'RECONCILIATION']),
            'note' => $this->faker->optional()->sentence(),
            'adjusted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'adjusted_by_type' => null,
            'adjusted_by_id' => null,
        ];
    }
}
