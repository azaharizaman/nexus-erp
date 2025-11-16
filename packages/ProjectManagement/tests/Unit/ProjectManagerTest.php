<?php

use Nexus\ProjectManagement\Services\ProjectManager;
use Nexus\ProjectManagement\Contracts\ProjectRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ProjectInterface;
use Nexus\ProjectManagement\Exceptions\ProjectNotFoundException;

test('create project', function () {
    $repo = Mockery::mock(ProjectRepositoryInterface::class);
    $data = ['name' => 'Test Project', 'project_manager_id' => 1, 'tenant_id' => 1];
    $project = Mockery::mock(ProjectInterface::class);
    $repo->shouldReceive('create')->with($data)->andReturn($project);

    $manager = new ProjectManager($repo);
    $result = $manager->createProject($data);

    expect($result)->toBe($project);
});

test('get project', function () {
    $repo = Mockery::mock(ProjectRepositoryInterface::class);
    $project = Mockery::mock(ProjectInterface::class);
    $repo->shouldReceive('findById')->with(1)->andReturn($project);

    $manager = new ProjectManager($repo);
    $result = $manager->getProject(1);

    expect($result)->toBe($project);
});

test('get project not found', function () {
    $repo = Mockery::mock(ProjectRepositoryInterface::class);
    $repo->shouldReceive('findById')->with(1)->andReturn(null);

    $manager = new ProjectManager($repo);
    expect(fn() => $manager->getProject(1))->toThrow(ProjectNotFoundException::class);
});

test('update project', function () {
    $repo = Mockery::mock(ProjectRepositoryInterface::class);
    $project = Mockery::mock(ProjectInterface::class);
    $data = ['name' => 'Updated'];
    $repo->shouldReceive('update')->with($project, $data)->andReturn(true);

    $manager = new ProjectManager($repo);
    $result = $manager->updateProject($project, $data);

    expect($result)->toBe(true);
});

test('delete project', function () {
    $repo = Mockery::mock(ProjectRepositoryInterface::class);
    $project = Mockery::mock(ProjectInterface::class);
    $repo->shouldReceive('delete')->with($project)->andReturn(true);

    $manager = new ProjectManager($repo);
    $result = $manager->deleteProject($project);

    expect($result)->toBe(true);
});

test('get active projects', function () {
    $repo = Mockery::mock(ProjectRepositoryInterface::class);
    $projects = [Mockery::mock(ProjectInterface::class)];
    $repo->shouldReceive('getActiveProjects')->with(1)->andReturn($projects);

    $manager = new ProjectManager($repo);
    $result = $manager->getActiveProjects(1);

    expect($result)->toBe($projects);
});