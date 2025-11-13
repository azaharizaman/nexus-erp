<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Feature;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\Observers\StaffObserver;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Observers\DepartmentObserver;
use Nexus\Backoffice\Observers\StaffTransferObserver;
use Nexus\Backoffice\Exceptions\InvalidTransferException;

#[CoversClass(Staff::class)]
#[CoversClass(Office::class)]
#[CoversClass(Company::class)]
#[CoversClass(TestCase::class)]
#[CoversClass(StaffStatus::class)]
#[CoversClass(Department::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(RefreshDatabase::class)]
#[CoversClass(StaffTransferStatus::class)]
#[CoversClass(InvalidTransferException::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(StaffTransferObserver::class)]
class StaffTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Office $sourceOffice;
    protected Office $targetOffice;
    protected Department $sourceDepartment;
    protected Department $targetDepartment;
    protected Staff $staff;
    protected Staff $supervisor;
    protected Staff $newSupervisor;
    protected Staff $hrStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $this->sourceOffice = Office::factory()->create([
            'name' => 'Source Office',
            'code' => 'SRC',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->targetOffice = Office::factory()->create([
            'name' => 'Target Office',
            'code' => 'TGT',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->sourceDepartment = Department::factory()->create([
            'name' => 'Source Department',
            'code' => 'SRC_DEPT',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->targetDepartment = Department::factory()->create([
            'name' => 'Target Department',
            'code' => 'TGT_DEPT',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->supervisor = Staff::factory()->create([
            'employee_id' => 'SUP001',
            'first_name' => 'Current',
            'last_name' => 'Supervisor',
            'email' => 'supervisor@test.com',
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->newSupervisor = Staff::factory()->create([
            'employee_id' => 'SUP002',
            'first_name' => 'New',
            'last_name' => 'Supervisor',
            'email' => 'newsupervisor@test.com',
            'office_id' => $this->targetOffice->id,
            'is_active' => true,
        ]);

        $this->staff = Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'email' => 'employee@test.com',
            'office_id' => $this->sourceOffice->id,
            'department_id' => $this->sourceDepartment->id,
            'supervisor_id' => $this->supervisor->id,
            'is_active' => true,
        ]);

        $this->hrStaff = Staff::factory()->create([
            'employee_id' => 'HR001',
            'first_name' => 'HR',
            'last_name' => 'Manager',
            'email' => 'hr@test.com',
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);
    }

    protected function createTransfer(array $attributes = []): StaffTransfer
    {
        return StaffTransfer::create(array_merge([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => now()->addWeek(),
            'reason' => 'Test transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ], $attributes));
    }

    #[Test]
    public function test_it_can_create_immediate_transfer_request(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'from_department_id' => $this->sourceDepartment->id,
            'to_department_id' => $this->targetDepartment->id,
            'from_supervisor_id' => $this->supervisor->id,
            'to_supervisor_id' => $this->newSupervisor->id,
            'effective_date' => now(),
            'is_immediate' => true,
            'reason' => 'Immediate transfer for project requirements',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);

        // Immediate transfer is auto-processed by observer
        $transfer->refresh();
        
        $this->assertInstanceOf(StaffTransfer::class, $transfer);
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status);
        $this->assertEquals($this->staff->id, $transfer->staff_id);
        $this->assertEquals($this->targetOffice->id, $transfer->to_office_id);
        $this->assertTrue($transfer->effective_date->isToday());
    }

    #[Test]
    public function test_it_can_create_scheduled_transfer_request(): void
    {
        $futureDate = now()->addMonth();

        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => $futureDate,
            'reason' => 'Scheduled transfer for next month',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);

        $this->assertEquals($futureDate->format('Y-m-d'), $transfer->effective_date->format('Y-m-d'));
        $this->assertFalse($transfer->isImmediate());
    }
    
    #[Test]
    public function test_it_can_approve_transfer_request(): void
    {
        $transfer = $this->createTransfer([
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve($this->hrStaff, 'HR Department approval');

        $this->assertEquals(StaffTransferStatus::APPROVED, $transfer->fresh()->status);
        $this->assertEquals($this->hrStaff->id, $transfer->fresh()->approved_by_id);
        $this->assertNotNull($transfer->fresh()->approved_at);
    }

    #[Test]
    public function test_it_can_reject_transfer_request(): void
    {
        $transfer = $this->createTransfer([
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->reject($this->hrStaff, 'Insufficient budget for transfer');

        $this->assertEquals(StaffTransferStatus::REJECTED, $transfer->fresh()->status);
        $this->assertEquals('Insufficient budget for transfer', $transfer->fresh()->rejection_reason);
        $this->assertNotNull($transfer->fresh()->rejected_at);
    }

    #[Test]
    public function test_it_can_cancel_transfer_request(): void
    {
        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->cancel($this->hrStaff, 'Employee request');

        $this->assertEquals(StaffTransferStatus::CANCELLED, $transfer->fresh()->status);
        $this->assertNotNull($transfer->fresh()->cancelled_at);
        $this->assertStringContainsString('Employee request', $transfer->fresh()->notes ?? '');
    }

    #[Test]
    public function test_it_processes_immediate_transfer_automatically_when_approved(): void
    {
        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'from_department_id' => $this->sourceDepartment->id,
            'to_department_id' => $this->targetDepartment->id,
            'from_supervisor_id' => $this->supervisor->id,
            'to_supervisor_id' => $this->newSupervisor->id,
            'effective_date' => now(),
            'is_immediate' => true,
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
        ]);

        // Refresh models to see changes (observer auto-approved and processed it)
        $this->staff->refresh();
        $transfer->refresh();

        // Check if transfer was approved and completed automatically
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status);
        $this->assertNotNull($transfer->approved_at);
        $this->assertNotNull($transfer->completed_at);

        // Check if staff record was updated
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertEquals($this->targetDepartment->id, $this->staff->department_id);
        $this->assertEquals($this->newSupervisor->id, $this->staff->supervisor_id);
    }

    #[Test]
    public function test_it_does_not_process_scheduled_transfer_automatically(): void
    {
        $futureDate = now()->addMonth();

        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => $futureDate,
            'is_immediate' => false,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve($this->hrStaff, 'Scheduled for next month');

        // Refresh models
        $this->staff->refresh();
        $transfer->refresh();

        // Transfer should be approved but not completed
        $this->assertEquals(StaffTransferStatus::APPROVED, $transfer->status);
        $this->assertNull($transfer->completed_at);

        // Staff record should not be updated yet
        $this->assertEquals($this->sourceOffice->id, $this->staff->office_id);
    }

    #[Test]
    public function test_it_validates_against_same_office_transfer(): void
    {
        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('Cannot transfer staff to the same office');

        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->sourceOffice->id, // Same office
            'effective_date' => now(),
            'is_immediate' => true,
            'reason' => 'Invalid transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function test_it_validates_against_pending_transfer_exists(): void
    {
        // Create first transfer
        StaffTransfer::factory()->for($this->staff)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('Staff already has a pending or approved transfer');

        // Try to create second transfer
        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => now(),
            'is_immediate' => true,
            'reason' => 'Duplicate transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function test_it_validates_against_circular_supervisor_reference(): void
    {
        // Create a subordinate
        $subordinate = Staff::factory()->for($this->sourceOffice)->create([
            'supervisor_id' => $this->staff->id,
        ]);

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('Cannot assign supervisor who reports to this staff member');

        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'from_supervisor_id' => $this->supervisor->id,
            'to_supervisor_id' => $subordinate->id, // Circular reference
            'effective_date' => now(),
            'is_immediate' => true,
            'reason' => 'Invalid supervisor assignment',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function test_it_can_transfer_without_changing_supervisor(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'from_supervisor_id' => $this->supervisor->id,
            'to_supervisor_id' => $this->supervisor->id, // Same supervisor
            'effective_date' => now(),
            'is_immediate' => true,
            'reason' => 'Office change only',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);

        // Immediate transfer is auto-approved and processed by observer
        $this->staff->refresh();
        $transfer->refresh();
        
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status);
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertEquals($this->supervisor->id, $this->staff->supervisor_id);
    }

    #[Test]
    public function test_it_can_remove_supervisor_during_transfer(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'from_supervisor_id' => $this->supervisor->id,
            'to_supervisor_id' => null, // Remove supervisor
            'effective_date' => now(),
            'is_immediate' => true,
            'reason' => 'Promote to manager',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);

        // Immediate transfer is auto-approved and processed by observer
        $this->staff->refresh();
        $transfer->refresh();
        
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status);
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertNull($this->staff->supervisor_id);
    }

    #[Test]
    public function test_it_can_only_transfer_office_without_department_change(): void
    {
        // Verify initial state
        $this->assertNotNull($this->staff->department_id, 'Staff should have a department initially');
        $initialDepartmentId = $this->staff->department_id;
        
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            // No department change specified - from_department_id and to_department_id are null
            'from_department_id' => null,
            'to_department_id' => null,
            'effective_date' => now(),
            'is_immediate' => true,
            'reason' => 'Office relocation',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);

        // Immediate transfer is auto-approved and processed by observer
        $this->staff->refresh();
        $transfer->refresh();
        
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status);
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertEquals($initialDepartmentId, $this->staff->department_id, 'Department should remain unchanged when not specified in transfer');
    }

    #[Test]
    public function test_it_tracks_transfer_history_correctly(): void
    {
        // Ensure no transfers exist initially
        $initialCount = $this->staff->transfers()->count();
        $this->assertEquals(0, $initialCount, 'Staff should have no transfers initially');
        
        // Create multiple transfers with explicit created_at timestamps
        $transfer1 = StaffTransfer::factory()
            ->for($this->staff)
            ->immediate()
            ->completed()
            ->create([
                'from_office_id' => $this->sourceOffice->id,
                'to_office_id' => $this->targetOffice->id,
                'completed_at' => now()->subMonth(),
                'created_at' => now()->subDay(),
            ]);

        $transfer2 = StaffTransfer::factory()
            ->for($this->staff)
            ->immediate()
            ->pending()
            ->create([
                'from_office_id' => $this->targetOffice->id,
                'to_office_id' => $this->sourceOffice->id,
                'created_at' => now(),
            ]);

        $transfers = $this->staff->fresh()->transfers()->orderBy('created_at')->get();

        $this->assertCount(2, $transfers, sprintf(
            'Should have exactly 2 transfers, found %d. Transfer IDs: %s',
            $transfers->count(),
            $transfers->pluck('id')->implode(', ')
        ));
        $this->assertEquals(
            $transfer1->id,
            $transfers->first()->id,
            sprintf('Expected first transfer ID to be %d but got %d', $transfer1->id, $transfers->first()->id)
        );
        $this->assertEquals(
            $transfer2->id,
            $transfers->last()->id,
            sprintf('Expected last transfer ID to be %d but got %d', $transfer2->id, $transfers->last()->id)
        );
    }

    #[Test]
    public function test_it_can_check_if_staff_has_active_transfer(): void
    {
        // No active transfer initially
        $this->assertFalse($this->staff->hasActiveTransfer());

        // Create pending transfer
        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
            'is_immediate' => false,
        ]);

        $this->assertTrue($this->staff->hasActiveTransfer());

        // Change to approved status
        $transfer->update(['status' => StaffTransferStatus::APPROVED]);
        $this->staff->refresh();

        $this->assertTrue($this->staff->hasActiveTransfer());
        
        // Complete the transfer
        $transfer->update(['status' => StaffTransferStatus::COMPLETED]);
        $this->staff->refresh();
        
        // No longer has active transfer
        $this->assertFalse($this->staff->hasActiveTransfer());
    }

    #[Test]
    public function test_it_can_check_if_staff_can_be_transferred(): void
    {
        // Active staff can be transferred
        $this->assertTrue($this->staff->canBeTransferred());

        // Resigned staff cannot be transferred
        $this->staff->update(['status' => StaffStatus::RESIGNED]);
        $this->assertFalse($this->staff->canBeTransferred());

        // Staff with pending transfer cannot have another transfer
        $this->staff->update(['status' => StaffStatus::ACTIVE]);
        StaffTransfer::factory()->for($this->staff)->create([
            'status' => StaffTransferStatus::PENDING,
        ]);

        $this->assertFalse($this->staff->canBeTransferred());
    }

    #[Test]
    public function test_it_provides_transfer_scopes(): void
    {
        $staff1 = Staff::factory()->for($this->sourceOffice)->create();
        $staff2 = Staff::factory()->for($this->sourceOffice)->create();
        $staff3 = Staff::factory()->for($this->sourceOffice)->create();
        $staff4 = Staff::factory()->for($this->sourceOffice)->create();

        $pending = StaffTransfer::factory()->for($staff1)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
            'is_immediate' => false,
        ]);

        $approved = StaffTransfer::factory()->for($staff2)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::APPROVED,
            'is_immediate' => false,
            'effective_date' => now()->addWeek(),
        ]);

        $completed = StaffTransfer::factory()->for($staff3)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        $rejected = StaffTransfer::factory()->for($staff4)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::REJECTED,
        ]);

        // Test pending scope
        $pendingTransfers = StaffTransfer::pending()->get();
        $this->assertCount(1, $pendingTransfers);
        $this->assertEquals($pending->id, $pendingTransfers->first()->id);

        // Test approved scope
        $approvedTransfers = StaffTransfer::approved()->get();
        $this->assertCount(1, $approvedTransfers);
        $this->assertEquals($approved->id, $approvedTransfers->first()->id);

        // Test completed scope
        $completedTransfers = StaffTransfer::completed()->get();
        $this->assertCount(1, $completedTransfers);
        $this->assertEquals($completed->id, $completedTransfers->first()->id);

        // Test rejected scope
        $rejectedTransfers = StaffTransfer::rejected()->get();
        $this->assertCount(1, $rejectedTransfers);
        $this->assertEquals($rejected->id, $rejectedTransfers->first()->id);

        // Test dueForProcessing scope (no transfers should be due since approved one is future-dated)
        $dueTransfers = StaffTransfer::dueForProcessing()->get();
        $this->assertCount(0, $dueTransfers);
    }

    #[Test]
    public function test_it_handles_transfer_request_helper_method(): void
    {
        $requestedBy = Staff::factory()->for($this->sourceOffice)->create();

        $transferData = $this->staff->requestTransfer(
            toOffice: $this->targetOffice,
            requestedBy: $requestedBy,
            effectiveDate: now()->addWeek(),
            toDepartment: $this->targetDepartment,
            toSupervisor: $this->newSupervisor,
            reason: 'Career development opportunity'
        );

        $this->assertInstanceOf(StaffTransfer::class, $transferData);
        $this->assertEquals($this->staff->id, $transferData->staff_id);
        $this->assertEquals($this->targetOffice->id, $transferData->to_office_id);
        $this->assertEquals($this->targetDepartment->id, $transferData->to_department_id);
        $this->assertEquals($this->newSupervisor->id, $transferData->to_supervisor_id);
        $this->assertEquals('Career development opportunity', $transferData->reason);
        $this->assertEquals(StaffTransferStatus::PENDING, $transferData->status);
    }

    #[Test]
    public function test_it_validates_effective_date_not_in_past(): void
    {
        $this->expectException(InvalidTransferException::class);

        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => now()->subDay(), // Past date
            'reason' => 'Invalid date',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->hrStaff->id,
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function test_it_cannot_modify_final_status_transfers(): void
    {
        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::COMPLETED,
        ]);

        $this->assertFalse($transfer->status->canBeModified());
        $this->assertTrue($transfer->status->isFinal());
        $this->assertFalse($transfer->canBeApproved());
        $this->assertFalse($transfer->canBeRejected());
        $this->assertFalse($transfer->canBeCancelled());
    }
}