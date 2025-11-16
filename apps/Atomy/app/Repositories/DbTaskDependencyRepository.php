<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\TaskDependencyRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TaskDependencyInterface;
use Nexus\Atomy\Models\TaskDependency;

class DbTaskDependencyRepository implements TaskDependencyRepositoryInterface
{
    public function create(array $data): TaskDependencyInterface
    {
        return TaskDependency::create($data);
    }

    public function findByTask(int $taskId): array
    {
        return TaskDependency::where('task_id', $taskId)->get()->all();
    }

    public function findDependents(int $taskId): array
    {
        return TaskDependency::where('depends_on_task_id', $taskId)->get()->all();
    }

    public function delete(TaskDependencyInterface $dependency): bool
    {
        return TaskDependency::where('id', $dependency->getId())->delete() > 0;
    }
}