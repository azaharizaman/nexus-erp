<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use Nexus\Backoffice\Models\Position;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Enums\PositionType;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\Observers\StaffObserver;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Observers\DepartmentObserver;
use Nexus\Backoffice\Policies\StaffTransferPolicy;
use Nexus\Backoffice\Observers\StaffTransferObserver;

#[CoversClass(StaffTransferPolicy::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(Staff::class)]
#[CoversClass(Company::class)]
#[CoversClass(Office::class)]
#[CoversClass(Department::class)]
#[CoversClass(Position::class)]
#[CoversClass(StaffTransferStatus::class)]
#[CoversClass(PositionType::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
#[CoversClass(StaffStatus::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(StaffTransferObserver::class)]

class StaffTransferPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Office $sourceOffice;
    protected Office $targetOffice;
    protected Department $hrDepartment;
    protected Department $otherDepartment;
    protected Position $hrManagerPosition;
    protected Position $regularPosition;
    protected Position $seniorManagerPosition;
    protected Staff $hrStaff;
    protected Staff $regularStaff;
    protected Staff $regularStaff2;
    protected Staff $seniorManager;
    protected Staff $supervisor;
    protected Staff $subordinate;
    protected Staff $unrelatedStaff;
    protected StaffTransfer $pendingTransfer;
    protected StaffTransfer $approvedTransfer;
    protected StaffTransfer $completedTransfer;
    protected StaffTransfer $nonPendingTransfer;
    protected StaffTransferPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new \Nexus\BackofficeManagement\Policies\StaffTransferPolicy();

        // Create company and offices
        $this->company = Company::factory()->create();
        $this->sourceOffice = Office::factory()->for($this->company)->create();
        $this->targetOffice = Office::factory()->for($this->company)->create();

        // Create departments
        $this->hrDepartment = Department::factory()->for($this->company)->create(['code' => 'HR']);
        $this->otherDepartment = Department::factory()->for($this->company)->create();

        // Create positions
        $this->hrManagerPosition = Position::factory()->for($this->company)->create([
            'name' => 'HR Manager',
            'code' => 'HR-MGR',
            'type' => PositionType::MANAGEMENT,
        ]);
        $this->regularPosition = Position::factory()->for($this->company)->create([
            'name' => 'Regular Staff',
            'code' => 'REG-001',
            'type' => PositionType::EXECUTIVE,
        ]);
        $this->seniorManagerPosition = Position::factory()->for($this->company)->create([
            'name' => 'Senior Manager',
            'code' => 'SR-MGR',
            'type' => PositionType::SENIOR_EXECUTIVE,
        ]);

        // Create staff with different roles
        $this->hrStaff = Staff::factory()->create([
            'position_id' => $this->hrManagerPosition->id,
            'department_id' => $this->hrDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->seniorManager = Staff::factory()->create([
            'position_id' => $this->seniorManagerPosition->id,
            'department_id' => $this->otherDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->supervisor = Staff::factory()->create([
            'position_id' => $this->regularPosition->id,
            'department_id' => $this->otherDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->subordinate = Staff::factory()->create([
            'position_id' => $this->regularPosition->id,
            'department_id' => $this->otherDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'supervisor_id' => $this->supervisor->id,
            'is_active' => true,
        ]);

        $this->regularStaff = Staff::factory()->create([
            'position_id' => $this->regularPosition->id,
            'department_id' => $this->otherDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->regularStaff2 = Staff::factory()->create([
            'position_id' => $this->regularPosition->id,
            'department_id' => $this->otherDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->unrelatedStaff = Staff::factory()->create([
            'position_id' => Position::factory()->for($this->company)->create([
                'name' => 'Entry Level Clerk',
                'code' => 'ELC',
                'type' => PositionType::CLERICAL,
            ])->id,
            'department_id' => $this->otherDepartment->id, // Non-HR department
            'office_id' => $this->targetOffice->id,
            'supervisor_id' => $this->supervisor->id, // Give them a supervisor so reporting level > 1
            'is_active' => true,
        ]);

        // Create transfers with different statuses using different staff
        $this->pendingTransfer = StaffTransfer::factory()->create([
            'staff_id' => $this->subordinate->id,
            'requested_by_id' => $this->subordinate->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
            'effective_date' => now()->addDays(7),
            'is_immediate' => false,
        ]);

        // create non-pending transfer
        $this->nonPendingTransfer = StaffTransfer::factory()->create([
            'staff_id' => $this->regularStaff2->id,
            'requested_by_id' => $this->regularStaff2->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::APPROVED,
            'effective_date' => now()->addDays(3),
            'is_immediate' => false,
        ]);

        // Create separate staff for approved transfer
        $approvedStaff = Staff::factory()->create([
            'position_id' => $this->regularPosition->id,
            'department_id' => $this->otherDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->approvedTransfer = StaffTransfer::withoutEvents(function () use ($approvedStaff) {
            return StaffTransfer::factory()->create([
                'staff_id' => $approvedStaff->id,
                'requested_by_id' => $approvedStaff->id,
                'from_office_id' => $this->sourceOffice->id,
                'to_office_id' => $this->targetOffice->id,
                'status' => StaffTransferStatus::APPROVED,
                'effective_date' => now()->subDays(1), // Past date so it's due for processing
                'is_immediate' => false,
            ]);
        });

        // Create separate staff for completed transfer
        $completedStaff = Staff::factory()->create([
            'position_id' => $this->regularPosition->id,
            'department_id' => $this->otherDepartment->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->completedTransfer = StaffTransfer::factory()->create([
            'staff_id' => $completedStaff->id,
            'requested_by_id' => $completedStaff->id,
            'from_office_id' => $this->sourceOffice->id,
            'to_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::COMPLETED,
            'effective_date' => now(),
            'is_immediate' => true,
        ]);
    }

    // ===== viewAny Tests =====

    #[Test]
    public function hr_staff_can_view_any_transfers(): void
    {
        $this->assertTrue($this->policy->viewAny($this->hrStaff));
    }

    #[Test]
    public function manager_can_view_any_transfers(): void
    {
        $this->assertTrue($this->policy->viewAny($this->supervisor));
    }

    #[Test]
    public function regular_staff_cannot_view_any_transfers(): void
    {
        $this->assertFalse($this->policy->viewAny($this->regularStaff));
    }

    // ===== view Tests =====

    #[Test]
    public function user_can_view_own_transfer(): void
    {
        $this->assertTrue($this->policy->view($this->subordinate, $this->pendingTransfer));
    }

    #[Test]
    public function user_can_view_transfer_they_requested(): void
    {
        $this->assertTrue($this->policy->view($this->subordinate, $this->pendingTransfer));
    }

    #[Test]
    public function supervisor_can_view_subordinate_transfer(): void
    {
        $this->assertTrue($this->policy->view($this->supervisor, $this->pendingTransfer));
    }

    #[Test]
    public function hr_staff_can_view_any_transfer(): void
    {
        $this->assertTrue($this->policy->view($this->hrStaff, $this->pendingTransfer));
    }

    #[Test]
    public function senior_manager_can_view_any_transfer(): void
    {
        $this->assertTrue($this->policy->view($this->seniorManager, $this->pendingTransfer));
    }

    #[Test]
    public function unrelated_staff_cannot_view_transfer(): void
    {
        $this->assertFalse($this->policy->view($this->unrelatedStaff, $this->pendingTransfer));
    }

    // ===== create Tests =====

    #[Test]
    public function active_staff_can_create_transfers(): void
    {
        $this->assertTrue($this->policy->create($this->regularStaff));
    }

    #[Test]
    public function inactive_staff_cannot_create_transfers(): void
    {
        $inactiveStaff = Staff::factory()->create(['is_active' => false]);
        $this->assertFalse($this->policy->create($inactiveStaff));
    }

    #[Test]
    public function staff_with_non_active_status_cannot_create_transfers(): void
    {
        // Create staff with resigned status directly without observer validation
        $resignedStaff = Staff::factory()->create([
            'status' => StaffStatus::RESIGNED,
            'is_active' => false,
        ]);
        $this->assertFalse($this->policy->create($resignedStaff));
    }

    // ===== createForOther Tests =====

    #[Test]
    public function hr_staff_can_create_transfer_for_anyone(): void
    {
        $this->assertTrue($this->policy->createForOther($this->hrStaff, $this->regularStaff));
    }

    #[Test]
    public function supervisor_can_create_transfer_for_subordinate(): void
    {
        $this->assertTrue($this->policy->createForOther($this->supervisor, $this->subordinate));
    }

    #[Test]
    public function senior_manager_can_create_transfer_for_staff_in_same_unit(): void
    {
        // Senior manager and regular staff are in same office and department
        $this->assertTrue($this->policy->createForOther($this->seniorManager, $this->regularStaff));
    }

    #[Test]
    public function unrelated_staff_cannot_create_transfer_for_others(): void
    {
        $this->assertFalse($this->policy->createForOther($this->unrelatedStaff, $this->regularStaff));
    }

    // ===== update Tests =====

    #[Test]
    public function requestor_can_update_pending_transfer(): void
    {
        $this->assertTrue($this->policy->update($this->subordinate, $this->pendingTransfer));
    }

    #[Test]
    public function hr_staff_can_update_non_final_transfer(): void
    {
        $this->assertTrue($this->policy->update($this->hrStaff, $this->pendingTransfer));
    }

    #[Test]
    public function hr_staff_cannot_update_final_status_transfer(): void
    {
        $this->assertFalse($this->policy->update($this->hrStaff, $this->completedTransfer));
    }

    #[Test]
    public function unrelated_staff_cannot_update_transfer(): void
    {
        $this->assertFalse($this->policy->update($this->unrelatedStaff, $this->pendingTransfer));
    }

    // ===== approve Tests =====

    #[Test]
    public function hr_staff_can_approve_pending_transfer(): void
    {
        $this->assertTrue($this->policy->approve($this->hrStaff, $this->pendingTransfer));
    }

    #[Test]
    public function senior_manager_can_approve_pending_transfer(): void
    {
        $this->assertTrue($this->policy->approve($this->seniorManager, $this->pendingTransfer));
    }

    #[Test]
    public function requestor_cannot_approve_own_transfer(): void
    {
        $this->assertFalse($this->policy->approve($this->subordinate, $this->pendingTransfer));
    }

    #[Test]
    public function cannot_approve_non_pending_transfer(): void
    {
        $this->assertFalse($this->policy->approve($this->hrStaff, $this->approvedTransfer));
    }

    // ===== reject Tests =====

    #[Test]
    public function hr_staff_can_reject_pending_transfer(): void
    {
        $this->assertTrue($this->policy->reject($this->hrStaff, $this->pendingTransfer));
    }

    #[Test]
    public function hr_staff_cannot_reject_non_pending_transfer()
    {
        $this->assertFalse($this->policy->reject($this->hrStaff, $this->nonPendingTransfer));
    }


    #[Test]
    public function senior_manager_can_reject_pending_transfer(): void
    {
        $this->assertTrue($this->policy->reject($this->seniorManager, $this->pendingTransfer));
    }

    #[Test]
    public function cannot_reject_non_pending_transfer(): void
    {
        $this->assertFalse($this->policy->reject($this->hrStaff, $this->approvedTransfer));
    }

    // ===== cancel Tests =====

    #[Test]
    public function requestor_can_cancel_own_transfer(): void
    {
        $this->assertTrue($this->policy->cancel($this->subordinate, $this->pendingTransfer));
    }

    #[Test]
    public function staff_can_cancel_their_own_transfer(): void
    {
        $this->assertTrue($this->policy->cancel($this->subordinate, $this->pendingTransfer));
    }

    #[Test]
    public function hr_staff_can_cancel_any_transfer(): void
    {
        $this->assertTrue($this->policy->cancel($this->hrStaff, $this->pendingTransfer));
    }

    #[Test]
    public function senior_manager_can_cancel_transfer_within_scope(): void
    {
        $this->assertTrue($this->policy->cancel($this->seniorManager, $this->pendingTransfer));
    }

    #[Test]
    public function cannot_cancel_non_cancellable_transfer(): void
    {
        $this->assertFalse($this->policy->cancel($this->hrStaff, $this->completedTransfer));
    }

    // ===== process Tests =====

    #[Test]
    public function hr_staff_can_process_approved_due_transfer(): void
    {
        $this->assertTrue($this->policy->process($this->hrStaff, $this->approvedTransfer));
    }

    #[Test]
    public function cannot_process_non_approved_transfer(): void
    {
        $this->assertFalse($this->policy->process($this->hrStaff, $this->pendingTransfer));
    }

    #[Test]
    public function cannot_process_not_due_transfer(): void
    {
        $futureTransfer = StaffTransfer::factory()->create([
            'staff_id' => $this->regularStaff->id,
            'status' => StaffTransferStatus::APPROVED,
            'effective_date' => now()->addDays(30), // Far future
        ]);
        $this->assertFalse($this->policy->process($this->hrStaff, $futureTransfer));
    }

    #[Test]
    public function non_hr_staff_cannot_process_transfer(): void
    {
        $this->assertFalse($this->policy->process($this->regularStaff, $this->approvedTransfer));
    }

    // ===== delete Tests =====

    #[Test]
    public function hr_staff_can_delete_non_completed_transfer(): void
    {
        $this->assertTrue($this->policy->delete($this->hrStaff, $this->pendingTransfer));
    }

    #[Test]
    public function hr_staff_cannot_delete_completed_transfer(): void
    {
        $this->assertFalse($this->policy->delete($this->hrStaff, $this->completedTransfer));
    }

    #[Test]
    public function non_hr_staff_cannot_delete_transfer(): void
    {
        $this->assertFalse($this->policy->delete($this->regularStaff, $this->pendingTransfer));
    }
}
