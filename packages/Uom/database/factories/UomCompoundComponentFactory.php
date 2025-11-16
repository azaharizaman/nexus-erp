<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomCompoundComponent;
use Nexus\Uom\Models\UomCompoundUnit;
use Nexus\Uom\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomCompoundComponentFactory extends Factory
{
    protected $model = UomCompoundComponent::class;

    public function definition(): array
    {
        return [
            'compound_unit_id' => UomCompoundUnit::factory(),
            'unit_id' => UomUnit::factory(),
            'exponent' => $this->faker->randomElement([-3, -2, -1, 1, 2, 3]),
        ];
    }
}
