<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Atomy\Models\User;
use Nexus\Atomy\Models\Project;
use Nexus\Atomy\Models\ResourceAllocation;
use Nexus\Atomy\Repositories\DbResourceAllocationRepository;

uses(RefreshDatabase::class);

it('detects overallocated resources', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['tenant_id' => $user->tenant_id]);

    ResourceAllocation::create(['project_id' => $project->id, 'user_id' => $user->id, 'allocation_percentage' => 60, 'start_date' => now()->toDateString(), 'tenant_id' => $user->tenant_id]);
    ResourceAllocation::create(['project_id' => $project->id, 'user_id' => $user->id, 'allocation_percentage' => 50, 'start_date' => now()->toDateString(), 'tenant_id' => $user->tenant_id]);

    $repo = new DbResourceAllocationRepository();
    $result = $repo->getOverallocation($user->tenant_id);
    expect(count($result))->toBeGreaterThanOrEqual(1);
});