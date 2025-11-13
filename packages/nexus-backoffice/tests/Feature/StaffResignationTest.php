<?php

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
use Nexus\Backoffice\Models\OfficeType;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\Observers\StaffObserver;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Observers\DepartmentObserver;
use Nexus\Backoffice\Exceptions\InvalidResignationException;

#[CoversClass(Company::class)]
#[CoversClass(Office::class)]
#[CoversClass(Department::class)]
#[CoversClass(OfficeType::class)]
#[CoversClass(Staff::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(StaffStatus::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
#[CoversClass(InvalidResignationException::class)]
class StaffResignationTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestStructure()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Main Office',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Attach office type using many-to-many relationship
        $office->officeTypes()->attach($officeType);

        $department = Department::factory()->create([
            'name' => 'IT Department',
            'code' => 'IT',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        return compact('company', 'office', 'department');
    }

    #[Test]
    public function it_can_schedule_staff_resignation()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'John', 'last_name' => 'Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $resignationDate = Carbon::now()->addDays(30);
        $resignationReason = 'Better opportunity elsewhere';

        $staff->scheduleResignation($resignationDate, $resignationReason);

        $staff->refresh();
        $this->assertEquals($resignationDate->toDateString(), $staff->resignation_date->toDateString());
        $this->assertEquals($resignationReason, $staff->resignation_reason);
        $this->assertEquals(StaffStatus::ACTIVE, $staff->status);
        $this->assertNull($staff->resigned_at);
        $this->assertTrue($staff->hasPendingResignation());
    }

    #[Test]
    public function it_can_process_staff_resignation()
    {
        $structure = $this->createTestStructure();

        // Create staff without resignation_date to avoid observer validation
        $staff = Staff::factory()->create([
            'first_name' => 'Jane', 'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'employee_id' => 'EMP002',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Set resignation date using updateQuietly to bypass observer
        $staff->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Personal reasons',
            'resigned_at' => null,
        ]);

        $staff->processResignation();

        $freshStaff = $staff->fresh();
        $this->assertEquals(StaffStatus::RESIGNED, $freshStaff->status);
        $this->assertNotNull($freshStaff->resigned_at);
        $this->assertFalse($freshStaff->is_active);
        $this->assertTrue($freshStaff->isResigned());
    }

    #[Test]
    public function it_can_cancel_scheduled_resignation()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'Bob', 'last_name' => 'Wilson',
            'email' => 'bob@example.com',
            'employee_id' => 'EMP003',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(15),
            'resignation_reason' => 'Counter offer accepted',
            'is_active' => true,
        ]);

        $this->assertTrue($staff->hasPendingResignation());

        $staff->cancelResignation();

        $freshStaff = $staff->fresh();
        $this->assertNull($freshStaff->resignation_date);
        $this->assertNull($freshStaff->resignation_reason);
        $this->assertFalse($freshStaff->hasPendingResignation());
    }

    #[Test]
    public function it_can_check_if_resignation_is_due()
    {
        $structure = $this->createTestStructure();

        // Staff with resignation due yesterday - create without date then updateQuietly
        $staffPastDue = Staff::factory()->create([
            'first_name' => 'Past', 'last_name' => 'Due Staff',
            'email' => 'pastdue@example.com',
            'employee_id' => 'EMP004',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        $staffPastDue->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(1),
            'resigned_at' => null,
        ]);

        // Staff with resignation due in future
        $staffFuture = Staff::factory()->create([
            'first_name' => 'Future', 'last_name' => 'Staff',
            'email' => 'future@example.com',
            'employee_id' => 'EMP005',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(5),
            'is_active' => true,
        ]);

        $this->assertTrue($staffPastDue->isResignationDue());
        $this->assertFalse($staffFuture->isResignationDue());
    }

    #[Test]
    public function it_can_get_days_until_resignation()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'Future', 'last_name' => 'Resignation',
            'email' => 'future@example.com',
            'employee_id' => 'EMP006',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(10)->startOfDay(),
            'is_active' => true,
        ]);

        $daysUntil = $staff->getDaysUntilResignation();
        $this->assertEquals(10, $daysUntil);
    }

    #[Test]
    public function it_can_scope_staff_with_pending_resignations()
    {
        $structure = $this->createTestStructure();

        $activStaff = Staff::factory()->create([
            'first_name' => 'Active', 'last_name' => 'Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP007',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $pendingResignationStaff = Staff::factory()->create([
            'first_name' => 'Pending', 'last_name' => 'Resignation',
            'email' => 'pending@example.com',
            'employee_id' => 'EMP008',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(5),
            'is_active' => true,
        ]);

        // Create resigned staff without past date, then updateQuietly
        $resignedStaff = Staff::factory()->create([
            'first_name' => 'Already', 'last_name' => 'Resigned',
            'email' => 'resigned@example.com',
            'employee_id' => 'EMP009',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::RESIGNED,
            'is_active' => false,
        ]);
        
        $resignedStaff->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(5),
            'resigned_at' => Carbon::now()->subDays(5),
        ]);

        $pendingStaff = Staff::pendingResignation()->get();
        
        $this->assertCount(1, $pendingStaff);
        $this->assertTrue($pendingStaff->contains('id', $pendingResignationStaff->id));
        $this->assertFalse($pendingStaff->contains('id', $activStaff->id));
        $this->assertFalse($pendingStaff->contains('id', $resignedStaff->id));
    }

    #[Test]
    public function it_can_scope_resigned_staff()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'first_name' => 'Active', 'last_name' => 'Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP010',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $resignedStaff = Staff::factory()->create([
            'first_name' => 'Resigned', 'last_name' => 'Staff',
            'email' => 'resigned@example.com',
            'employee_id' => 'EMP011',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::RESIGNED,
            'resigned_at' => Carbon::now()->subDays(5),
            'is_active' => false,
        ]);

        $resigned = Staff::resigned()->get();
        
        $this->assertCount(1, $resigned);
        $this->assertTrue($resigned->contains('id', $resignedStaff->id));
        $this->assertFalse($resigned->contains('id', $activeStaff->id));
    }

    #[Test]
    public function it_validates_resignation_date_not_in_past_for_new_staff()
    {
        $structure = $this->createTestStructure();

        $this->expectException(InvalidResignationException::class);
        $this->expectExceptionMessage('Resignation date cannot be in the past for new staff entries.');

        Staff::factory()->create([
            'first_name' => 'Invalid', 'last_name' => 'Staff',
            'email' => 'invalid@example.com',
            'employee_id' => 'EMP012',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_automatically_sets_resigned_at_when_status_changes_to_resigned()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'Auto', 'last_name' => 'Resigned',
            'email' => 'auto@example.com',
            'employee_id' => 'EMP013',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $staff->update(['status' => StaffStatus::RESIGNED]);

        $freshStaff = $staff->fresh();
        $this->assertEquals(StaffStatus::RESIGNED, $freshStaff->status);
        $this->assertNotNull($freshStaff->resigned_at);
        $this->assertFalse($freshStaff->is_active);
    }

    #[Test]
    public function it_clears_resignation_data_when_reactivating_resigned_staff()
    {
        $structure = $this->createTestStructure();

        // Create active staff first
        $staff = Staff::factory()->create([
            'first_name' => 'Reactivated', 'last_name' => 'Staff',
            'email' => 'reactivated@example.com',
            'employee_id' => 'EMP014',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
        
        // Schedule resignation in future
        $staff->scheduleResignation(
            Carbon::now()->addDays(5),
            'Previous job'
        );
        
        // Process the resignation to make staff resigned
        $staff->updateQuietly([
            'resignation_date' => Carbon::now()->subDays(10),
            'resigned_at' => Carbon::now()->subDays(10),
        ]);
        $staff->refresh();
        $staff->update(['status' => StaffStatus::RESIGNED]);
        $staff->refresh();
        
        // Verify staff is resigned with resignation data
        $this->assertEquals(StaffStatus::RESIGNED, $staff->status);
        $this->assertNotNull($staff->resignation_date);
        $this->assertNotNull($staff->resigned_at);
        
        // Now reactivate the staff
        $staff->update(['status' => StaffStatus::ACTIVE]);

        $freshStaff = $staff->fresh();
        $this->assertEquals(StaffStatus::ACTIVE, $freshStaff->status);
        $this->assertNull($freshStaff->resignation_date);
        $this->assertNull($freshStaff->resignation_reason);
        $this->assertNull($freshStaff->resigned_at);
        $this->assertTrue($freshStaff->is_active);
    }

    #[Test]
    public function it_can_filter_staff_by_status()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'first_name' => 'Active', 'last_name' => 'Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP015',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $resignedStaff = Staff::factory()->create([
            'first_name' => 'Resigned', 'last_name' => 'Staff',
            'email' => 'resigned@example.com',
            'employee_id' => 'EMP016',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::RESIGNED,
            'is_active' => false,
        ]);

        $activeList = Staff::byStatus(StaffStatus::ACTIVE)->get();
        $resignedList = Staff::byStatus(StaffStatus::RESIGNED)->get();

        $this->assertCount(1, $activeList);
        $this->assertCount(1, $resignedList);
        $this->assertTrue($activeList->contains('id', $activeStaff->id));
        $this->assertTrue($resignedList->contains('id', $resignedStaff->id));
    }
}