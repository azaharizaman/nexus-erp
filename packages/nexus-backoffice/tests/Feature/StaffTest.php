<?php

namespace Nexus\Backoffice\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\QueryException;
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

#[CoversClass(Staff::class)]
#[CoversClass(Company::class)]
#[CoversClass(Office::class)]
#[CoversClass(Department::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(StaffStatus::class)]
#[CoversClass(OfficeType::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
class StaffTest extends TestCase
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
    public function it_can_create_staff()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backoffice_staff', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->assertEquals('John Doe', $staff->full_name);
        $this->assertEquals('john@example.com', $staff->email);
        $this->assertEquals($structure['department']->id, $staff->department_id);
    }

    #[Test]
    public function it_belongs_to_department()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'Jane', 'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'employee_id' => 'EMP002',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->assertEquals($structure['department']->id, $staff->department->id);
        $this->assertEquals('IT Department', $staff->department->name);
    }

    #[Test]
    public function it_belongs_to_office()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'Alice', 'last_name' => 'Johnson',
            'email' => 'alice@example.com',
            'employee_id' => 'EMP003',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->assertEquals($structure['office']->id, $staff->office->id);
        $this->assertEquals('Main Office', $staff->office->name);
        $this->assertEquals('Headquarters', $staff->office->officeTypes->first()->name);
    }

    #[Test]
    public function it_can_have_supervisor_relationship()
    {
        $structure = $this->createTestStructure();

        $supervisor = Staff::factory()->create([
            'first_name' => 'Senior', 'last_name' => 'Manager',
            'email' => 'manager@example.com',
            'employee_id' => 'MGR001',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $subordinate = Staff::factory()->create([
            'first_name' => 'Junior', 'last_name' => 'Developer',
            'email' => 'junior@example.com',
            'employee_id' => 'JUN001',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'supervisor_id' => $supervisor->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->assertEquals($supervisor->id, $subordinate->supervisor_id);
        $this->assertEquals($supervisor->id, $subordinate->supervisor->id);
        $this->assertTrue($supervisor->subordinates->contains($subordinate));
    }

    #[Test]
    public function it_can_scope_by_status()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'first_name' => 'Active', 'last_name' => 'Employee',
            'email' => 'active@example.com',
            'employee_id' => 'ACT001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $terminatedStaff = Staff::factory()->create([
            'first_name' => 'Terminated', 'last_name' => 'Employee',
            'email' => 'terminated@example.com',
            'employee_id' => 'TER001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::TERMINATED,
            'is_active' => false,
        ]);

        $activeStaffList = Staff::byStatus(StaffStatus::ACTIVE)->get();
        $terminatedStaffList = Staff::byStatus(StaffStatus::TERMINATED)->get();

        $this->assertCount(1, $activeStaffList);
        $this->assertCount(1, $terminatedStaffList);
        $this->assertTrue($activeStaffList->contains('id', $activeStaff->id));
        $this->assertTrue($terminatedStaffList->contains('id', $terminatedStaff->id));
    }

    #[Test]
    public function it_can_scope_by_department()
    {
        $structure = $this->createTestStructure();

        // Create second department
        $hrDepartment = Department::factory()->create([
            'name' => 'HR Department',
            'code' => 'HR',
            'company_id' => $structure['company']->id,
            'is_active' => true,
        ]);

        $itStaff = Staff::factory()->create([
            'first_name' => 'IT', 'last_name' => 'Staff',
            'email' => 'it@example.com',
            'employee_id' => 'IT001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $hrStaff = Staff::factory()->create([
            'first_name' => 'HR', 'last_name' => 'Staff',
            'email' => 'hr@example.com',
            'employee_id' => 'HR001',
            'department_id' => $hrDepartment->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $itDepartmentStaff = Staff::inDepartment($structure['department']->id)->get();
        
        $this->assertCount(1, $itDepartmentStaff);
        $this->assertTrue($itDepartmentStaff->contains('id', $itStaff->id));
        $this->assertFalse($itDepartmentStaff->contains('id', $hrStaff->id));
    }

    #[Test]
    public function it_can_scope_active_staff()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'first_name' => 'Active', 'last_name' => 'Staff',
            'email' => 'active@example.com',
            'employee_id' => 'ACT001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $inactiveStaff = Staff::factory()->create([
            'first_name' => 'Inactive', 'last_name' => 'Staff',
            'email' => 'inactive@example.com',
            'employee_id' => 'INA001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::INACTIVE,
            'is_active' => false,
        ]);

        $activeStaffList = Staff::active()->get();
        
        $this->assertCount(1, $activeStaffList);
        $this->assertTrue($activeStaffList->contains('id', $activeStaff->id));
        $this->assertFalse($activeStaffList->contains('id', $inactiveStaff->id));
    }

    #[Test]
    public function it_can_get_staff_hierarchy()
    {
        $structure = $this->createTestStructure();

        $manager = Staff::factory()->create([
            'first_name' => 'Department', 'last_name' => 'Manager',
            'email' => 'manager@example.com',
            'employee_id' => 'MGR001',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $teamLead = Staff::factory()->create([
            'first_name' => 'Team', 'last_name' => 'Lead',
            'email' => 'lead@example.com',
            'employee_id' => 'LEAD001',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'supervisor_id' => $manager->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $developer = Staff::factory()->create([
            'first_name' => 'Developer', 'last_name' => 'Developer',
            'email' => 'dev@example.com',
            'employee_id' => 'DEV001',
            'office_id' => $structure['office']->id,
            'department_id' => $structure['department']->id,
            'supervisor_id' => $teamLead->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        // Test supervisor relationships
        $this->assertEquals($manager->id, $teamLead->supervisor->id);
        $this->assertEquals($teamLead->id, $developer->supervisor->id);

        // Test subordinate relationships
        $this->assertTrue($manager->subordinates->contains($teamLead));
        $this->assertTrue($teamLead->subordinates->contains($developer));
        $this->assertCount(1, $manager->subordinates);
        $this->assertCount(1, $teamLead->subordinates);
        $this->assertCount(0, $developer->subordinates);
    }

    #[Test]
    public function it_validates_unique_employee_id()
    {
        $structure = $this->createTestStructure();

        Staff::factory()->create([
            'first_name' => 'First', 'last_name' => 'Employee',
            'email' => 'first@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Staff::factory()->create([
            'first_name' => 'Second', 'last_name' => 'Employee',
            'email' => 'second@example.com',
            'employee_id' => 'EMP001', // Same employee ID
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_validates_unique_email()
    {
        $structure = $this->createTestStructure();

        Staff::factory()->create([
            'first_name' => 'First', 'last_name' => 'Employee',
            'email' => 'employee@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Staff::factory()->create([
            'first_name' => 'Second', 'last_name' => 'Employee',
            'email' => 'employee@example.com', // Same email
            'employee_id' => 'EMP002',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_requires_first_name_last_name_and_email()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Staff::factory()->create([
            'employee_id' => 'EMP001',
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'status' => StaffStatus::ACTIVE,
        ]);
    }

    #[Test]
    public function it_defaults_to_active_status()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'first_name' => 'Test', 'last_name' => 'Employee',
            'email' => 'test@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
        ]);

        $this->assertEquals(StaffStatus::ACTIVE->value, $staff->status->value);
        $this->assertTrue($staff->is_active);
    }
}