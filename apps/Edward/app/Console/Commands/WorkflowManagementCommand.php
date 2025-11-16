<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nexus\Workflow\Models\WorkflowDefinition;
use Nexus\Workflow\Models\WorkflowInstance;
use Nexus\Workflow\Models\UserTask;
use Nexus\Workflow\Models\ApproverGroup;
use Nexus\Workflow\Services\WorkflowDefinitionService;
use Nexus\Workflow\Services\UserTaskService;
use Nexus\Workflow\Services\ApproverGroupService;
use Nexus\Workflow\Engines\DatabaseWorkflowEngine;
use Nexus\Erp\Models\User;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\table;

/**
 * Edward Workflow Management Command
 * 
 * Demonstrates Phase 2 workflow capabilities:
 * - Database-driven workflow management
 * - Multi-approver scenarios
 * - User task management
 * - Approval strategies
 */
class WorkflowManagementCommand extends Command
{
    protected $signature = 'edward:workflow {user?}';
    protected $description = 'Workflow Management & Task Inbox - Phase 2 Demo';

    protected ?User $currentUser = null;
    protected WorkflowDefinitionService $workflowService;
    protected UserTaskService $taskService;
    protected ApproverGroupService $approverService;
    protected DatabaseWorkflowEngine $workflowEngine;

    public function __construct()
    {
        parent::__construct();
        
        $this->workflowService = app(WorkflowDefinitionService::class);
        $this->taskService = app(UserTaskService::class);
        $this->approverService = app(ApproverGroupService::class);
        $this->workflowEngine = app(DatabaseWorkflowEngine::class);
    }

    public function handle(): int
    {
        // Get or select user
        $userId = $this->argument('user');
        
        if ($userId) {
            $this->currentUser = User::find($userId);
        } else {
            $this->currentUser = $this->selectUser();
        }

        if (!$this->currentUser) {
            $this->error('No user selected. Exiting.');
            return self::FAILURE;
        }

        $this->displayBanner();

        while (true) {
            $choice = $this->displayMainMenu();

            if ($choice === '0') {
                $this->newLine();
                info('Returning to Edward main menu...');
                return self::SUCCESS;
            }

            $this->handleMenuChoice($choice);
        }
    }

    protected function selectUser(): ?User
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->error('No users found. Please create users first.');
            return null;
        }

        $options = $users->mapWithKeys(function ($user) {
            return [$user->id => "{$user->name} ({$user->email})"];
        })->toArray();

        $userId = select(
            label: 'Select User',
            options: $options,
            hint: 'Choose a user to view tasks and workflows'
        );

        return User::find($userId);
    }

    protected function displayBanner(): void
    {
        $this->newLine(2);
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                                                                ║');
        $this->line('║        🔄  WORKFLOW MANAGEMENT & TASK INBOX  🔄               ║');
        $this->line('║                                                                ║');
        $this->line('║              Phase 2: Database-Driven Workflows                ║');
        $this->line('║              Multi-Approver Support & Task Management          ║');
        $this->line('║                                                                ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        info("Logged in as: {$this->currentUser->name}");
        $this->newLine();
    }

    protected function displayMainMenu(): string
    {
        // Get task counts
        $pendingTasks = UserTask::where('assigned_to', $this->currentUser->id)
            ->where('status', 'pending')
            ->count();

        $activeWorkflows = WorkflowInstance::whereHas('definition', function ($query) {
            $query->where('is_active', true);
        })->count();

        return select(
            label: '═══ WORKFLOW MANAGEMENT MENU ═══',
            options: [
                '1' => "📬 My Task Inbox ({$pendingTasks} pending)",
                '2' => "🔄 Active Workflows ({$activeWorkflows} running)",
                '3' => '📋 Workflow Definitions',
                '4' => '👥 Approver Groups',
                '5' => '🎭 Approval Strategy Demos',
                '6' => '🧪 Test Scenarios',
                '0' => '← Back to Main Menu',
            ],
            default: '1',
            hint: 'Use arrow keys to navigate, Enter to select'
        );
    }

    protected function handleMenuChoice(string $choice): void
    {
        match ($choice) {
            '1' => $this->showTaskInbox(),
            '2' => $this->showActiveWorkflows(),
            '3' => $this->manageWorkflowDefinitions(),
            '4' => $this->manageApproverGroups(),
            '5' => $this->showApprovalStrategyDemos(),
            '6' => $this->runTestScenarios(),
            default => warning('Invalid choice'),
        };
    }

    // ==================== TASK INBOX ====================

    protected function showTaskInbox(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                      📬 MY TASK INBOX 📬                       ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $tasks = UserTask::with(['workflowInstance.definition'])
            ->where('assigned_to', $this->currentUser->id)
            ->where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('due_at')
            ->get();

        if ($tasks->isEmpty()) {
            info('No pending tasks. Inbox is empty! ✨');
            $this->newLine();
            $this->line('Press Enter to continue...');
            $this->ask('');
            return;
        }

        // Display tasks table
        $tableData = $tasks->map(function ($task) {
            return [
                'ID' => substr($task->id, 0, 8) . '...',
                'Title' => $task->title,
                'Workflow' => $task->workflowInstance->definition->name ?? 'N/A',
                'Priority' => $this->getPriorityLabel($task->priority),
                'Due' => $task->due_at ? $task->due_at->diffForHumans() : 'No deadline',
            ];
        })->toArray();

        table(
            headers: ['ID', 'Title', 'Workflow', 'Priority', 'Due'],
            rows: $tableData
        );

        $this->newLine();

        // Task actions
        $action = select(
            label: 'What would you like to do?',
            options: [
                'view' => '👁️  View task details',
                'complete' => '✅ Complete a task',
                'back' => '← Back to menu',
            ],
            default: 'view'
        );

        if ($action === 'back') {
            return;
        }

        // Select task
        $taskOptions = $tasks->mapWithKeys(function ($task) {
            return [(string)$task->id => "{$task->title} ({$this->getPriorityLabel($task->priority)})"];
        })->toArray();

        $taskId = select(
            label: 'Select Task',
            options: $taskOptions
        );

        $task = $tasks->firstWhere('id', $taskId);

        if ($action === 'view') {
            $this->showTaskDetails($task);
        } elseif ($action === 'complete') {
            $this->completeTask($task);
        }

        $this->showTaskInbox(); // Refresh inbox
    }

    protected function showTaskDetails(UserTask $task): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                       📋 TASK DETAILS 📋                       ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $workflow = $task->workflowInstance->definition;
        $instance = $task->workflowInstance;

        $details = [
            ['Property', 'Value'],
            ['Task ID', substr($task->id, 0, 8) . '...'],
            ['Title', $task->title],
            ['Description', $task->description ?? 'N/A'],
            ['Status', strtoupper($task->status)],
            ['Priority', $this->getPriorityLabel($task->priority)],
            ['Workflow', $workflow->name],
            ['Current State', $instance->current_state],
            ['Due Date', $task->due_at ? $task->due_at->format('Y-m-d H:i') : 'No deadline'],
            ['Created', $task->created_at->format('Y-m-d H:i')],
        ];

        table(
            headers: ['Property', 'Value'],
            rows: array_slice($details, 1)
        );

        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    protected function completeTask(UserTask $task): void
    {
        $this->newLine();
        info("Completing task: {$task->title}");
        $this->newLine();

        // Get completion notes
        $notes = text(
            label: 'Completion Notes (optional)',
            placeholder: 'Enter any notes or comments...',
            default: ''
        );

        $outcome = text(
            label: 'Task Outcome',
            placeholder: 'approved, rejected, completed, etc.',
            default: 'completed',
            required: true
        );

        try {
            $this->taskService->completeTask($task->id, [
                'notes' => $notes,
                'outcome' => $outcome,
            ]);

            $this->newLine();
            info('✅ Task completed successfully!');
            $this->newLine();
        } catch (\Exception $e) {
            error("Failed to complete task: {$e->getMessage()}");
            $this->newLine();
        }

        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    protected function getPriorityLabel(int $priority): string
    {
        return match (true) {
            $priority >= 20 => '🔴 URGENT',
            $priority >= 10 => '🟡 HIGH',
            $priority >= 5 => '🟢 NORMAL',
            default => '⚪ LOW',
        };
    }

    // ==================== ACTIVE WORKFLOWS ====================

    protected function showActiveWorkflows(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                   🔄 ACTIVE WORKFLOWS 🔄                       ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $instances = WorkflowInstance::with('definition')
            ->whereHas('definition', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        if ($instances->isEmpty()) {
            info('No active workflow instances found.');
            $this->newLine();
            $this->line('Press Enter to continue...');
            $this->ask('');
            return;
        }

        $tableData = $instances->map(function ($instance) {
            return [
                'ID' => substr($instance->id, 0, 8) . '...',
                'Workflow' => $instance->definition->name,
                'Current State' => $instance->current_state,
                'Entity Type' => class_basename($instance->entity_type),
                'Created' => $instance->created_at->diffForHumans(),
            ];
        })->toArray();

        table(
            headers: ['ID', 'Workflow', 'Current State', 'Entity Type', 'Created'],
            rows: $tableData
        );

        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    // ==================== WORKFLOW DEFINITIONS ====================

    protected function manageWorkflowDefinitions(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                  📋 WORKFLOW DEFINITIONS 📋                    ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $workflows = WorkflowDefinition::orderBy('code')->get();

        if ($workflows->isEmpty()) {
            warning('No workflow definitions found.');
            $this->newLine();
            
            if (confirm('Would you like to import sample workflows?', true)) {
                $this->importSampleWorkflows();
                $this->manageWorkflowDefinitions();
                return;
            }
            
            $this->line('Press Enter to continue...');
            $this->ask('');
            return;
        }

        $tableData = $workflows->map(function ($workflow) {
            return [
                'Code' => $workflow->code,
                'Name' => $workflow->name,
                'Version' => 'v' . $workflow->version,
                'Active' => $workflow->is_active ? '✓' : '✗',
                'Created' => $workflow->created_at->format('Y-m-d'),
            ];
        })->toArray();

        table(
            headers: ['Code', 'Name', 'Version', 'Active', 'Created'],
            rows: $tableData
        );

        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    protected function importSampleWorkflows(): void
    {
        $this->newLine();
        info('Importing sample workflow definitions...');
        $this->newLine();

        // Purchase Order Approval Workflow
        $poWorkflow = [
            'code' => 'purchase-order-approval',
            'name' => 'Purchase Order Approval',
            'version' => 1,
            'is_active' => true,
            'definition' => [
                'states' => [
                    ['name' => 'draft', 'label' => 'Draft', 'type' => 'initial'],
                    ['name' => 'pending_approval', 'label' => 'Pending Approval', 'type' => 'regular'],
                    ['name' => 'approved', 'label' => 'Approved', 'type' => 'regular'],
                    ['name' => 'rejected', 'label' => 'Rejected', 'type' => 'final'],
                    ['name' => 'completed', 'label' => 'Completed', 'type' => 'final'],
                ],
                'transitions' => [
                    [
                        'name' => 'submit',
                        'from' => 'draft',
                        'to' => 'pending_approval',
                        'label' => 'Submit for Approval',
                    ],
                    [
                        'name' => 'approve',
                        'from' => 'pending_approval',
                        'to' => 'approved',
                        'label' => 'Approve',
                    ],
                    [
                        'name' => 'reject',
                        'from' => 'pending_approval',
                        'to' => 'rejected',
                        'label' => 'Reject',
                    ],
                    [
                        'name' => 'complete',
                        'from' => 'approved',
                        'to' => 'completed',
                        'label' => 'Complete',
                    ],
                ],
            ],
        ];

        try {
            $this->workflowService->create($poWorkflow);
            info('✅ Purchase Order Approval workflow imported');
        } catch (\Exception $e) {
            error("Failed to import PO workflow: {$e->getMessage()}");
        }

        // Invoice Approval Workflow
        $invoiceWorkflow = [
            'code' => 'invoice-approval',
            'name' => 'Invoice Approval',
            'version' => 1,
            'is_active' => true,
            'definition' => [
                'states' => [
                    ['name' => 'draft', 'label' => 'Draft', 'type' => 'initial'],
                    ['name' => 'pending_review', 'label' => 'Pending Review', 'type' => 'regular'],
                    ['name' => 'approved', 'label' => 'Approved', 'type' => 'regular'],
                    ['name' => 'paid', 'label' => 'Paid', 'type' => 'final'],
                    ['name' => 'cancelled', 'label' => 'Cancelled', 'type' => 'final'],
                ],
                'transitions' => [
                    [
                        'name' => 'submit',
                        'from' => 'draft',
                        'to' => 'pending_review',
                        'label' => 'Submit for Review',
                    ],
                    [
                        'name' => 'approve',
                        'from' => 'pending_review',
                        'to' => 'approved',
                        'label' => 'Approve',
                    ],
                    [
                        'name' => 'pay',
                        'from' => 'approved',
                        'to' => 'paid',
                        'label' => 'Mark as Paid',
                    ],
                    [
                        'name' => 'cancel',
                        'from' => 'draft',
                        'to' => 'cancelled',
                        'label' => 'Cancel',
                    ],
                    [
                        'name' => 'cancel',
                        'from' => 'pending_review',
                        'to' => 'cancelled',
                        'label' => 'Cancel',
                    ],
                ],
            ],
        ];

        try {
            $this->workflowService->create($invoiceWorkflow);
            info('✅ Invoice Approval workflow imported');
        } catch (\Exception $e) {
            error("Failed to import Invoice workflow: {$e->getMessage()}");
        }

        $this->newLine();
        info('Sample workflows imported successfully!');
        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    // ==================== APPROVER GROUPS ====================

    protected function manageApproverGroups(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                    👥 APPROVER GROUPS 👥                       ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $groups = ApproverGroup::with('members')->get();

        if ($groups->isEmpty()) {
            warning('No approver groups found.');
            $this->newLine();
            
            if (confirm('Would you like to create sample approver groups?', true)) {
                $this->createSampleApproverGroups();
                $this->manageApproverGroups();
                return;
            }
            
            $this->line('Press Enter to continue...');
            $this->ask('');
            return;
        }

        $tableData = $groups->map(function ($group) {
            return [
                'Name' => $group->name,
                'Strategy' => ucfirst(str_replace('_', ' ', $group->strategy)),
                'Members' => $group->members->count(),
                'Active' => $group->is_active ? '✓' : '✗',
            ];
        })->toArray();

        table(
            headers: ['Name', 'Strategy', 'Members', 'Active'],
            rows: $tableData
        );

        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    protected function createSampleApproverGroups(): void
    {
        $this->newLine();
        info('Creating sample approver groups...');
        $this->newLine();

        $users = User::limit(5)->get();

        if ($users->count() < 3) {
            error('Need at least 3 users to create sample approver groups.');
            return;
        }

        // Sequential Approval Group
        try {
            $sequentialGroup = $this->approverService->create([
                'name' => 'Finance Sequential Approval',
                'strategy' => 'sequential',
                'is_active' => true,
            ]);

            foreach ($users->take(3) as $index => $user) {
                $this->approverService->addMember($sequentialGroup->id, $user->id, [
                    'sequence' => $index + 1,
                ]);
            }

            info('✅ Sequential approval group created');
        } catch (\Exception $e) {
            error("Failed to create sequential group: {$e->getMessage()}");
        }

        // Parallel Approval Group
        try {
            $parallelGroup = $this->approverService->create([
                'name' => 'Executive Parallel Approval',
                'strategy' => 'parallel',
                'is_active' => true,
            ]);

            foreach ($users->take(3) as $user) {
                $this->approverService->addMember($parallelGroup->id, $user->id);
            }

            info('✅ Parallel approval group created');
        } catch (\Exception $e) {
            error("Failed to create parallel group: {$e->getMessage()}");
        }

        $this->newLine();
        info('Sample approver groups created successfully!');
        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    // ==================== APPROVAL STRATEGY DEMOS ====================

    protected function showApprovalStrategyDemos(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                🎭 APPROVAL STRATEGY DEMOS 🎭                   ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $strategy = select(
            label: 'Select Strategy to Demo',
            options: [
                'sequential' => '1️⃣  Sequential (Approvers in order)',
                'parallel' => '⚡ Parallel (All must approve)',
                'quorum' => '🎯 Quorum (N of M required)',
                'any' => '✨ Any (First approval wins)',
                'weighted' => '⚖️  Weighted (Sum of weights)',
                'back' => '← Back to menu',
            ],
            hint: 'Each strategy demonstrates different approval patterns'
        );

        if ($strategy === 'back') {
            return;
        }

        $this->demoApprovalStrategy($strategy);
    }

    protected function demoApprovalStrategy(string $strategy): void
    {
        $this->newLine();
        
        $descriptions = [
            'sequential' => 'Approvers must approve in sequence (1→2→3). Next approver only sees task after previous approval.',
            'parallel' => 'All approvers must approve (unanimous). Order does not matter.',
            'quorum' => 'Requires N of M approvals (e.g., 3 of 5). Majority or supermajority rules.',
            'any' => 'First approval wins. Fast-track for trusted approvers.',
            'weighted' => 'Sum of approver weights must reach threshold. CEO(100) OR CFO(50) + Manager(25).',
        ];

        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║  ' . strtoupper($strategy) . ' STRATEGY DEMO');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        info($descriptions[$strategy]);
        $this->newLine();

        // Demo implementation would go here
        // For now, just show the concept
        
        warning('Full interactive demo coming in next phase...');
        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    // ==================== TEST SCENARIOS ====================

    protected function runTestScenarios(): void
    {
        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                   🧪 TEST SCENARIOS 🧪                         ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $scenario = select(
            label: 'Select Test Scenario',
            options: [
                'full' => '🎬 Full Approval Workflow (PO → Approval → Complete)',
                'multi' => '👥 Multi-Approver Test (All 5 strategies)',
                'task' => '📋 Task Management Test (Create → Assign → Complete)',
                'back' => '← Back to menu',
            ],
            hint: 'Run end-to-end scenarios to test Phase 2 features'
        );

        if ($scenario === 'back') {
            return;
        }

        match ($scenario) {
            'full' => $this->runFullApprovalTest(),
            'multi' => $this->runMultiApproverTest(),
            'task' => $this->runTaskManagementTest(),
        };
    }

    protected function runFullApprovalTest(): void
    {
        $this->newLine();
        info('Running Full Approval Workflow Test...');
        $this->newLine();
        
        warning('Test scenario implementation coming soon...');
        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    protected function runMultiApproverTest(): void
    {
        $this->newLine();
        info('Running Multi-Approver Strategy Test...');
        $this->newLine();
        
        warning('Test scenario implementation coming soon...');
        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }

    protected function runTaskManagementTest(): void
    {
        $this->newLine();
        info('Running Task Management Test...');
        $this->newLine();
        
        warning('Test scenario implementation coming soon...');
        $this->newLine();
        $this->line('Press Enter to continue...');
        $this->ask('');
    }
}
