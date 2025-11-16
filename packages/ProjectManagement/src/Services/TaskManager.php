<?php

namespace Nexus\ProjectManagement\Services;

use Nexus\ProjectManagement\Contracts\TaskRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TaskDependencyRepositoryInterface;
use Nexus\ProjectManagement\Contracts\TaskInterface;
use Nexus\ProjectManagement\Exceptions\TaskNotFoundException;

class TaskManager
{
    private TaskRepositoryInterface $taskRepository;
    private TaskDependencyRepositoryInterface $dependencyRepository;

    public function __construct(TaskRepositoryInterface $taskRepository, ?TaskDependencyRepositoryInterface $dependencyRepository = null)
    {
        $this->taskRepository = $taskRepository;
        $this->dependencyRepository = $dependencyRepository;
    }

    // Backwards compatible constructor removed - use single constructor instead

    public function createTask(array $data): TaskInterface
    {
        // Validate data, assign to project, etc.
        return $this->taskRepository->create($data);
    }

    public function getTask(int $id): TaskInterface
    {
        $task = $this->taskRepository->findById($id);
        if (!$task) {
            throw new TaskNotFoundException("Task with ID $id not found");
        }
        return $task;
    }

    public function assignTask(TaskInterface $task, int $userId): bool
    {
        return $this->taskRepository->update($task, ['assignee_id' => $userId]);
    }

    public function completeTask(TaskInterface $task): bool
    {
        return $this->taskRepository->update($task, ['status' => 'completed']);
    }

    public function addDependency(int $taskId, int $dependsOnTaskId): bool
    {
        $this->dependencyRepository->create(['task_id' => $taskId, 'depends_on_task_id' => $dependsOnTaskId]);
        return true;
    }

    public function canStartTask(TaskInterface $task): bool
    {
        $deps = $this->dependencyRepository->findByTask($task->getId());
        foreach ($deps as $dep) {
            $dependsOnTask = $this->taskRepository->findById($dep->getDependsOnTaskId());
            if ($dependsOnTask && $dependsOnTask->getStatus() !== 'completed') {
                return false;
            }
        }
        return true;
    }

    public function getTasksByProject(int $projectId): array
    {
        return $this->taskRepository->findByProject($projectId);
    }

    public function getTasksByAssignee(int $userId): array
    {
        return $this->taskRepository->findByAssignee($userId);
    }

    public function startTask(TaskInterface $task): bool
    {
        if ($this->dependencyRepository && !$this->canStartTask($task)) {
            return false;
        }

        return $this->taskRepository->update($task, ['status' => 'in_progress']);
    }
}