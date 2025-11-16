<?php

use Nexus\ProjectManagement\Services\TaskManager;
use Nexus\ProjectManagement\Contracts\TaskRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TaskDependencyRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TaskInterface;

it('prevents starting when predecessor not completed', function () {
    $taskRepo = Mockery::mock(TaskRepositoryInterface::class);
    $depRepo = Mockery::mock(TaskDependencyRepositoryInterface::class);

    $task = Mockery::mock(TaskInterface::class);
    $task->shouldReceive('getId')->andReturn(10);

    $dependency = Mockery::mock();
    $dependency->shouldReceive('getDependsOnTaskId')->andReturn(5);
    $depRepo->shouldReceive('findByTask')->with(10)->andReturn([$dependency]);

    $depTask = Mockery::mock(TaskInterface::class);
    $depTask->shouldReceive('getStatus')->andReturn('in_progress');
    $taskRepo->shouldReceive('findById')->with(5)->andReturn($depTask);

    $manager = new TaskManager($taskRepo, $depRepo);
    expect($manager->canStartTask($task))->toBeFalse();
});

it('allows starting when predecessor completed', function () {
    $taskRepo = Mockery::mock(TaskRepositoryInterface::class);
    $depRepo = Mockery::mock(TaskDependencyRepositoryInterface::class);

    $task = Mockery::mock(TaskInterface::class);
    $task->shouldReceive('getId')->andReturn(10);

    $dependency = Mockery::mock();
    $dependency->shouldReceive('getDependsOnTaskId')->andReturn(5);
    $depRepo->shouldReceive('findByTask')->with(10)->andReturn([$dependency]);

    $depTask = Mockery::mock(TaskInterface::class);
    $depTask->shouldReceive('getStatus')->andReturn('completed');
    $taskRepo->shouldReceive('findById')->with(5)->andReturn($depTask);

    $taskRepo->shouldReceive('update')->with($task, ['status' => 'in_progress'])->andReturn(true);

    $manager = new TaskManager($taskRepo, $depRepo);
    expect($manager->startTask($task))->toBeTrue();
});