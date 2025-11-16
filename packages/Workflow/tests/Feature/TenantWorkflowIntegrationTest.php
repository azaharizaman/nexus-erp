<?php

declare(strict_types=1);

use Nexus\Erp\Models\Tenant;
use Nexus\Tenancy\Enums\TenantStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure tenants table exists with workflow_state column
    if (!Schema::hasColumn('tenants', 'workflow_state')) {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('workflow_state')->default('pending')->after('status');
        });
    }
});

describe('Tenant Workflow Integration', function () {
    it('demonstrates atomicity - tenancy package has no workflow knowledge', function () {
        // Verify nexus-tenancy base model has no workflow trait
        $baseTenant = new \Nexus\Tenancy\Models\Tenant();
        
        expect(method_exists($baseTenant, 'workflow'))->toBeFalse();
        expect(method_exists($baseTenant, 'workflowDefinition'))->toBeFalse();
    });
    
    it('demonstrates orchestration - ERP model combines both packages', function () {
        // Verify Nexus\Erp model has workflow capabilities
        $erpTenant = new Tenant();
        
        expect(method_exists($erpTenant, 'workflow'))->toBeTrue();
        expect(method_exists($erpTenant, 'workflowDefinition'))->toBeTrue();
        expect($erpTenant)->toBeInstanceOf(\Nexus\Tenancy\Models\Tenant::class);
    });
});

describe('Tenant Lifecycle Workflow', function () {
    it('initializes tenant in pending state', function () {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'domain' => 'acme.example.com',
            'billing_email' => 'billing@acme.example.com',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@acme.example.com',
        ]);
        
        expect($tenant->workflow()->currentState())->toBe('pending');
        expect($tenant->getWorkflowState())->toBe('pending');
    });
    
    it('can activate a pending tenant with approval', function () {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'domain' => 'acme.example.com',
            'billing_email' => 'billing@acme.example.com',
        ]);
        
        // Check available transitions
        $transitions = $tenant->workflow()->availableTransitions([
            'approved_by' => 1,
        ]);
        
        expect($transitions)->toContain('activate');
        
        // Activate the tenant
        $result = $tenant->workflow()->apply('activate', [
            'approved_by' => 1,
        ]);
        
        expect($result->isSuccess())->toBeTrue();
        expect($tenant->workflow()->currentState())->toBe('active');
        
        // Verify the status enum was updated via after hook
        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::ACTIVE);
    });
    
    it('prevents activation without required data', function () {
        $tenant = Tenant::create([
            'name' => 'Incomplete Corp',
            'domain' => null, // Missing domain
            'billing_email' => 'billing@incomplete.example.com',
        ]);
        
        // Try to activate without domain
        expect($tenant->workflow()->can('activate', ['approved_by' => 1]))
            ->toBeFalse();
        
        // Add domain
        $tenant->domain = 'incomplete.example.com';
        $tenant->save();
        
        // Now activation should be possible
        expect($tenant->workflow()->can('activate', ['approved_by' => 1]))
            ->toBeTrue();
    });
    
    it('prevents activation without approval', function () {
        $tenant = Tenant::create([
            'name' => 'Test Corp',
            'domain' => 'test.example.com',
            'billing_email' => 'billing@test.example.com',
        ]);
        
        // Without approval
        expect($tenant->workflow()->can('activate', ['approved_by' => false]))
            ->toBeFalse();
        
        // With approval
        expect($tenant->workflow()->can('activate', ['approved_by' => 1]))
            ->toBeTrue();
    });
});

describe('Tenant Suspension and Reactivation', function () {
    it('can suspend an active tenant', function () {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'domain' => 'acme.example.com',
            'billing_email' => 'billing@acme.example.com',
        ]);
        
        // Activate first
        $tenant->workflow()->apply('activate', ['approved_by' => 1]);
        expect($tenant->workflow()->currentState())->toBe('active');
        
        // Suspend
        $result = $tenant->workflow()->apply('suspend', [
            'suspended_by' => 1,
            'reason' => 'Overdue payment',
        ]);
        
        expect($result->isSuccess())->toBeTrue();
        expect($tenant->workflow()->currentState())->toBe('suspended');
        
        // Verify status enum updated
        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::SUSPENDED);
    });
    
    it('can reactivate a suspended tenant after issue resolution', function () {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'domain' => 'acme.example.com',
            'billing_email' => 'billing@acme.example.com',
        ]);
        
        // Activate, then suspend
        $tenant->workflow()->apply('activate', ['approved_by' => 1]);
        $tenant->workflow()->apply('suspend', [
            'suspended_by' => 1,
            'reason' => 'Overdue payment',
        ]);
        
        expect($tenant->workflow()->currentState())->toBe('suspended');
        
        // Try to reactivate without resolution
        expect($tenant->workflow()->can('reactivate', ['issue_resolved' => false]))
            ->toBeFalse();
        
        // Reactivate after issue resolved
        $result = $tenant->workflow()->apply('reactivate', [
            'issue_resolved' => true,
            'reactivated_by' => 1,
        ]);
        
        expect($result->isSuccess())->toBeTrue();
        expect($tenant->workflow()->currentState())->toBe('active');
        
        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::ACTIVE);
    });
    
    it('tracks suspension history with reason', function () {
        $tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'domain' => 'acme.example.com',
            'billing_email' => 'billing@acme.example.com',
        ]);
        
        $tenant->workflow()->apply('activate', ['approved_by' => 1]);
        $tenant->workflow()->apply('suspend', [
            'suspended_by' => 42,
            'reason' => 'Policy violation',
        ]);
        
        $history = $tenant->workflow()->history();
        
        expect($history)->toHaveCount(2);
        
        $suspensionEvent = $history[1];
        expect($suspensionEvent)
            ->toHaveKey('transition', 'suspend')
            ->toHaveKey('from', 'active')
            ->toHaveKey('to', 'suspended')
            ->toHaveKey('metadata');
        
        expect($suspensionEvent['metadata'])
            ->toHaveKey('suspended_by', 42)
            ->toHaveKey('reason', 'Policy violation');
    });
});

describe('Tenant Archival', function () {
    it('can archive active tenant with admin approval', function () {
        $tenant = Tenant::create([
            'name' => 'Old Corp',
            'domain' => 'old.example.com',
            'billing_email' => 'billing@old.example.com',
        ]);
        
        $tenant->workflow()->apply('activate', ['approved_by' => 1]);
        
        // Archive requires admin approval
        expect($tenant->workflow()->can('archive', ['admin_approved' => false]))
            ->toBeFalse();
        
        $result = $tenant->workflow()->apply('archive', [
            'admin_approved' => true,
            'archived_by' => 1,
            'reason' => 'Business closed',
        ]);
        
        expect($result->isSuccess())->toBeTrue();
        expect($tenant->workflow()->currentState())->toBe('archived');
        
        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::ARCHIVED);
        expect($tenant->trashed())->toBeTrue(); // Soft deleted
    });
    
    it('can archive suspended tenant', function () {
        $tenant = Tenant::create([
            'name' => 'Suspended Corp',
            'domain' => 'suspended.example.com',
            'billing_email' => 'billing@suspended.example.com',
        ]);
        
        $tenant->workflow()->apply('activate', ['approved_by' => 1]);
        $tenant->workflow()->apply('suspend', ['suspended_by' => 1]);
        
        $result = $tenant->workflow()->apply('archive', [
            'admin_approved' => true,
            'archived_by' => 1,
        ]);
        
        expect($result->isSuccess())->toBeTrue();
    });
    
    it('can restore archived tenant with super admin approval', function () {
        $tenant = Tenant::create([
            'name' => 'Restored Corp',
            'domain' => 'restored.example.com',
            'billing_email' => 'billing@restored.example.com',
        ]);
        
        $tenant->workflow()->apply('activate', ['approved_by' => 1]);
        $tenant->workflow()->apply('archive', [
            'admin_approved' => true,
            'archived_by' => 1,
        ]);
        
        expect($tenant->workflow()->currentState())->toBe('archived');
        expect($tenant->trashed())->toBeTrue();
        
        // Restore requires super admin and data integrity
        expect($tenant->workflow()->can('restore', [
            'super_admin_approved' => true,
            'data_intact' => false,
        ]))->toBeFalse();
        
        $result = $tenant->workflow()->apply('restore', [
            'super_admin_approved' => true,
            'data_intact' => true,
            'restored_by' => 1,
        ]);
        
        expect($result->isSuccess())->toBeTrue();
        expect($tenant->workflow()->currentState())->toBe('active');
        
        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::ACTIVE);
        expect($tenant->trashed())->toBeFalse();
    });
});

describe('Complete Tenant Lifecycle', function () {
    it('demonstrates full lifecycle with history tracking', function () {
        // 1. Create pending tenant
        $tenant = Tenant::create([
            'name' => 'Lifecycle Corp',
            'domain' => 'lifecycle.example.com',
            'billing_email' => 'billing@lifecycle.example.com',
            'contact_name' => 'Jane Smith',
            'contact_email' => 'jane@lifecycle.example.com',
        ]);
        
        expect($tenant->workflow()->currentState())->toBe('pending');
        
        // 2. Activate
        $tenant->workflow()->apply('activate', [
            'approved_by' => 1,
        ]);
        expect($tenant->workflow()->currentState())->toBe('active');
        
        // 3. Suspend due to payment issue
        $tenant->workflow()->apply('suspend', [
            'suspended_by' => 2,
            'reason' => 'Overdue payment - 30 days',
        ]);
        expect($tenant->workflow()->currentState())->toBe('suspended');
        
        // 4. Reactivate after payment received
        $tenant->workflow()->apply('reactivate', [
            'issue_resolved' => true,
            'reactivated_by' => 2,
        ]);
        expect($tenant->workflow()->currentState())->toBe('active');
        
        // 5. Archive due to business closure
        $tenant->workflow()->apply('archive', [
            'admin_approved' => true,
            'archived_by' => 1,
            'reason' => 'Business permanently closed',
        ]);
        expect($tenant->workflow()->currentState())->toBe('archived');
        
        // Verify complete history
        $history = $tenant->workflow()->history();
        expect($history)->toHaveCount(4);
        
        expect($history[0]['transition'])->toBe('activate');
        expect($history[1]['transition'])->toBe('suspend');
        expect($history[2]['transition'])->toBe('reactivate');
        expect($history[3]['transition'])->toBe('archive');
        
        // Verify final state
        $tenant->refresh();
        expect($tenant->status)->toBe(TenantStatus::ARCHIVED);
        expect($tenant->trashed())->toBeTrue();
    });
});

describe('ACID Compliance', function () {
    it('wraps workflow transitions in database transactions', function () {
        $tenant = Tenant::create([
            'name' => 'ACID Test Corp',
            'domain' => 'acid.example.com',
            'billing_email' => 'billing@acid.example.com',
        ]);
        
        $initialState = $tenant->workflow()->currentState();
        
        // Try invalid transition (should not change database)
        $result = $tenant->workflow()->apply('suspend'); // Can't suspend from pending
        
        expect($result->isFailure())->toBeTrue();
        
        // Verify database state unchanged
        $tenant->refresh();
        expect($tenant->getWorkflowState())->toBe($initialState);
        expect($tenant->workflow()->currentState())->toBe($initialState);
    });
});

describe('Separation of Concerns', function () {
    it('keeps workflow_state separate from business status', function () {
        $tenant = Tenant::create([
            'name' => 'Separation Corp',
            'domain' => 'separation.example.com',
            'billing_email' => 'billing@separation.example.com',
        ]);
        
        // Workflow state and business status are separate
        expect($tenant->workflow_state)->toBe('pending'); // Workflow state
        expect($tenant->status)->toBeInstanceOf(TenantStatus::class); // Business enum
        
        // Activate tenant
        $tenant->workflow()->apply('activate', ['approved_by' => 1]);
        
        $tenant->refresh();
        expect($tenant->workflow_state)->toBe('active'); // Workflow updated
        expect($tenant->status)->toBe(TenantStatus::ACTIVE); // Enum updated via hook
    });
});
