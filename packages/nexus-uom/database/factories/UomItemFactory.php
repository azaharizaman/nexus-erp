<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomItem;
use Nexus\Uom\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UomItemFactory extends Factory
{
    protected $model = UomItem::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'default_unit_id' => UomUnit::factory(),
            'metadata' => $this->faker->optional()->randomElement([
                ['category' => 'grocery'],
                ['category' => 'hardware'],
            ]),
        ];
    }
}
