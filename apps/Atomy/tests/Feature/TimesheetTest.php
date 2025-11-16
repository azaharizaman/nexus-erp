<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Atomy\Models\User;
use Nexus\Atomy\Models\Project;
use Nexus\Atomy\Models\Task;

uses(RefreshDatabase::class);

it('submits timesheet', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);
    $task = Task::factory()->create(['project_id' => $project->id, 'tenant_id' => $user->tenant_id]);

    $response = $this->actingAs($user)->postJson('/api/timesheets', [
        'task_id' => $task->id,
        'date' => now()->toDateString(),
        'hours' => 6.5,
        'description' => 'Worked on feature',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('timesheets', ['task_id' => $task->id, 'user_id' => $user->id]);
});

it('approves timesheet', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);
    $task = Task::factory()->create(['project_id' => $project->id, 'tenant_id' => $user->tenant_id]);

    $this->actingAs($user)->postJson('/api/timesheets', [
        'task_id' => $task->id,
        'date' => now()->toDateString(),
        'hours' => 2.5,
    ]);

    $ts = \Nexus\Atomy\Models\Timesheet::first();

    $response = $this->actingAs($user)->postJson("/api/timesheets/{$ts->id}/approve");
    $response->assertStatus(200);
    $this->assertDatabaseHas('timesheets', ['id' => $ts->id, 'status' => 'approved']);
});