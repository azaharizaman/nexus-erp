<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Position;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Enums\PositionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'name' => $this->faker->jobTitle(),
            'code' => strtoupper($this->faker->unique()->lexify('POS-???')),
            'gred' => $this->faker->optional()->randomElement(['A', 'B', 'C', 'D', 'M40', 'M44', 'M48', 'M52', 'M54']),
            'type' => $this->faker->randomElement(PositionType::cases())->value,
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an active position.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for an inactive position.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory with a default department.
     */
    public function withDepartment(?Department $department = null): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department?->id ?? Department::factory(),
        ]);
    }

    /**
     * Configure the factory for HR position.
     */
    public function hr(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::HR,
            'name' => $this->faker->randomElement([
                'HR Manager',
                'HR Executive',
                'HR Specialist',
            ]),
        ]);
    }

    /**
     * Configure the factory for C-Level position.
     */
    public function cLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::C_LEVEL,
            'name' => $this->faker->randomElement([
                'Chief Executive Officer',
                'Chief Operating Officer',
                'Chief Financial Officer',
                'Chief Technology Officer',
            ]),
        ]);
    }

    /**
     * Configure the factory for Top Management position.
     */
    public function seniorManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::TOP_MANAGEMENT,
            'name' => $this->faker->randomElement([
                'Vice President',
                'Senior Vice President',
                'General Manager',
            ]),
        ]);
    }

    /**
     * Configure the factory for Management position.
     */
    public function management(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::MANAGEMENT,
            'name' => $this->faker->randomElement([
                'Manager',
                'Department Head',
                'Director',
            ]),
        ]);
    }

    /**
     * Configure the factory for Junior Management position.
     */
    public function juniorManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::JUNIOR_MANAGEMENT,
            'name' => $this->faker->randomElement([
                'Assistant Manager',
                'Team Leader',
                'Supervisor',
            ]),
        ]);
    }

    /**
     * Configure the factory for Senior Executive position.
     */
    public function seniorExecutive(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::SENIOR_EXECUTIVE,
            'name' => $this->faker->randomElement([
                'Senior Executive',
                'Senior Officer',
                'Senior Specialist',
            ]),
        ]);
    }

    /**
     * Configure the factory for Executive position.
     */
    public function executive(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::EXECUTIVE,
            'name' => $this->faker->randomElement([
                'Executive',
                'Officer',
                'Specialist',
            ]),
        ]);
    }

    /**
     * Configure the factory for Junior Executive position.
     */
    public function juniorExecutive(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::JUNIOR_EXECUTIVE,
            'name' => $this->faker->randomElement([
                'Junior Executive',
                'Junior Officer',
                'Assistant Officer',
            ]),
        ]);
    }

    /**
     * Configure the factory for Non-Executive position.
     */
    public function nonExecutive(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::NON_EXECUTIVE,
            'name' => $this->faker->randomElement([
                'Technician',
                'Coordinator',
                'Support Staff',
            ]),
        ]);
    }

    /**
     * Configure the factory for Clerical position.
     */
    public function clerical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::CLERICAL,
            'name' => $this->faker->randomElement([
                'Clerk',
                'Data Entry',
                'Secretary',
            ]),
        ]);
    }

    /**
     * Configure the factory for Assistant position.
     */
    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PositionType::ASSISTANT,
            'name' => $this->faker->randomElement([
                'Assistant',
                'Admin Assistant',
                'Office Assistant',
            ]),
        ]);
    }
}
