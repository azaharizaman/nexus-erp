<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Atomy\Models\Task;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        return [
            'project_id' => 1,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'assignee_id' => null,
            'due_date' => now()->addDays(7)->toDateString(),
            'priority' => 'medium',
            'status' => 'to_do',
            'parent_task_id' => null,
            'tenant_id' => 1,
        ];
    }
}