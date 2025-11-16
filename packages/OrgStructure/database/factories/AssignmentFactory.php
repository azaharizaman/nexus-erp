<?php

declare(strict_types=1);

namespace Database\Factories\Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\OrgStructure\Models\Assignment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\OrgStructure\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        $effectiveFrom = $this->faker->date();

        return [
            'tenant_id' => (string) $this->faker->uuid(),
            'employee_id' => (string) $this->faker->uuid(),
            'position_id' => (string) $this->faker->uuid(),
            'org_unit_id' => (string) $this->faker->uuid(),
            'effective_from' => $effectiveFrom,
            'effective_to' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween($effectiveFrom)->format('Y-m-d') : null,
            'is_primary' => $this->faker->boolean(80), // 80% are primary
            'metadata' => [
                'fte' => $this->faker->randomFloat(2, 0.1, 1.0),
                'employment_type' => $this->faker->randomElement(['Full-time', 'Part-time', 'Contract']),
            ],
        ];
    }

    public function primary(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => true,
            ];
        });
    }

    public function secondary(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => false,
            ];
        });
    }

    public function current(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'effective_from' => $this->faker->date('Y-m-d', '-1 year'),
                'effective_to' => null,
            ];
        });
    }

    public function historical(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'effective_from' => $this->faker->date('Y-m-d', '-2 years'),
                'effective_to' => $this->faker->date('Y-m-d', '-6 months'),
            ];
        });
    }
}