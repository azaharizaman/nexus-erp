<?php

declare(strict_types=1);

namespace Database\Factories\Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\OrgStructure\Models\ReportingLine;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\OrgStructure\Models\ReportingLine>
 */
class ReportingLineFactory extends Factory
{
    protected $model = ReportingLine::class;

    public function definition(): array
    {
        $effectiveFrom = $this->faker->date();

        return [
            'tenant_id' => (string) $this->faker->uuid(),
            'manager_employee_id' => (string) $this->faker->uuid(),
            'subordinate_employee_id' => (string) $this->faker->uuid(),
            'position_id' => (string) $this->faker->uuid(),
            'effective_from' => $effectiveFrom,
            'effective_to' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween($effectiveFrom)->format('Y-m-d') : null,
            'metadata' => [
                'relationship_type' => $this->faker->randomElement(['Direct', 'Dotted-line', 'Matrix']),
                'authority_level' => $this->faker->randomElement(['Full', 'Partial', 'Advisory']),
            ],
        ];
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

    public function direct(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'relationship_type' => 'Direct',
                ]),
            ];
        });
    }

    public function dottedLine(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'relationship_type' => 'Dotted-line',
                ]),
            ];
        });
    }
}