<?php

namespace Nexus\Inventory\Database\Factories\Transactions;

use Nexus\Inventory\Models\Stock;
use Nexus\Inventory\Models\Transactions\StockIn;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockInFactory extends Factory
{
    protected $model = StockIn::class;

    public function definition(): array
    {
        return [
            'stock_id' => Stock::factory(),
            'expected_quantity' => $this->faker->randomFloat(4, 1, 250),
            'received_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'document_number' => $this->faker->optional()->bothify('PO-#####'),
            'note' => $this->faker->optional()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
        ];
    }
}
