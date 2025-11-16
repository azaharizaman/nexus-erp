<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\OfficeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeType>
 */
class OfficeTypeFactory extends Factory
{
    protected $model = OfficeType::class;

    public function definition(): array
    {
        $types = [
            'Headquarters',
            'Branch Office',
            'Regional Office',
            'Sales Office',
            'Service Center',
            'Call Center',
            'Distribution Center',
            'Research Facility',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($types),
            'code' => strtoupper($this->faker->unique()->lexify('??')),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an inactive office type.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for an active office type.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
