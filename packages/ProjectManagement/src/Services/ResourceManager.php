<?php

namespace Nexus\ProjectManagement\Services;

use Nexus\ProjectManagement\Contracts\ResourceAllocationRepositoryInterface;
use Nexus\ProjectManagement\Contracts\ResourceAllocationInterface;

class ResourceManager
{
    private ResourceAllocationRepositoryInterface $allocationRepository;

    public function __construct(ResourceAllocationRepositoryInterface $allocationRepository)
    {
        $this->allocationRepository = $allocationRepository;
    }

    public function allocateResource(array $data): ResourceAllocationInterface
    {
        // Check for overallocation
        $existing = $this->allocationRepository->findByUser($data['user_id']);
        $total = array_sum(array_map(fn($a) => $a->getAllocationPercentage(), $existing)) + $data['allocation_percentage'];
        if ($total > 100) {
            throw new \Exception("Overallocation: total {$total}%");
        }
        return $this->allocationRepository->create($data);
    }

    public function getOverallocatedResources(int $tenantId): array
    {
        return $this->allocationRepository->getOverallocation($tenantId);
    }

    public function getAllocationsByProject(int $projectId): array
    {
        return $this->allocationRepository->findByProject($projectId);
    }
}