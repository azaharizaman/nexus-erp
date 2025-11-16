<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomCustomUnit;
use Nexus\Uom\Models\UomType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UomCustomUnitFactory extends Factory
{
    protected $model = UomCustomUnit::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'code' => Str::upper($this->faker->unique()->lexify('CU?')), 
            'name' => Str::title($name),
            'symbol' => $this->faker->optional()->lexify('c?'),
            'description' => $this->faker->optional()->sentence(8),
            'uom_type_id' => UomType::factory(),
            'conversion_factor' => $this->faker->randomFloat(6, 0.001, 1000),
            'metadata' => null,
            'owner_type' => null,
            'owner_id' => null,
        ];
    }
}
