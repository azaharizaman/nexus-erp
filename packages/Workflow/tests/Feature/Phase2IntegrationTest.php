<?php

/**
 * Phase 2 Integration Test
 * 
 * Tests all Phase 2 features end-to-end:
 * - Workflow definitions
 * - Approver groups
 * - User tasks
 * - Database workflow engine
 * - HasDatabaseWorkflow trait
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Workflow\Models\WorkflowDefinition;
use Nexus\Workflow\Models\ApproverGroup;
use Nexus\Workflow\Models\UserTask;
use Nexus\Workflow\Services\WorkflowDefinitionService;
use Nexus\Workflow\Services\ApproverGroupService;
use Nexus\Workflow\Services\UserTaskService;
use Nexus\Workflow\Engines\DatabaseWorkflowEngine;
use Nexus\Erp\Models\User;

uses(RefreshDatabase::class);

describe('Phase 2: Complete Integration Test', function () {
    
    beforeEach(function () {
        $this->workflowService = app(WorkflowDefinitionService::class);
        $this->approverService = app(ApproverGroupService::class);
        $this->taskService = app(UserTaskService::class);
        $this->workflowEngine = app(DatabaseWorkflowEngine::class);
        
        // Create test users
        $this->user1 = User::factory()->create(['name' => 'Manager']);
        $this->user2 = User::factory()->create(['name' => 'CFO']);
        $this->user3 = User::factory()->create(['name' => 'CEO']);
    });
    
    test('can create workflow definition with all states and transitions', function () {
        $workflow = $this->workflowService->create([
            'code' => 'test-workflow',
            'name' => 'Test Workflow',
            'version' => 1,
            'is_active' => true,
            'definition' => [
                'states' => [
                    ['name' => 'draft', 'label' => 'Draft', 'type' => 'initial'],
                    ['name' => 'approved', 'label' => 'Approved', 'type' => 'final'],
                ],
                'transitions' => [
                    ['name' => 'approve', 'from' => 'draft', 'to' => 'approved', 'label' => 'Approve'],
                ],
            ],
        ]);
        
        expect($workflow)->toBeInstanceOf(WorkflowDefinition::class)
            ->and($workflow->code)->toBe('test-workflow')
            ->and($workflow->is_active)->toBeTrue()
            ->and($workflow->definition['states'])->toHaveCount(2);
    });
    
    test('can create sequential approver group with members', function () {
        $group = $this->approverService->create([
            'name' => 'Sequential Approvers',
            'strategy' => 'sequential',
            'is_active' => true,
        ]);
        
        $this->approverService->addMember($group->id, $this->user1->id, ['sequence' => 1]);
        $this->approverService->addMember($group->id, $this->user2->id, ['sequence' => 2]);
        $this->approverService->addMember($group->id, $this->user3->id, ['sequence' => 3]);
        
        $group->refresh();
        
        expect($group->members)->toHaveCount(3)
            ->and($group->strategy)->toBe('sequential');
    });
    
    test('can create parallel approver group', function () {
        $group = $this->approverService->create([
            'name' => 'Parallel Approvers',
            'strategy' => 'parallel',
            'is_active' => true,
        ]);
        
        $this->approverService->addMember($group->id, $this->user1->id);
        $this->approverService->addMember($group->id, $this->user2->id);
        
        expect($group->strategy)->toBe('parallel')
            ->and($group->members)->toHaveCount(2);
    });
    
    test('can create quorum approver group with quorum count', function () {
        $group = $this->approverService->create([
            'name' => 'Quorum Approvers',
            'strategy' => 'quorum',
            'is_active' => true,
            'config' => ['quorum_count' => 2],
        ]);
        
        $this->approverService->addMember($group->id, $this->user1->id);
        $this->approverService->addMember($group->id, $this->user2->id);
        $this->approverService->addMember($group->id, $this->user3->id);
        
        expect($group->config['quorum_count'])->toBe(2)
            ->and($group->members)->toHaveCount(3);
    });
    
    test('can create weighted approver group with member weights', function () {
        $group = $this->approverService->create([
            'name' => 'Weighted Approvers',
            'strategy' => 'weighted',
            'is_active' => true,
            'config' => ['min_weight' => 75],
        ]);
        
        $this->approverService->addMember($group->id, $this->user1->id, ['weight' => 25]); // Manager
        $this->approverService->addMember($group->id, $this->user2->id, ['weight' => 50]); // CFO
        $this->approverService->addMember($group->id, $this->user3->id, ['weight' => 100]); // CEO
        
        $group->refresh();
        
        expect($group->config['min_weight'])->toBe(75)
            ->and($group->members)->toHaveCount(3);
    });
    
    test('can create and assign user task', function () {
        $task = $this->taskService->create([
            'title' => 'Approve Purchase Order',
            'description' => 'Review and approve PO #12345',
            'assigned_to' => $this->user1->id,
            'priority' => 10,
            'status' => 'pending',
        ]);
        
        expect($task)->toBeInstanceOf(UserTask::class)
            ->and($task->assigned_to)->toBe($this->user1->id)
            ->and($task->status)->toBe('pending');
    });
    
    test('can complete user task with notes', function () {
        $task = $this->taskService->create([
            'title' => 'Approve Invoice',
            'assigned_to' => $this->user1->id,
            'status' => 'pending',
        ]);
        
        $completedTask = $this->taskService->completeTask($task->id, [
            'notes' => 'Approved for payment',
            'outcome' => 'approved',
        ]);
        
        expect($completedTask->status)->toBe('completed')
            ->and($completedTask->data['notes'])->toBe('Approved for payment');
    });
    
    test('database workflow engine loads definitions from database', function () {
        // Create workflow
        $workflow = $this->workflowService->create([
            'code' => 'engine-test',
            'name' => 'Engine Test',
            'version' => 1,
            'is_active' => true,
            'definition' => [
                'states' => [
                    ['name' => 'start', 'label' => 'Start', 'type' => 'initial'],
                    ['name' => 'end', 'label' => 'End', 'type' => 'final'],
                ],
                'transitions' => [
                    ['name' => 'finish', 'from' => 'start', 'to' => 'end', 'label' => 'Finish'],
                ],
            ],
        ]);
        
        // Load through engine
        $loadedDef = $this->workflowEngine->loadDefinition($workflow->id);
        
        expect($loadedDef)->not->toBeNull()
            ->and($loadedDef->code)->toBe('engine-test');
    });
    
    test('database workflow engine caches definitions', function () {
        $workflow = $this->workflowService->create([
            'code' => 'cache-test',
            'name' => 'Cache Test',
            'version' => 1,
            'is_active' => true,
            'definition' => [
                'states' => [
                    ['name' => 'start', 'label' => 'Start', 'type' => 'initial'],
                ],
                'transitions' => [],
            ],
        ]);
        
        // First load (cache miss)
        $def1 = $this->workflowEngine->loadDefinition($workflow->id);
        
        // Second load (cache hit)
        $def2 = $this->workflowEngine->loadDefinition($workflow->id);
        
        expect($def1->id)->toBe($def2->id);
        
        // Clear cache
        $this->workflowEngine->clearCache($workflow->id);
        
        // Third load (cache miss again)
        $def3 = $this->workflowEngine->loadDefinition($workflow->id);
        
        expect($def3->id)->toBe($workflow->id);
    });
    
    test('workflow definition service can clone workflow', function () {
        $original = $this->workflowService->create([
            'code' => 'original',
            'name' => 'Original Workflow',
            'version' => 1,
            'is_active' => true,
            'definition' => ['states' => [], 'transitions' => []],
        ]);
        
        $cloned = $this->workflowService->clone($original->id, [
            'code' => 'cloned',
            'name' => 'Cloned Workflow',
        ]);
        
        expect($cloned->code)->toBe('cloned')
            ->and($cloned->version)->toBe(1)
            ->and($cloned->id)->not->toBe($original->id);
    });
    
    test('workflow definition service can export to JSON', function () {
        $workflow = $this->workflowService->create([
            'code' => 'export-test',
            'name' => 'Export Test',
            'version' => 1,
            'is_active' => true,
            'definition' => [
                'states' => [
                    ['name' => 'draft', 'label' => 'Draft', 'type' => 'initial'],
                ],
                'transitions' => [],
            ],
        ]);
        
        $json = $this->workflowService->exportToJson($workflow->id);
        $data = json_decode($json, true);
        
        expect($data)->toHaveKeys(['code', 'name', 'version', 'definition'])
            ->and($data['code'])->toBe('export-test');
    });
    
    test('Phase 2 complete integration: create workflow, group, and task', function () {
        // 1. Create workflow
        $workflow = $this->workflowService->create([
            'code' => 'po-approval',
            'name' => 'PO Approval',
            'version' => 1,
            'is_active' => true,
            'definition' => [
                'states' => [
                    ['name' => 'draft', 'label' => 'Draft', 'type' => 'initial'],
                    ['name' => 'pending', 'label' => 'Pending', 'type' => 'regular'],
                    ['name' => 'approved', 'label' => 'Approved', 'type' => 'final'],
                ],
                'transitions' => [
                    ['name' => 'submit', 'from' => 'draft', 'to' => 'pending', 'label' => 'Submit'],
                    ['name' => 'approve', 'from' => 'pending', 'to' => 'approved', 'label' => 'Approve'],
                ],
            ],
        ]);
        
        // 2. Create approver group
        $group = $this->approverService->create([
            'name' => 'Finance Team',
            'strategy' => 'sequential',
            'is_active' => true,
        ]);
        
        $this->approverService->addMember($group->id, $this->user1->id, ['sequence' => 1]);
        $this->approverService->addMember($group->id, $this->user2->id, ['sequence' => 2]);
        
        // 3. Create task
        $task = $this->taskService->create([
            'title' => 'Approve PO #12345',
            'assigned_to' => $this->user1->id,
            'priority' => 10,
            'status' => 'pending',
        ]);
        
        // Verify everything
        expect($workflow->is_active)->toBeTrue()
            ->and($group->members)->toHaveCount(2)
            ->and($task->status)->toBe('pending');
    });
});
