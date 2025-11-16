<?php

namespace Nexus\Inventory\Database\Factories\Transactions;

use Nexus\Inventory\Models\Stock;
use Nexus\Inventory\Models\Transactions\OpeningBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpeningBalanceFactory extends Factory
{
    protected $model = OpeningBalance::class;

    public function definition(): array
    {
        $initialQuantity = $this->faker->randomFloat(4, 0, 500);

        return [
            'stock_id' => Stock::factory(),
            'initial_quantity' => $initialQuantity,
            'recorded_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
