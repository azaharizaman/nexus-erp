<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomCustomConversion;
use Nexus\Uom\Models\UomCustomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomCustomConversionFactory extends Factory
{
    protected $model = UomCustomConversion::class;

    public function definition(): array
    {
        return [
            'source_custom_unit_id' => UomCustomUnit::factory(),
            'target_custom_unit_id' => UomCustomUnit::factory(),
            'formula' => $this->faker->optional()->randomElement(['x * 2', '(x - 32) * 5/9']),
            'factor' => $this->faker->randomFloat(6, 0.001, 100),
            'offset' => $this->faker->boolean(10) ? $this->faker->randomFloat(6, -50, 50) : 0,
            'is_linear' => true,
            'metadata' => null,
        ];
    }
}
