<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Atomy\Models\Project;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'client_id' => null,
            'project_manager_id' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'active',
            'budget' => 10000,
            'tenant_id' => 1,
        ];
    }
}