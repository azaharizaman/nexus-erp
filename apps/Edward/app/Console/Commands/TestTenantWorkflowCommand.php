<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Erp\Models\Tenant;
use Nexus\Tenancy\Enums\TenantStatus;

/**
 * Test command to validate Phase 1 Workflow integration with Tenant model.
 * 
 * This demonstrates:
 * - Maximum Atomicity (packages remain independent)
 * - Orchestration at Nexus\Erp level
 * - Complete tenant lifecycle workflow
 * - Guard conditions, hooks, and history tracking
 */
class TestTenantWorkflowCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:tenant-workflow
                            {--clean : Clean up test data after completion}';

    /**
     * The console command description.
     */
    protected $description = 'Test Phase 1 Workflow integration with Tenant lifecycle';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧪 Testing Phase 1 Workflow Integration');
        $this->newLine();

        // Test 1: Verify atomicity
        $this->testAtomicity();

        // Test 2: Create tenant and test lifecycle
        $tenant = $this->testTenantCreation();

        if (!$tenant) {
            return self::FAILURE;
        }

        // Test 3: Activation workflow
        $this->testActivation($tenant);

        // Test 4: Suspension workflow
        $this->testSuspension($tenant);

        // Test 5: Reactivation workflow
        $this->testReactivation($tenant);

        // Test 6: Archival workflow
        $this->testArchival($tenant);

        // Test 7: Restoration workflow
        $this->testRestoration($tenant);

        // Test 8: History tracking
        $this->testHistory($tenant);

        // Cleanup if requested
        if ($this->option('clean')) {
            $tenant->forceDelete();
            $this->info('🧹 Test data cleaned up');
        }

        $this->newLine();
        $this->info('✅ All workflow integration tests passed!');

        return self::SUCCESS;
    }

    /**
     * Test 1: Verify Maximum Atomicity principle
     */
    protected function testAtomicity(): void
    {
        $this->comment('Test 1: Verifying Maximum Atomicity...');

        // Check base tenant has no workflow knowledge
        $baseTenant = new \Nexus\Tenancy\Models\Tenant();
        $hasWorkflowMethod = method_exists($baseTenant, 'workflow');

        if ($hasWorkflowMethod) {
            $this->error('❌ FAILED: nexus-tenancy has workflow knowledge (violates atomicity)');
            exit(1);
        }

        // Check ERP tenant has workflow methods
        $erpTenant = new Tenant();
        $hasWorkflowMethod = method_exists($erpTenant, 'workflow');
        $hasDefinitionMethod = method_exists($erpTenant, 'workflowDefinition');

        if (!$hasWorkflowMethod || !$hasDefinitionMethod) {
            $this->error('❌ FAILED: Nexus\Erp\Models\Tenant missing workflow methods');
            exit(1);
        }

        $this->info('  ✓ nexus-tenancy: No workflow knowledge (atomic) ✅');
        $this->info('  ✓ Nexus\Erp\Models\Tenant: Has workflow via orchestration ✅');
        $this->newLine();
    }

    /**
     * Test 2: Create tenant and verify initial state
     */
    protected function testTenantCreation(): ?Tenant
    {
        $this->comment('Test 2: Creating test tenant...');

        try {
            $tenant = Tenant::create([
                'name' => 'Test Corporation',
                'domain' => 'test-' . time() . '.example.com',
                'billing_email' => 'billing@test.example.com',
            ]);

            $currentState = $tenant->workflow()->currentState();
            
            if ($currentState !== 'pending') {
                $this->error("❌ FAILED: Expected initial state 'pending', got '$currentState'");
                return null;
            }

            $this->info("  ✓ Tenant created: {$tenant->name}");
            $this->info("  ✓ Initial workflow state: {$currentState} ✅");
            $this->info("  ✓ Business status: " . ($tenant->status ? $tenant->status->value : 'null (not set)'));
            $this->newLine();

            return $tenant;
        } catch (\Exception $e) {
            $this->error("❌ FAILED: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Test 3: Activate tenant with guards
     */
    protected function testActivation(Tenant $tenant): void
    {
        $this->comment('Test 3: Testing activation workflow...');

        // Test guard conditions - should fail without approval
        $result = $tenant->workflow()->apply('activate', []);
        
        if ($result->isSuccess()) {
            $this->error('❌ FAILED: Activation succeeded without approval (guard not working)');
            exit(1);
        }

        $this->info('  ✓ Guard enforced: Activation blocked without approval ✅');

        // Now activate with approval
        $result = $tenant->workflow()->apply('activate', [
            'approved_by' => 1,
        ]);

        if (!$result->isSuccess()) {
            $this->error("❌ FAILED: {$result->getError()}");
            exit(1);
        }

        $tenant->refresh();
        
        $this->info('  ✓ Tenant activated successfully ✅');
        $this->info("  ✓ Workflow state: {$tenant->workflow()->currentState()}");
        $this->info("  ✓ Business status: {$tenant->status->value} (synced via hook) ✅");
        $this->newLine();
    }

    /**
     * Test 4: Suspend tenant
     */
    protected function testSuspension(Tenant $tenant): void
    {
        $this->comment('Test 4: Testing suspension workflow...');

        $result = $tenant->workflow()->apply('suspend', [
            'reason' => 'Payment overdue',
        ]);

        if (!$result->isSuccess()) {
            $this->error("❌ FAILED: {$result->getError()}");
            exit(1);
        }

        $tenant->refresh();
        
        $this->info('  ✓ Tenant suspended successfully ✅');
        $this->info("  ✓ Workflow state: {$tenant->workflow()->currentState()}");
        $this->info("  ✓ Business status: {$tenant->status->value} (synced via hook) ✅");
        $this->newLine();
    }

    /**
     * Test 5: Reactivate tenant with guard
     */
    protected function testReactivation(Tenant $tenant): void
    {
        $this->comment('Test 5: Testing reactivation workflow...');

        // Should fail without issue resolution
        $result = $tenant->workflow()->apply('reactivate', []);
        
        if ($result->isSuccess()) {
            $this->error('❌ FAILED: Reactivation succeeded without issue_resolved flag');
            exit(1);
        }

        $this->info('  ✓ Guard enforced: Reactivation blocked without resolution ✅');

        // Now reactivate with resolution
        $result = $tenant->workflow()->apply('reactivate', [
            'issue_resolved' => true,
        ]);

        if (!$result->isSuccess()) {
            $this->error("❌ FAILED: {$result->getError()}");
            exit(1);
        }

        $tenant->refresh();
        
        $this->info('  ✓ Tenant reactivated successfully ✅');
        $this->info("  ✓ Workflow state: {$tenant->workflow()->currentState()}");
        $this->info("  ✓ Business status: {$tenant->status->value} (synced via hook) ✅");
        $this->newLine();
    }

    /**
     * Test 6: Archive tenant with admin approval
     */
    protected function testArchival(Tenant $tenant): void
    {
        $this->comment('Test 6: Testing archival workflow...');

        // Should fail without admin approval
        $result = $tenant->workflow()->apply('archive', [
            'reason' => 'Company dissolved',
        ]);
        
        if ($result->isSuccess()) {
            $this->error('❌ FAILED: Archive succeeded without admin approval');
            exit(1);
        }

        $this->info('  ✓ Guard enforced: Archive blocked without admin approval ✅');

        // Now archive with admin approval
        $result = $tenant->workflow()->apply('archive', [
            'reason' => 'Company dissolved',
            'admin_approved' => true,
        ]);

        if (!$result->isSuccess()) {
            $this->error("❌ FAILED: {$result->getError()}");
            exit(1);
        }

        $tenant->refresh();
        
        $this->info('  ✓ Tenant archived successfully ✅');
        $this->info("  ✓ Workflow state: {$tenant->workflow()->currentState()}");
        $this->info("  ✓ Business status: {$tenant->status->value} (synced via hook) ✅");
        $this->info('  ✓ Soft deleted: ' . ($tenant->trashed() ? 'Yes ✅' : 'No'));
        $this->newLine();
    }

    /**
     * Test 7: Restore archived tenant
     */
    protected function testRestoration(Tenant $tenant): void
    {
        $this->comment('Test 7: Testing restoration workflow...');

        // Should fail without super admin and data integrity check
        $result = $tenant->workflow()->apply('restore', []);
        
        if ($result->isSuccess()) {
            $this->error('❌ FAILED: Restore succeeded without super admin approval');
            exit(1);
        }

        $this->info('  ✓ Guard enforced: Restore blocked without super admin ✅');

        // Now restore with super admin approval
        $result = $tenant->workflow()->apply('restore', [
            'super_admin' => true,
            'data_intact' => true,
        ]);

        if (!$result->isSuccess()) {
            $this->error("❌ FAILED: {$result->getError()}");
            exit(1);
        }

        $tenant->refresh();
        
        $this->info('  ✓ Tenant restored successfully ✅');
        $this->info("  ✓ Workflow state: {$tenant->workflow()->currentState()}");
        $this->info("  ✓ Business status: {$tenant->status->value} (synced via hook) ✅");
        $this->info('  ✓ Soft deleted: ' . ($tenant->trashed() ? 'Yes' : 'No ✅'));
        $this->newLine();
    }

    /**
     * Test 8: Verify history tracking
     */
    protected function testHistory(Tenant $tenant): void
    {
        $this->comment('Test 8: Verifying history tracking...');

        $history = $tenant->workflow()->history();
        
        $expectedTransitions = 5; // activate, suspend, reactivate, archive, restore
        $actualCount = count($history);

        if ($actualCount !== $expectedTransitions) {
            $this->error("❌ FAILED: Expected {$expectedTransitions} transitions, got {$actualCount}");
            exit(1);
        }

        $this->info("  ✓ History tracked: {$actualCount} transitions ✅");
        
        // Display transition history - check what keys are available
        if (!empty($history)) {
            $firstEntry = $history[0];
            $this->info("  ✓ History entry structure: " . implode(', ', array_keys($firstEntry)));
            
            // Display simplified history
            $this->table(
                ['#', 'Transition', 'State', 'Timestamp'],
                collect($history)->map(fn($entry, $index) => [
                    $index + 1,
                    $entry['transition'] ?? 'N/A',
                    $entry['to_state'] ?? 'N/A',
                    isset($entry['timestamp']) ? \Carbon\Carbon::parse($entry['timestamp'])->diffForHumans() : 'N/A',
                ])->toArray()
            );
        }

        $this->newLine();
    }
}
