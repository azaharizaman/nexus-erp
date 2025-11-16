<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $departments = [
            'Human Resources',
            'Information Technology',
            'Finance',
            'Marketing',
            'Sales',
            'Operations',
            'Customer Service',
            'Research and Development',
            'Legal',
            'Administration',
            'Engineering',
            'Quality Assurance',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($departments),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->optional()->sentence(),
            'company_id' => Company::factory(),
            'parent_department_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an inactive department.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for an active department.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for a child department.
     */
    public function childOf(Department $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_department_id' => $parent->id,
            'company_id' => $parent->company_id,
        ]);
    }

    /**
     * Configure the factory for a root department (no parent).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_department_id' => null,
        ]);
    }
}
