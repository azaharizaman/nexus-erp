<?php

namespace Nexus\ProjectManagement\Contracts;

interface TaskRepositoryInterface
{
    public function create(array $data): TaskInterface;
    public function findById(int $id): ?TaskInterface;
    public function findByProject(int $projectId): array;
    public function findByAssignee(int $userId): array;
    public function update(TaskInterface $task, array $data): bool;
    public function delete(TaskInterface $task): bool;
    public function getOverdueTasks(int $tenantId): array;
}