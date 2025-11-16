<?php

namespace Nexus\Uom\Database\Factories;

use Nexus\Uom\Models\UomCompoundUnit;
use Nexus\Uom\Models\UomType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UomCompoundUnitFactory extends Factory
{
    protected $model = UomCompoundUnit::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'symbol' => $this->faker->optional()->regexify('[A-Z]{1,2}\/[A-Z]{1,2}'),
            'uom_type_id' => UomType::factory(),
            'metadata' => null,
        ];
    }
}
