<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\Unit;
use Nexus\Backoffice\Models\UnitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $unitNames = [
            'Alpha Team',
            'Beta Squad',
            'Gamma Group',
            'Delta Force',
            'Echo Unit',
            'Foxtrot Team',
            'Golf Squad',
            'Hotel Group',
            'India Team',
            'Juliet Unit',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($unitNames),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->optional()->sentence(),
            'unit_group_id' => UnitGroup::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an inactive unit.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for an active unit.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
