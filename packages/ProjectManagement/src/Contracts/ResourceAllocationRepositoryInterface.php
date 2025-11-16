<?php

namespace Nexus\ProjectManagement\Contracts;

interface ResourceAllocationRepositoryInterface
{
    public function create(array $data): ResourceAllocationInterface;
    public function findById(int $id): ?ResourceAllocationInterface;
    public function findByProject(int $projectId): array;
    public function findByUser(int $userId): array;
    public function update(ResourceAllocationInterface $allocation, array $data): bool;
    public function delete(ResourceAllocationInterface $allocation): bool;
    public function getOverallocation(int $tenantId): array;
}