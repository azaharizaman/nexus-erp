<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomConversionLog;
use Nexus\Uom\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomConversionLogFactory extends Factory
{
    protected $model = UomConversionLog::class;

    public function definition(): array
    {
        $value = $this->faker->randomFloat(4, 1, 1000);
        $factor = $this->faker->randomFloat(6, 0.001, 100);
        $result = $value * $factor;

        return [
            'source_unit_id' => UomUnit::factory(),
            'target_unit_id' => UomUnit::factory(),
            'factor_used' => $factor,
            'value' => $value,
            'result' => $result,
            'metadata' => null,
            'performed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
