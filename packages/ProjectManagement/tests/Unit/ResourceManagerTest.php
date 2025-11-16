<?php

use Nexus\ProjectManagement\Services\ResourceManager;
use Nexus\ProjectManagement\Contracts\ResourceAllocationRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ResourceAllocationInterface;

it('allocates resource when not overallocated', function () {
    $repo = Mockery::mock(ResourceAllocationRepositoryInterface::class);
    $repo->shouldReceive('findByUser')->with(1)->andReturn([]);
    $repo->shouldReceive('create')->andReturn(Mockery::mock(ResourceAllocationInterface::class));

    $manager = new ResourceManager($repo);
    $allocation = ['project_id' => 1, 'user_id' => 1, 'allocation_percentage' => 50, 'start_date' => now()->toDateString(), 'tenant_id' => 1];

    $res = $manager->allocateResource($allocation);
    expect($res)->toBeObject();
});

it('throws on overallocation', function () {
    $repo = Mockery::mock(ResourceAllocationRepositoryInterface::class);
    $existing = [Mockery::mock(ResourceAllocationInterface::class)];
    $existing[0]->shouldReceive('getAllocationPercentage')->andReturn(60);
    $repo->shouldReceive('findByUser')->with(1)->andReturn($existing);

    $manager = new ResourceManager($repo);
    $allocation = ['project_id' => 1, 'user_id' => 1, 'allocation_percentage' => 50, 'start_date' => now()->toDateString(), 'tenant_id' => 1];

    expect(fn() => $manager->allocateResource($allocation))->toThrow(Exception::class);
});