<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomUnitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UomUnitGroupFactory extends Factory
{
    protected $model = UomUnitGroup::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(8),
        ];
    }
}
