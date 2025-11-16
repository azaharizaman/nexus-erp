<?php

use Nexus\ProjectManagement\Services\BudgetManager;
use Nexus\ProjectManagement\Contracts\TimesheetRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ExpenseRepositoryInterface;
use Nexus\ProjectManagement\Contracts\BillingRateProviderInterface;
use Nexus\ProjectManagement\Contracts\ProjectInterface;

test('calculate actual cost and budget variance', function () {
    $timesheetRepo = Mockery::mock(TimesheetRepositoryInterface::class);
    $expenseRepo = Mockery::mock(ExpenseRepositoryInterface::class);
    $billing = Mockery::mock(BillingRateProviderInterface::class);

    $project = Mockery::mock(ProjectInterface::class);
    $project->shouldReceive('getId')->andReturn(1);
    $project->shouldReceive('getBudget')->andReturn(200.0);

    $t1 = Mockery::mock();
    $t1->shouldReceive('getUserId')->andReturn(1);
    $t1->shouldReceive('getHours')->andReturn(2.0);

    $t2 = Mockery::mock();
    $t2->shouldReceive('getUserId')->andReturn(2);
    $t2->shouldReceive('getHours')->andReturn(3.0);

    $timesheetRepo->shouldReceive('findByProject')->with(1)->andReturn([$t1, $t2]);
    $expenseRepo->shouldReceive('findByProject')->with(1)->andReturn([]);
    $billing->shouldReceive('getHourlyRateForUser')->with(1)->andReturn(10.0);
    $billing->shouldReceive('getHourlyRateForUser')->with(2)->andReturn(20.0);

    $manager = new BudgetManager($timesheetRepo, $expenseRepo, $billing);
    $actual = $manager->calculateActualCost($project);
    // labor cost: 2*10 + 3*20 = 70
    expect($actual)->toBe(70);
    expect($manager->getBudgetVariance($project))->toBe(130);
});