<?php

namespace Nexus\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Inventory\Models\Location;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition()
    {
        return [
            'name' => $this->faker->city,
        ];
    }
}
