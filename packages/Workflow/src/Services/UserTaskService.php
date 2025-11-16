<?php

declare(strict_types=1);

namespace Nexus\Workflow\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nexus\Workflow\Models\UserTask;
use Nexus\Workflow\Models\WorkflowInstance;

/**
 * UserTaskService
 * 
 * Manages user task lifecycle, assignment, and completion.
 * Provides inbox/outbox queries for task management.
 */
class UserTaskService
{
    /**
     * Create a new user task.
     * 
     * @param array $data Task data
     * @return UserTask
     * @throws ValidationException
     */
    public function create(array $data): UserTask
    {
        $this->validateTaskData($data);

        return DB::transaction(function () use ($data) {
            $task = UserTask::create([
                'workflow_instance_id' => $data['workflow_instance_id'],
                'transition' => $data['transition'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'assigned_by' => $data['assigned_by'] ?? null,
                'status' => $data['status'] ?? UserTask::STATUS_PENDING,
                'priority' => $data['priority'] ?? UserTask::PRIORITY_MEDIUM,
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);

            return $task;
        });
    }

    /**
     * Create multiple tasks for a workflow instance.
     * Useful for parallel approval strategies.
     * 
     * @param string $workflowInstanceId Workflow instance ID
     * @param string $transition Transition name
     * @param array $assignees Array of user IDs
     * @param array $options Optional task options (priority, due_at, etc.)
     * @return Collection Collection of created tasks
     */
    public function createBulk(
        string $workflowInstanceId,
        string $transition,
        array $assignees,
        array $options = []
    ): Collection {
        return DB::transaction(function () use ($workflowInstanceId, $transition, $assignees, $options) {
            $tasks = collect();

            foreach ($assignees as $assigneeId) {
                $task = $this->create([
                    'workflow_instance_id' => $workflowInstanceId,
                    'transition' => $transition,
                    'assigned_to' => $assigneeId,
                    'assigned_by' => $options['assigned_by'] ?? null,
                    'status' => $options['status'] ?? UserTask::STATUS_PENDING,
                    'priority' => $options['priority'] ?? UserTask::PRIORITY_MEDIUM,
                    'title' => $options['title'] ?? null,
                    'description' => $options['description'] ?? null,
                    'due_at' => $options['due_at'] ?? null,
                ]);

                $tasks->push($task);
            }

            return $tasks;
        });
    }

    /**
     * Assign a task to a user.
     * 
     * @param string $taskId Task ID
     * @param string $userId User ID to assign to
     * @param string|null $assignedBy User ID performing the assignment
     * @return UserTask
     */
    public function assign(string $taskId, string $userId, ?string $assignedBy = null): UserTask
    {
        $task = UserTask::findOrFail($taskId);

        if ($task->isCompleted() || $task->isCancelled()) {
            throw ValidationException::withMessages([
                'task' => ['Cannot reassign a completed or cancelled task.'],
            ]);
        }

        return DB::transaction(function () use ($task, $userId, $assignedBy) {
            $task->reassign($userId, $assignedBy);
            return $task->fresh();
        });
    }

    /**
     * Mark task as in progress.
     * 
     * @param string $taskId Task ID
     * @return UserTask
     */
    public function startTask(string $taskId): UserTask
    {
        $task = UserTask::findOrFail($taskId);

        if (!$task->isPending()) {
            throw ValidationException::withMessages([
                'task' => ['Can only start pending tasks.'],
            ]);
        }

        return DB::transaction(function () use ($task) {
            $task->markAsInProgress();
            return $task->fresh();
        });
    }

    /**
     * Complete a task with result data.
     * 
     * @param string $taskId Task ID
     * @param array $result Task result data (e.g., approval decision, comments)
     * @param string|null $completedBy User ID completing the task
     * @return UserTask
     */
    public function complete(string $taskId, array $result = [], ?string $completedBy = null): UserTask
    {
        $task = UserTask::findOrFail($taskId);

        if ($task->isCompleted() || $task->isCancelled()) {
            throw ValidationException::withMessages([
                'task' => ['Task is already completed or cancelled.'],
            ]);
        }

        return DB::transaction(function () use ($task, $result, $completedBy) {
            $task->markAsCompleted($result, $completedBy);
            return $task->fresh();
        });
    }

    /**
     * Cancel a task.
     * 
     * @param string $taskId Task ID
     * @return UserTask
     */
    public function cancel(string $taskId): UserTask
    {
        $task = UserTask::findOrFail($taskId);

        if ($task->isCompleted()) {
            throw ValidationException::withMessages([
                'task' => ['Cannot cancel a completed task.'],
            ]);
        }

        return DB::transaction(function () use ($task) {
            $task->markAsCancelled();
            return $task->fresh();
        });
    }

    /**
     * Get user's inbox (tasks assigned to them).
     * 
     * @param string $userId User ID
     * @param string|null $status Optional status filter
     * @return Collection
     */
    public function getInbox(string $userId, ?string $status = null): Collection
    {
        $query = UserTask::assignedTo($userId)
            ->with(['instance.subject', 'instance.definition'])
            ->orderByPriority()
            ->orderBy('due_at')
            ->orderBy('created_at');

        if ($status) {
            $query->withStatus($status);
        } else {
            // Default: show pending and in-progress tasks
            $query->whereIn('status', [UserTask::STATUS_PENDING, UserTask::STATUS_IN_PROGRESS]);
        }

        return $query->get();
    }

    /**
     * Get user's pending tasks only.
     * 
     * @param string $userId User ID
     * @return Collection
     */
    public function getPendingTasks(string $userId): Collection
    {
        return UserTask::assignedTo($userId)
            ->pending()
            ->with(['instance.subject', 'instance.definition'])
            ->orderByPriority()
            ->orderBy('due_at')
            ->get();
    }

    /**
     * Get user's overdue tasks.
     * 
     * @param string $userId User ID
     * @return Collection
     */
    public function getOverdueTasks(string $userId): Collection
    {
        return UserTask::assignedTo($userId)
            ->overdue()
            ->with(['instance.subject', 'instance.definition'])
            ->orderByPriority()
            ->orderBy('due_at')
            ->get();
    }

    /**
     * Get user's completed tasks.
     * 
     * @param string $userId User ID
     * @param int $limit Maximum number of tasks to return
     * @return Collection
     */
    public function getCompletedTasks(string $userId, int $limit = 50): Collection
    {
        return UserTask::assignedTo($userId)
            ->completed()
            ->with(['instance.subject', 'instance.definition'])
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all tasks for a workflow instance.
     * 
     * @param string $workflowInstanceId Workflow instance ID
     * @return Collection
     */
    public function getTasksForWorkflow(string $workflowInstanceId): Collection
    {
        return UserTask::where('workflow_instance_id', $workflowInstanceId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get task statistics for a user.
     * 
     * @param string $userId User ID
     * @return array Statistics array
     */
    public function getTaskStatistics(string $userId): array
    {
        $baseQuery = UserTask::assignedTo($userId);

        return [
            'pending' => (clone $baseQuery)->pending()->count(),
            'in_progress' => (clone $baseQuery)->inProgress()->count(),
            'completed_today' => (clone $baseQuery)->completed()
                ->whereDate('completed_at', today())
                ->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),
            'total_completed' => (clone $baseQuery)->completed()->count(),
        ];
    }

    /**
     * Update task priority.
     * 
     * @param string $taskId Task ID
     * @param string $priority New priority
     * @return UserTask
     */
    public function updatePriority(string $taskId, string $priority): UserTask
    {
        $task = UserTask::findOrFail($taskId);

        $validPriorities = [
            UserTask::PRIORITY_LOW,
            UserTask::PRIORITY_MEDIUM,
            UserTask::PRIORITY_HIGH,
            UserTask::PRIORITY_URGENT,
        ];

        if (!in_array($priority, $validPriorities)) {
            throw ValidationException::withMessages([
                'priority' => ['Invalid priority value.'],
            ]);
        }

        return DB::transaction(function () use ($task, $priority) {
            $task->priority = $priority;
            $task->save();
            return $task->fresh();
        });
    }

    /**
     * Update task due date.
     * 
     * @param string $taskId Task ID
     * @param \DateTimeInterface|string|null $dueAt New due date
     * @return UserTask
     */
    public function updateDueDate(string $taskId, $dueAt): UserTask
    {
        $task = UserTask::findOrFail($taskId);

        return DB::transaction(function () use ($task, $dueAt) {
            $task->due_at = $dueAt;
            $task->save();
            return $task->fresh();
        });
    }

    /**
     * Cancel all pending tasks for a workflow instance.
     * Useful when workflow is cancelled or completed.
     * 
     * @param string $workflowInstanceId Workflow instance ID
     * @return int Number of tasks cancelled
     */
    public function cancelWorkflowTasks(string $workflowInstanceId): int
    {
        return DB::transaction(function () use ($workflowInstanceId) {
            return UserTask::where('workflow_instance_id', $workflowInstanceId)
                ->whereIn('status', [UserTask::STATUS_PENDING, UserTask::STATUS_IN_PROGRESS])
                ->update(['status' => UserTask::STATUS_CANCELLED]);
        });
    }

    /**
     * Validate task data.
     * 
     * @param array $data Task data
     * @throws ValidationException
     */
    protected function validateTaskData(array $data): void
    {
        // Verify workflow instance exists
        if (!WorkflowInstance::find($data['workflow_instance_id'])) {
            throw ValidationException::withMessages([
                'workflow_instance_id' => ['Workflow instance not found.'],
            ]);
        }

        // Validate status if provided
        if (isset($data['status'])) {
            $validStatuses = [
                UserTask::STATUS_PENDING,
                UserTask::STATUS_IN_PROGRESS,
                UserTask::STATUS_COMPLETED,
                UserTask::STATUS_CANCELLED,
            ];

            if (!in_array($data['status'], $validStatuses)) {
                throw ValidationException::withMessages([
                    'status' => ['Invalid task status.'],
                ]);
            }
        }

        // Validate priority if provided
        if (isset($data['priority'])) {
            $validPriorities = [
                UserTask::PRIORITY_LOW,
                UserTask::PRIORITY_MEDIUM,
                UserTask::PRIORITY_HIGH,
                UserTask::PRIORITY_URGENT,
            ];

            if (!in_array($data['priority'], $validPriorities)) {
                throw ValidationException::withMessages([
                    'priority' => ['Invalid task priority.'],
                ]);
            }
        }
    }
}
