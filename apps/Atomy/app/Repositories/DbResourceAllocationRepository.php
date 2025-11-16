<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\ResourceAllocationRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ResourceAllocationInterface;
use Nexus\Atomy\Models\ResourceAllocation;

class DbResourceAllocationRepository implements ResourceAllocationRepositoryInterface
{
    public function create(array $data): ResourceAllocationInterface
    {
        return ResourceAllocation::create($data);
    }

    public function findById(int $id): ?ResourceAllocationInterface
    {
        return ResourceAllocation::find($id);
    }

    public function findByProject(int $projectId): array
    {
        return ResourceAllocation::where('project_id', $projectId)->get()->all();
    }

    public function findByUser(int $userId): array
    {
        return ResourceAllocation::where('user_id', $userId)->get()->all();
    }

    public function update(ResourceAllocationInterface $allocation, array $data): bool
    {
        return ResourceAllocation::where('id', $allocation->getId())->update($data) > 0;
    }

    public function delete(ResourceAllocationInterface $allocation): bool
    {
        return ResourceAllocation::where('id', $allocation->getId())->delete() > 0;
    }

    public function getOverallocation(int $tenantId): array
    {
        // Simplified: get users with >100% allocation
        return ResourceAllocation::where('tenant_id', $tenantId)
            ->selectRaw('user_id, sum(allocation_percentage) as total')
            ->groupBy('user_id')
            ->having('total', '>', 100)
            ->get()->all();
    }
}