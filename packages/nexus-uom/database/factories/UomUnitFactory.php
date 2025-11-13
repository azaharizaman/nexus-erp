<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UomUnitFactory extends Factory
{
    protected $model = UomUnit::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();
        $symbol = $this->faker->optional(0.6)->lexify('?');

        return [
            'code' => Str::upper($this->faker->unique()->lexify('???')),
            'name' => Str::title($name),
            'symbol' => $symbol ? Str::upper($symbol) : null,
            'uom_type_id' => UomType::factory(),
            'conversion_factor' => $this->faker->randomFloat(6, 0.001, 1000),
            'offset' => $this->faker->boolean(10) ? $this->faker->randomFloat(6, -100, 100) : 0,
            'precision' => $this->faker->numberBetween(0, 6),
            'is_base' => false,
            'is_active' => true,
            'metadata' => $this->faker->optional()->randomElement([
                ['system' => 'metric'],
                ['system' => 'imperial'],
            ]),
        ];
    }

    public function base(): static
    {
        return $this->state(fn () => ['is_base' => true, 'conversion_factor' => 1, 'offset' => 0]);
    }
}
