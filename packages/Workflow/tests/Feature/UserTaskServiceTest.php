<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Nexus\Workflow\Models\UserTask;
use Nexus\Workflow\Models\WorkflowDefinition;
use Nexus\Workflow\Models\WorkflowInstance;
use Nexus\Workflow\Services\UserTaskService;

beforeEach(function () {
    $this->service = new UserTaskService();
    
    // Create a test workflow definition
    $this->definition = WorkflowDefinition::create([
        'name' => 'Test Workflow',
        'key' => 'test-workflow',
        'version' => 1,
        'definition' => [
            'states' => ['pending', 'approved', 'completed'],
            'transitions' => [
                ['name' => 'approve', 'from' => 'pending', 'to' => 'approved'],
                ['name' => 'complete', 'from' => 'approved', 'to' => 'completed'],
            ],
            'initial_state' => 'pending',
        ],
        'is_active' => true,
    ]);
    
    // Create a test workflow instance
    $this->instance = WorkflowInstance::create([
        'workflow_definition_id' => $this->definition->id,
        'subject_type' => 'App\\Models\\DummyModel',
        'subject_id' => 'dummy-uuid',
        'current_state' => 'pending',
        'started_at' => now(),
    ]);
    
    $this->userId1 = 'user-uuid-1';
    $this->userId2 = 'user-uuid-2';
});

describe('UserTaskService - Create', function () {
    test('can create a new task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'title' => 'Review and approve',
        ]);

        expect($task)->toBeInstanceOf(UserTask::class)
            ->and($task->workflow_instance_id)->toBe($this->instance->id)
            ->and($task->transition)->toBe('approve')
            ->and($task->assigned_to)->toBe($this->userId1)
            ->and($task->status)->toBe(UserTask::STATUS_PENDING)
            ->and($task->priority)->toBe(UserTask::PRIORITY_MEDIUM);
    });

    test('can create task with custom priority', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'priority' => UserTask::PRIORITY_URGENT,
        ]);

        expect($task->priority)->toBe(UserTask::PRIORITY_URGENT);
    });

    test('can create task with due date', function () {
        $dueDate = now()->addDays(3);
        
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'due_at' => $dueDate,
        ]);

        expect($task->due_at->equalTo($dueDate))->toBeTrue();
    });

    test('throws error for nonexistent workflow instance', function () {
        expect(fn() => $this->service->create([
            'workflow_instance_id' => 'nonexistent-uuid',
            'transition' => 'approve',
        ]))->toThrow(ValidationException::class);
    });

    test('throws error for invalid status', function () {
        expect(fn() => $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'status' => 'invalid_status',
        ]))->toThrow(ValidationException::class);
    });

    test('throws error for invalid priority', function () {
        expect(fn() => $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'priority' => 'invalid_priority',
        ]))->toThrow(ValidationException::class);
    });
});

describe('UserTaskService - Bulk Create', function () {
    test('can create multiple tasks for multiple assignees', function () {
        $assignees = [$this->userId1, $this->userId2, 'user-uuid-3'];
        
        $tasks = $this->service->createBulk(
            $this->instance->id,
            'approve',
            $assignees,
            ['title' => 'Review document', 'priority' => UserTask::PRIORITY_HIGH]
        );

        expect($tasks)->toHaveCount(3)
            ->and($tasks->pluck('assigned_to')->toArray())->toBe($assignees)
            ->and($tasks->first()->title)->toBe('Review document')
            ->and($tasks->first()->priority)->toBe(UserTask::PRIORITY_HIGH);
    });

    test('bulk create uses transaction', function () {
        $tasks = $this->service->createBulk(
            $this->instance->id,
            'approve',
            [$this->userId1, $this->userId2]
        );

        expect(UserTask::count())->toBe(2);
    });
});

describe('UserTaskService - Assignment', function () {
    test('can assign task to another user', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $reassigned = $this->service->assign($task->id, $this->userId2, 'admin-uuid');

        expect($reassigned->assigned_to)->toBe($this->userId2)
            ->and($reassigned->assigned_by)->toBe('admin-uuid');
    });

    test('cannot reassign completed task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $this->service->complete($task->id, ['approved' => true]);

        expect(fn() => $this->service->assign($task->id, $this->userId2))
            ->toThrow(ValidationException::class);
    });

    test('cannot reassign cancelled task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $this->service->cancel($task->id);

        expect(fn() => $this->service->assign($task->id, $this->userId2))
            ->toThrow(ValidationException::class);
    });
});

describe('UserTaskService - Task Lifecycle', function () {
    test('can start a pending task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $started = $this->service->startTask($task->id);

        expect($started->status)->toBe(UserTask::STATUS_IN_PROGRESS);
    });

    test('cannot start non-pending task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $this->service->startTask($task->id);

        expect(fn() => $this->service->startTask($task->id))
            ->toThrow(ValidationException::class);
    });

    test('can complete task with result', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $result = ['approved' => true, 'comment' => 'Looks good'];
        $completed = $this->service->complete($task->id, $result, $this->userId1);

        expect($completed->status)->toBe(UserTask::STATUS_COMPLETED)
            ->and($completed->result)->toBe($result)
            ->and($completed->completed_by)->toBe($this->userId1)
            ->and($completed->completed_at)->not->toBeNull();
    });

    test('cannot complete already completed task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $this->service->complete($task->id);

        expect(fn() => $this->service->complete($task->id))
            ->toThrow(ValidationException::class);
    });

    test('can cancel pending task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $cancelled = $this->service->cancel($task->id);

        expect($cancelled->status)->toBe(UserTask::STATUS_CANCELLED);
    });

    test('cannot cancel completed task', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $this->service->complete($task->id);

        expect(fn() => $this->service->cancel($task->id))
            ->toThrow(ValidationException::class);
    });
});

describe('UserTaskService - Inbox Queries', function () {
    test('can get user inbox with pending and in-progress tasks', function () {
        // Create various tasks
        $pending = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $inProgress = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->startTask($inProgress->id);

        $completed = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->complete($completed->id);

        // Another user's task
        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId2,
        ]);

        $inbox = $this->service->getInbox($this->userId1);

        expect($inbox)->toHaveCount(2);
    });

    test('can filter inbox by status', function () {
        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $inProgress = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->startTask($inProgress->id);

        $inbox = $this->service->getInbox($this->userId1, UserTask::STATUS_IN_PROGRESS);

        expect($inbox)->toHaveCount(1)
            ->and($inbox->first()->status)->toBe(UserTask::STATUS_IN_PROGRESS);
    });

    test('inbox is ordered by priority then due date', function () {
        $urgent = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'priority' => UserTask::PRIORITY_URGENT,
            'due_at' => now()->addDays(5),
        ]);

        $highDueSoon = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'priority' => UserTask::PRIORITY_HIGH,
            'due_at' => now()->addDays(1),
        ]);

        $highDueLater = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'priority' => UserTask::PRIORITY_HIGH,
            'due_at' => now()->addDays(3),
        ]);

        $inbox = $this->service->getInbox($this->userId1);

        expect($inbox->first()->id)->toBe($urgent->id)
            ->and($inbox->get(1)->id)->toBe($highDueSoon->id)
            ->and($inbox->get(2)->id)->toBe($highDueLater->id);
    });
});

describe('UserTaskService - Pending Tasks', function () {
    test('can get only pending tasks', function () {
        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $task2 = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->startTask($task2->id);

        $pending = $this->service->getPendingTasks($this->userId1);

        expect($pending)->toHaveCount(1)
            ->and($pending->first()->status)->toBe(UserTask::STATUS_PENDING);
    });
});

describe('UserTaskService - Overdue Tasks', function () {
    test('can get overdue tasks', function () {
        $overdue = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'due_at' => now()->subDays(1),
        ]);

        $notDue = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'due_at' => now()->addDays(1),
        ]);

        $overdueTasks = $this->service->getOverdueTasks($this->userId1);

        expect($overdueTasks)->toHaveCount(1)
            ->and($overdueTasks->first()->id)->toBe($overdue->id);
    });

    test('completed tasks are not overdue', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'due_at' => now()->subDays(1),
        ]);

        $this->service->complete($task->id);

        $overdueTasks = $this->service->getOverdueTasks($this->userId1);

        expect($overdueTasks)->toHaveCount(0);
    });
});

describe('UserTaskService - Completed Tasks', function () {
    test('can get completed tasks', function () {
        $task1 = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->complete($task1->id);

        $task2 = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $completed = $this->service->getCompletedTasks($this->userId1);

        expect($completed)->toHaveCount(1)
            ->and($completed->first()->id)->toBe($task1->id);
    });

    test('completed tasks are limited', function () {
        for ($i = 0; $i < 60; $i++) {
            $task = $this->service->create([
                'workflow_instance_id' => $this->instance->id,
                'transition' => 'approve',
                'assigned_to' => $this->userId1,
            ]);
            $this->service->complete($task->id);
        }

        $completed = $this->service->getCompletedTasks($this->userId1);

        expect($completed)->toHaveCount(50);
    });
});

describe('UserTaskService - Task Statistics', function () {
    test('can get task statistics for user', function () {
        // Pending
        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        // In progress
        $task2 = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->startTask($task2->id);

        // Completed today
        $task3 = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->complete($task3->id);

        // Overdue
        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'due_at' => now()->subDays(1),
        ]);

        $stats = $this->service->getTaskStatistics($this->userId1);

        expect($stats['pending'])->toBe(1)
            ->and($stats['in_progress'])->toBe(1)
            ->and($stats['completed_today'])->toBe(1)
            ->and($stats['overdue'])->toBe(1)
            ->and($stats['total_completed'])->toBe(1);
    });
});

describe('UserTaskService - Workflow Tasks', function () {
    test('can get all tasks for workflow instance', function () {
        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId2,
        ]);

        $tasks = $this->service->getTasksForWorkflow($this->instance->id);

        expect($tasks)->toHaveCount(2);
    });

    test('can cancel all workflow tasks', function () {
        $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $task2 = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId2,
        ]);
        $this->service->startTask($task2->id);

        $count = $this->service->cancelWorkflowTasks($this->instance->id);

        expect($count)->toBe(2);

        $tasks = $this->service->getTasksForWorkflow($this->instance->id);
        expect($tasks->every(fn($task) => $task->isCancelled()))->toBeTrue();
    });

    test('cancel workflow tasks does not affect completed tasks', function () {
        $completed = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);
        $this->service->complete($completed->id);

        $pending = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId2,
        ]);

        $count = $this->service->cancelWorkflowTasks($this->instance->id);

        expect($count)->toBe(1)
            ->and($completed->fresh()->isCompleted())->toBeTrue()
            ->and($pending->fresh()->isCancelled())->toBeTrue();
    });
});

describe('UserTaskService - Update Operations', function () {
    test('can update task priority', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
            'priority' => UserTask::PRIORITY_LOW,
        ]);

        $updated = $this->service->updatePriority($task->id, UserTask::PRIORITY_URGENT);

        expect($updated->priority)->toBe(UserTask::PRIORITY_URGENT);
    });

    test('throws error for invalid priority', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        expect(fn() => $this->service->updatePriority($task->id, 'invalid'))
            ->toThrow(ValidationException::class);
    });

    test('can update task due date', function () {
        $task = $this->service->create([
            'workflow_instance_id' => $this->instance->id,
            'transition' => 'approve',
            'assigned_to' => $this->userId1,
        ]);

        $newDueDate = now()->addDays(5);
        $updated = $this->service->updateDueDate($task->id, $newDueDate);

        expect($updated->due_at->equalTo($newDueDate))->toBeTrue();
    });
});
