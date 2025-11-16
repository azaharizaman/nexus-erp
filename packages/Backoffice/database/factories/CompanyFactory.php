<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Database\Factories;

use Nexus\Backoffice\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->optional()->sentence(),
            'parent_company_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an inactive company.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for an active company.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory for a child company.
     */
    public function childOf(Company $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_company_id' => $parent->id,
        ]);
    }

    /**
     * Configure the factory for a root company (no parent).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_company_id' => null,
        ]);
    }
}
