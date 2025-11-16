<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Position;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'employee_id' => $this->faker->unique()->numerify('EMP####'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'office_id' => Office::factory(),
            'department_id' => null,
            'position_id' => null,
            'supervisor_id' => null,
            'hire_date' => $this->faker->dateTimeBetween('-5 years', '-1 month'),
            'resignation_date' => null,
            'resignation_reason' => null,
            'resigned_at' => null,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an inactive staff.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for an active staff.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for a resigned staff.
     */
    public function resigned(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffStatus::RESIGNED,
            'resignation_date' => $this->faker->dateTimeBetween('-6 months', '-1 day'),
            'resignation_reason' => $reason ?? $this->faker->sentence(),
            'resigned_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for a staff with pending resignation.
     */
    public function pendingResignation(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => $this->faker->dateTimeBetween('+1 day', '+2 months'),
            'resignation_reason' => $reason ?? $this->faker->sentence(),
            'resigned_at' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for a staff on probation.
     */
    public function onProbation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffStatus::ON_PROBATION,
            'hire_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for a suspended staff.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffStatus::SUSPENDED,
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for a staff on leave.
     */
    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffStatus::ON_LEAVE,
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for a staff with a supervisor.
     */
    public function withSupervisor(Staff $supervisor): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_id' => $supervisor->id,
            // Ensure staff is in same office as supervisor if supervisor has an office
            'office_id' => $supervisor->office_id ?? $attributes['office_id'],
        ]);
    }

    /**
     * Configure the factory for a top-level staff (no supervisor).
     */
    public function topLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_id' => null,
        ]);
    }

    /**
     * Configure the factory for a manager (with subordinates).
     * Note: This doesn't create subordinates, just sets it up as a potential manager.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'position_id' => Position::factory()->management(),
        ]);
    }

    public function seniorManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'position_id' => Position::factory()->seniorManagement(),
        ]);
    }

    public function hr(): static
    {
        return $this->state(fn (array $attributes) => [
            'position_id' => Position::factory()->hr(),
        ]);
    }

    /**
     * Configure the factory for staff in a specific department.
     */
    public function inDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
            'office_id' => $attributes['office_id'], // Keep the office if set
        ]);
    }

    /**
     * Configure the factory for staff in a specific office.
     */
    public function inOffice(Office $office): static
    {
        return $this->state(fn (array $attributes) => [
            'office_id' => $office->id,
        ]);
    }

    /**
     * Configure the factory for staff with both office and department.
     */
    public function withBoth(Office $office, Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'office_id' => $office->id,
            'department_id' => $department->id,
        ]);
    }

    /**
     * Configure the factory for staff with no office (department only).
     */
    public function departmentOnly(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'office_id' => null,
            'department_id' => $department->id,
        ]);
    }

    /**
     * Configure the factory for staff with a position.
     */
    public function withPosition(?Position $position = null): static
    {
        return $this->state(fn (array $attributes) => [
            'position_id' => $position?->id ?? Position::factory(),
        ]);
    }

    /**
     * Configure the factory for CEO/President (top executive).
     */
    public function ceo(): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_id' => null,
        ]);
    }
}
