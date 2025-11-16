<?php

namespace Nexus\ProjectManagement\Contracts;

interface TaskDependencyRepositoryInterface
{
    public function create(array $data): TaskDependencyInterface;
    public function findByTask(int $taskId): array; // tasks this one depends on
    public function findDependents(int $taskId): array; // tasks that depend on this
    public function delete(TaskDependencyInterface $dependency): bool;
}
