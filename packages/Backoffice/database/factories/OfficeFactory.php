<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Office>
 */
class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city() . ' Office',
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->optional()->sentence(),
            'company_id' => Company::factory(),
            'parent_office_id' => null,
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an inactive office.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for an active office.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for a child office.
     */
    public function childOf(Office $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_office_id' => $parent->id,
            'company_id' => $parent->company_id,
        ]);
    }

    /**
     * Configure the factory for a root office (no parent).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_office_id' => null,
        ]);
    }
}
