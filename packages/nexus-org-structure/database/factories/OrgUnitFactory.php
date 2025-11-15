<?php

declare(strict_types=1);

namespace Database\Factories\Nexus\OrgStructure\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\OrgStructure\Models\OrgUnit;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Nexus\OrgStructure\Models\OrgUnit>
 */
class OrgUnitFactory extends Factory
{
    protected $model = OrgUnit::class;

    public function definition(): array
    {
        return [
            'tenant_id' => (string) $this->faker->uuid(),
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->lexify('DEPT???'),
            'parent_org_unit_id' => null,
            'metadata' => [
                'description' => $this->faker->sentence(),
                'location' => $this->faker->city(),
            ],
        ];
    }

    public function withParent(?string $parentId = null): self
    {
        return $this->state(function (array $attributes) use ($parentId) {
            return [
                'parent_org_unit_id' => $parentId ?? (string) $this->faker->uuid(),
            ];
        });
    }
}