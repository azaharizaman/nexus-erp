<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UomAliasFactory extends Factory
{
    protected $model = UomAlias::class;

    public function definition(): array
    {
        return [
            'unit_id' => UomUnit::factory(),
            'alias' => Str::upper($this->faker->unique()->lexify('??')),
            'is_preferred' => $this->faker->boolean(20),
        ];
    }
}
