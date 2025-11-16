<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Atomy\Models\User;
use Nexus\Atomy\Models\Project;
use Nexus\Atomy\Models\Task;
use Nexus\Atomy\Models\Timesheet;
use Nexus\Atomy\Repositories\DbTimesheetRepository;

uses(RefreshDatabase::class);

it('finds timesheets by project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);
    $task = Task::factory()->create(['project_id' => $project->id, 'tenant_id' => $user->tenant_id]);

    Timesheet::factory()->create(['task_id' => $task->id, 'user_id' => $user->id]);

    $repo = new DbTimesheetRepository();
    $ts = $repo->findByProject($project->id);
    expect(count($ts))->toBe(1);
});