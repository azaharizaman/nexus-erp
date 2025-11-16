<?php

namespace Nexus\Atomy\Repositories;

use Nexus\ProjectManagement\Contracts\TaskRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TaskInterface;
use Nexus\Atomy\Models\Task;

class DbTaskRepository implements TaskRepositoryInterface
{
    public function create(array $data): TaskInterface
    {
        return Task::create($data);
    }

    public function findById(int $id): ?TaskInterface
    {
        return Task::find($id);
    }

    public function findByProject(int $projectId): array
    {
        return Task::where('project_id', $projectId)->get()->all();
    }

    public function findByAssignee(int $userId): array
    {
        return Task::where('assignee_id', $userId)->get()->all();
    }

    public function update(TaskInterface $task, array $data): bool
    {
        return Task::where('id', $task->getId())->update($data) > 0;
    }

    public function delete(TaskInterface $task): bool
    {
        return Task::where('id', $task->getId())->delete() > 0;
    }

    public function getOverdueTasks(int $tenantId): array
    {
        return Task::where('tenant_id', $tenantId)
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->get()->all();
    }
}