<?php

use Nexus\ProjectManagement\Services\TimesheetManager;
use Nexus\ProjectManagement\Contracts\TimesheetRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TimesheetInterface;

it('logs time and approves timesheet', function () {
    $repo = Mockery::mock(TimesheetRepositoryInterface::class);
    $data = ['task_id' => 1, 'user_id' => 1, 'date' => new DateTime(), 'hours' => 4.0, 'tenant_id' => 1];

    $ts = Mockery::mock(TimesheetInterface::class);
    $repo->shouldReceive('create')->with(Mockery::subset(['hours' => 4.0]))->andReturn($ts);
    $repo->shouldReceive('approve')->with($ts)->andReturn(true);

    $manager = new TimesheetManager($repo);
    $result = $manager->logTime(['task_id' => 1, 'user_id' => 1, 'date' => '2025-01-01', 'hours' => 4.0, 'tenant_id' => 1]);
    expect($result)->toBe($ts);

    expect($manager->approveTimesheet($ts))->toBeTrue();
});

it('rejects when hours non positive', function () {
    $repo = Mockery::mock(TimesheetRepositoryInterface::class);
    $manager = new TimesheetManager($repo);

    expect(fn() => $manager->logTime(['hours' => 0]))->toThrow(Nexus\ProjectManagement\Exceptions\TimesheetException::class);
});