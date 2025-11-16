<?php

use Nexus\ProjectManagement\Services\MilestoneManager;
use Nexus\ProjectManagement\Contracts\MilestoneRepositoryInterface;
use Nexus\ProjectManagement\Contracts\MilestoneInterface;

it('creates and approves milestone', function () {
    $repo = Mockery::mock(MilestoneRepositoryInterface::class);
    $repo->shouldReceive('create')->with(Mockery::type('array'))->andReturn(Mockery::mock(MilestoneInterface::class));
    $repo->shouldReceive('approve')->andReturn(true);

    $manager = new MilestoneManager($repo);
    $milestone = $manager->createMilestone(['project_id' => 1, 'name' => 'M1', 'due_date' => '2025-12-01']);
    expect($milestone)->toBeObject();
    expect($manager->approveMilestone($milestone))->toBeTrue();
});