<?php

declare(strict_types=1);

namespace Database\Factories\Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\OrgStructure\Models\Position;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\OrgStructure\Models\Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'tenant_id' => (string) $this->faker->uuid(),
            'title' => $this->faker->jobTitle(),
            'code' => $this->faker->unique()->lexify('POS???'),
            'org_unit_id' => (string) $this->faker->uuid(),
            'metadata' => [
                'level' => $this->faker->randomElement(['Entry', 'Mid', 'Senior', 'Executive']),
                'salary_range' => [
                    'min' => $this->faker->numberBetween(30000, 80000),
                    'max' => $this->faker->numberBetween(80000, 200000),
                ],
            ],
        ];
    }
}