<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Atomy\Models\User;
use Nexus\Atomy\Models\Project;
use Nexus\Atomy\Models\Task;
use Nexus\Atomy\Models\TaskDependency;

uses(RefreshDatabase::class);

it('prevents starting when predecessor not completed via API', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);

    $taskA = Task::factory()->create(['project_id' => $project->id, 'tenant_id' => $user->tenant_id]);
    $taskB = Task::factory()->create(['project_id' => $project->id, 'tenant_id' => $user->tenant_id]);

    TaskDependency::create(['task_id' => $taskB->id, 'depends_on_task_id' => $taskA->id]);

    // taskA not completed
    $response = $this->actingAs($user)->postJson("/api/tasks/{$taskB->id}/start");
    $response->assertStatus(422);

    // complete taskA
    $this->actingAs($user)->postJson("/api/tasks/{$taskA->id}/complete");

    $response = $this->actingAs($user)->postJson("/api/tasks/{$taskB->id}/start");
    $response->assertStatus(200);
});