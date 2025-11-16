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

#[CoversClass(StaffTransfer::class)]
#[CoversClass(Staff::class)]
#[CoversClass(Office::class)]
#[CoversClass(Company::class)]
#[CoversClass(TestCase::class)]
#[CoversClass(CoversClass::class)]
#[CoversClass(StaffStatus::class)]
#[CoversClass(Department::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(RefreshDatabase::class)]
#[CoversClass(StaffTransferStatus::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(StaffTransferObserver::class)]
#[CoversClass(InvalidTransferException::class)]
#[CoversClass(BackOfficeServiceProvider::class)]

class StaffTransferFeatureTest extends TestCase
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
            'status' => StaffStatus::ACTIVE,
        ]);

        $this->newSupervisor = Staff::factory()->create([
            'employee_id' => 'SUP002',
            'first_name' => 'New',
            'last_name' => 'Supervisor',
            'email' => 'newsupervisor@test.com',
            'office_id' => $this->targetOffice->id,
            'is_active' => true,
            'status' => StaffStatus::ACTIVE,
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
            'status' => StaffStatus::ACTIVE,
        ]);

        $this->hrStaff = Staff::factory()->create([
            'employee_id' => 'HR001',
            'first_name' => 'HR',
            'last_name' => 'Manager',
            'email' => 'hr@test.com',
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
            'status' => StaffStatus::ACTIVE,
        ]);
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
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);

        $this->assertInstanceOf(StaffTransfer::class, $transfer);
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status); // Immediate transfers auto-complete
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
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);

        $this->assertEquals($futureDate->format('Y-m-d'), $transfer->effective_date->format('Y-m-d'));
        $this->assertFalse($transfer->isImmediate());
    }

    #[Test]
    public function test_it_can_approve_transfer_request(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => now()->addWeek(),
            'reason' => 'Test transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);

        $transfer->approve($this->hrStaff, 'HR Department approval');

        $this->assertEquals(StaffTransferStatus::APPROVED, $transfer->fresh()->status);
        $this->assertEquals($this->hrStaff->id, $transfer->fresh()->approved_by_id);
        $this->assertNotNull($transfer->fresh()->approved_at);
    }

    #[Test]
    public function test_it_can_reject_transfer_request(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => now()->addWeek(),
            'reason' => 'Test transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);

        $transfer->reject($this->hrStaff, 'Insufficient budget for transfer');

        $this->assertEquals(StaffTransferStatus::REJECTED, $transfer->fresh()->status);
        $this->assertEquals('Insufficient budget for transfer', $transfer->fresh()->rejection_reason);
        $this->assertNotNull($transfer->fresh()->rejected_at);
    }

    #[Test]
    public function test_it_processes_immediate_transfer_automatically_when_created(): void
    {
        // Store original staff assignments
        $originalOfficeId = $this->staff->office_id;
        $originalDepartmentId = $this->staff->department_id;
        $originalSupervisorId = $this->staff->supervisor_id;

        // Create an immediate transfer - it should auto-complete
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
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);

        // Refresh models to see changes
        $this->staff->refresh();
        $transfer->refresh();

        // Check if transfer was completed automatically
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status);
        $this->assertNotNull($transfer->completed_at);

        // Check if staff record was updated
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertEquals($this->targetDepartment->id, $this->staff->department_id);
        $this->assertEquals($this->newSupervisor->id, $this->staff->supervisor_id);
        
        // Verify change from original
        $this->assertNotEquals($originalOfficeId, $this->staff->office_id);
        $this->assertNotEquals($originalDepartmentId, $this->staff->department_id);
        $this->assertNotEquals($originalSupervisorId, $this->staff->supervisor_id);
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
            'reason' => 'Invalid transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);
    }

    #[Test]
    public function test_it_can_use_helper_method_to_request_transfer(): void
    {
        $transferData = $this->staff->requestTransfer(
            toOffice: $this->targetOffice,
            requestedBy: $this->hrStaff,
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
    public function test_it_can_check_if_staff_has_active_transfer(): void
    {
        // No active transfer initially
        $this->assertFalse($this->staff->hasActiveTransfer());

        // Create pending transfer
        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'effective_date' => now()->addWeek(),
            'reason' => 'Test transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);

        $this->assertTrue($this->staff->hasActiveTransfer());
    }

    #[Test]
    public function test_it_can_check_if_staff_can_be_transferred(): void
    {
        // Active staff can be transferred
        $this->assertTrue($this->staff->canBeTransferred());

        // Resigned staff cannot be transferred
        $this->staff->update(['status' => StaffStatus::RESIGNED]);
        $this->assertFalse($this->staff->canBeTransferred());
    }

    #[Test]
    public function test_transfer_relationships_work_correctly(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'from_department_id' => $this->sourceDepartment->id,
            'to_department_id' => $this->targetDepartment->id,
            'from_supervisor_id' => $this->supervisor->id,
            'to_supervisor_id' => $this->newSupervisor->id,
            'effective_date' => now()->addWeek(),
            'reason' => 'Test transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by_id' => $this->staff->id,
            'requested_at' => now(),
        ]);

        // Test relationships
        $this->assertEquals($this->staff->id, $transfer->staff->id);
        $this->assertEquals($this->sourceOffice->id, $transfer->fromOffice->id);
        $this->assertEquals($this->targetOffice->id, $transfer->toOffice->id);
        $this->assertEquals($this->sourceDepartment->id, $transfer->fromDepartment->id);
        $this->assertEquals($this->targetDepartment->id, $transfer->toDepartment->id);
        $this->assertEquals($this->supervisor->id, $transfer->fromSupervisor->id);
        $this->assertEquals($this->newSupervisor->id, $transfer->toSupervisor->id);
        $this->assertEquals($this->staff->id, $transfer->requestedBy->id);
    }
}