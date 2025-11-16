<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Nexus\Workflow\Models\WorkflowDefinition;
use Nexus\Workflow\Models\WorkflowInstance;
use Nexus\Workflow\Services\UserTaskService;

/**
 * Test User Task Management in Edward CLI
 * 
 * Demonstrates task inbox, assignment, and completion workflows
 * in a full Laravel application context.
 */
class TestUserTaskCommand extends Command
{
    protected $signature = 'test:user-tasks {--clean : Clean up test data after execution}';
    protected $description = 'Test User Task Management in Edward app';

    private UserTaskService $taskService;
    private array $testData = [];

    public function __construct(UserTaskService $taskService)
    {
        parent::__construct();
        $this->taskService = $taskService;
    }

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('   Testing User Task Management (Phase 2 Checkpoint 4)');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        try {
            $this->setupTestData();
            
            $this->runTest('Task Creation', fn() => $this->testTaskCreation());
            $this->runTest('Bulk Task Creation', fn() => $this->testBulkCreation());
            $this->runTest('Task Assignment', fn() => $this->testTaskAssignment());
            $this->runTest('Task Lifecycle', fn() => $this->testTaskLifecycle());
            $this->runTest('User Inbox Queries', fn() => $this->testInboxQueries());
            $this->runTest('Task Statistics', fn() => $this->testTaskStatistics());
            $this->runTest('Priority Management', fn() => $this->testPriorityManagement());
            $this->runTest('Overdue Detection', fn() => $this->testOverdueDetection());
            $this->runTest('Workflow Task Cancellation', fn() => $this->testWorkflowCancellation());

            $this->newLine();
            $this->info('✅ All User Task Management tests passed!');
            
            if ($this->option('clean')) {
                $this->cleanupTestData();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function setupTestData(): void
    {
        $this->info('Setting up test data...');

        // Clean up any previous test data
        WorkflowDefinition::where('key', 'po-approval-test')->forceDelete();

        // Create test workflow definition
        $this->testData['definition'] = WorkflowDefinition::create([
            'name' => 'Purchase Order Approval',
            'key' => 'po-approval-test',
            'version' => 1,
            'definition' => [
                'states' => ['draft', 'pending_approval', 'approved', 'rejected'],
                'transitions' => [
                    ['name' => 'submit', 'from' => 'draft', 'to' => 'pending_approval'],
                    ['name' => 'approve', 'from' => 'pending_approval', 'to' => 'approved'],
                    ['name' => 'reject', 'from' => 'pending_approval', 'to' => 'rejected'],
                ],
                'initial_state' => 'draft',
            ],
            'is_active' => true,
        ]);

        // Create test workflow instances
        $this->testData['instance1'] = WorkflowInstance::create([
            'workflow_definition_id' => $this->testData['definition']->id,
            'subject_type' => 'App\\Models\\PurchaseOrder',
            'subject_id' => Str::uuid(),
            'current_state' => 'pending_approval',
            'data' => ['po_number' => 'PO-2025-001', 'amount' => 50000],
            'started_at' => now(),
        ]);

        $this->testData['instance2'] = WorkflowInstance::create([
            'workflow_definition_id' => $this->testData['definition']->id,
            'subject_type' => 'App\\Models\\PurchaseOrder',
            'subject_id' => Str::uuid(),
            'current_state' => 'pending_approval',
            'data' => ['po_number' => 'PO-2025-002', 'amount' => 25000],
            'started_at' => now(),
        ]);

        // Create test user IDs
        $this->testData['manager1'] = Str::uuid();
        $this->testData['manager2'] = Str::uuid();
        $this->testData['cfo'] = Str::uuid();

        $this->line('  ✓ Created workflow definition and instances');
    }

    private function testTaskCreation(): void
    {
        $task = $this->taskService->create([
            'workflow_instance_id' => $this->testData['instance1']->id,
            'transition' => 'approve',
            'assigned_to' => $this->testData['manager1'],
            'title' => 'Review PO-2025-001 (Amount: $50,000)',
            'description' => 'Please review and approve this purchase order',
            'priority' => 'high',
            'due_at' => now()->addDays(2),
        ]);

        $this->testData['task1'] = $task;

        $this->assertTrue($task->isPending(), 'Task should be pending');
        $this->assertEquals('high', $task->priority, 'Task priority should be high');
        $this->line('  ✓ Task created successfully with correct attributes');
    }

    private function testBulkCreation(): void
    {
        $approvers = [
            $this->testData['manager1'],
            $this->testData['manager2'],
            $this->testData['cfo'],
        ];

        $tasks = $this->taskService->createBulk(
            $this->testData['instance2']->id,
            'approve',
            $approvers,
            [
                'title' => 'Review PO-2025-002 (Amount: $25,000)',
                'priority' => 'medium',
                'due_at' => now()->addDays(3),
            ]
        );

        $this->testData['bulkTasks'] = $tasks;

        $this->assertCount(3, $tasks, 'Should create 3 tasks');
        $this->assertEquals($approvers, $tasks->pluck('assigned_to')->toArray(), 'Tasks assigned to correct users');
        $this->line('  ✓ Bulk task creation successful (3 approvers)');
    }

    private function testTaskAssignment(): void
    {
        $originalTask = $this->testData['task1'];
        $originalAssignee = $originalTask->assigned_to;

        $reassigned = $this->taskService->assign(
            (string) $originalTask->id,
            (string) $this->testData['cfo'],
            (string) $this->testData['manager2']
        );

        $this->assertEquals($this->testData['cfo'], $reassigned->assigned_to, 'Task reassigned to CFO');
        $this->assertEquals($this->testData['manager2'], $reassigned->assigned_by, 'Assignment tracked');
        $this->line('  ✓ Task reassignment successful with audit trail');
    }

    private function testTaskLifecycle(): void
    {
        $task = $this->testData['task1'];

        // Start task
        $started = $this->taskService->startTask((string) $task->id);
        $this->assertTrue($started->isInProgress(), 'Task should be in progress');
        $this->line('  ✓ Task marked as in progress');

        // Complete task
        $result = [
            'decision' => 'approved',
            'comment' => 'Approved - within budget limits',
            'approval_date' => now()->toIso8601String(),
        ];

        $completed = $this->taskService->complete((string) $started->id, $result, (string) $this->testData['cfo']);

        $this->assertTrue($completed->isCompleted(), 'Task should be completed');
        // Sort arrays for comparison (JSONB may reorder keys)
        ksort($result);
        $actualResult = $completed->result;
        ksort($actualResult);
        $this->assertEquals($result, $actualResult, 'Result data stored correctly');
        $this->assertEquals($this->testData['cfo'], $completed->completed_by, 'Completion tracked');
        $this->assertNotNull($completed->completed_at, 'Completion timestamp set');
        $this->line('  ✓ Task completed with result data');
    }

    private function testInboxQueries(): void
    {
        // Create more tasks for inbox testing
        $this->taskService->create([
            'workflow_instance_id' => (string) $this->testData['instance1']->id,
            'transition' => 'approve',
            'assigned_to' => (string) $this->testData['manager1'],
            'title' => 'Another pending task',
            'priority' => 'urgent',
        ]);

        $inbox = $this->taskService->getInbox((string) $this->testData['manager1']);
        $this->assertGreaterThan(0, $inbox->count(), 'Manager should have tasks in inbox');
        $this->line("  ✓ Inbox query returned {$inbox->count()} task(s)");

        $pending = $this->taskService->getPendingTasks((string) $this->testData['manager1']);
        $this->assertGreaterThan(0, $pending->count(), 'Manager should have pending tasks');
        $this->line("  ✓ Pending tasks query returned {$pending->count()} task(s)");

        // Verify inbox is ordered by priority
        $firstTask = $inbox->first();
        $this->assertEquals('urgent', $firstTask->priority, 'Inbox should prioritize urgent tasks');
        $this->line('  ✓ Inbox correctly ordered by priority');
    }

    private function testTaskStatistics(): void
    {
        $stats = $this->taskService->getTaskStatistics((string) $this->testData['manager1']);

        $this->assertIsArray($stats, 'Statistics should be an array');
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('in_progress', $stats);
        $this->assertArrayHasKey('completed_today', $stats);
        $this->assertArrayHasKey('overdue', $stats);
        $this->assertArrayHasKey('total_completed', $stats);

        $this->line('  ✓ Task statistics generated:');
        $this->line("    - Pending: {$stats['pending']}");
        $this->line("    - In Progress: {$stats['in_progress']}");
        $this->line("    - Completed Today: {$stats['completed_today']}");
        $this->line("    - Overdue: {$stats['overdue']}");
        $this->line("    - Total Completed: {$stats['total_completed']}");
    }

    private function testPriorityManagement(): void
    {
        $task = $this->testData['bulkTasks']->first();
        $originalPriority = $task->priority;

        $updated = $this->taskService->updatePriority((string) $task->id, 'urgent');

        $this->assertEquals('urgent', $updated->priority, 'Priority updated to urgent');
        $this->assertNotEquals($originalPriority, $updated->priority, 'Priority changed');
        $this->line('  ✓ Task priority updated successfully');
    }

    private function testOverdueDetection(): void
    {
        // Create an overdue task
        $overdueTask = $this->taskService->create([
            'workflow_instance_id' => (string) $this->testData['instance1']->id,
            'transition' => 'approve',
            'assigned_to' => (string) $this->testData['manager1'],
            'title' => 'Overdue approval',
            'due_at' => now()->subDays(2),
        ]);

        $this->assertTrue($overdueTask->isOverdue(), 'Task should be overdue');
        $this->line('  ✓ Overdue task created');

        $overdueTasks = $this->taskService->getOverdueTasks((string) $this->testData['manager1']);
        $this->assertGreaterThan(0, $overdueTasks->count(), 'Manager should have overdue tasks');
        $this->line("  ✓ Overdue query returned {$overdueTasks->count()} task(s)");
    }

    private function testWorkflowCancellation(): void
    {
        $instance = $this->testData['instance2'];
        $tasksBeforeCount = $this->taskService->getTasksForWorkflow((string) $instance->id)->count();

        $this->assertGreaterThan(0, $tasksBeforeCount, 'Workflow should have tasks');
        $this->line("  ✓ Workflow has {$tasksBeforeCount} task(s)");

        $cancelledCount = $this->taskService->cancelWorkflowTasks((string) $instance->id);

        $this->assertEquals(3, $cancelledCount, 'Should cancel 3 tasks (bulk created tasks)');
        $this->line("  ✓ Cancelled {$cancelledCount} task(s) for workflow");

        $tasksAfter = $this->taskService->getTasksForWorkflow((string) $instance->id);
        $allCancelled = $tasksAfter->filter(fn($t) => !$t->isCompleted())->every(fn($t) => $t->isCancelled());
        $this->assertTrue($allCancelled, 'All non-completed tasks should be cancelled');
        $this->line('  ✓ Verified all tasks cancelled');
    }

    private function cleanupTestData(): void
    {
        $this->newLine();
        $this->info('Cleaning up test data...');

        if (isset($this->testData['definition'])) {
            $this->testData['definition']->delete();
        }

        $this->line('  ✓ Test data cleaned up');
    }

    private function runTest(string $name, callable $test): void
    {
        $this->info("Testing: {$name}");
        $test();
        $this->newLine();
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException("Assertion failed: {$message}");
        }
    }

    private function assertEquals($expected, $actual, string $message): void
    {
        // Convert UUIDs to strings for comparison
        $expectedStr = is_object($expected) && method_exists($expected, '__toString') 
            ? (string) $expected 
            : $expected;
        $actualStr = is_object($actual) && method_exists($actual, '__toString') 
            ? (string) $actual 
            : $actual;
            
        if ($expectedStr !== $actualStr) {
            throw new \RuntimeException(
                "Assertion failed: {$message}. Expected: " . json_encode($expectedStr) . 
                ", Got: " . json_encode($actualStr)
            );
        }
    }

    private function assertCount(int $expected, $collection, string $message): void
    {
        $actual = is_array($collection) ? count($collection) : $collection->count();
        if ($expected !== $actual) {
            throw new \RuntimeException(
                "Assertion failed: {$message}. Expected: {$expected}, Got: {$actual}"
            );
        }
    }

    private function assertGreaterThan(int $threshold, $value, string $message): void
    {
        $actual = is_numeric($value) ? $value : $value->count();
        if ($actual <= $threshold) {
            throw new \RuntimeException(
                "Assertion failed: {$message}. Expected > {$threshold}, Got: {$actual}"
            );
        }
    }

    private function assertIsArray($value, string $message): void
    {
        if (!is_array($value)) {
            throw new \RuntimeException("Assertion failed: {$message}");
        }
    }

    private function assertArrayHasKey($key, array $array): void
    {
        if (!array_key_exists($key, $array)) {
            throw new \RuntimeException("Assertion failed: Array should have key '{$key}'");
        }
    }

    private function assertNotEquals($expected, $actual, string $message): void
    {
        if ($expected === $actual) {
            throw new \RuntimeException("Assertion failed: {$message}");
        }
    }

    private function assertNotNull($value, string $message): void
    {
        if ($value === null) {
            throw new \RuntimeException("Assertion failed: {$message}");
        }
    }
}
