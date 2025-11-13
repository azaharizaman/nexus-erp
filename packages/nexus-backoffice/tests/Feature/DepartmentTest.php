<?php

namespace Nexus\Backoffice\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\OfficeType;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Observers\DepartmentObserver;

#[CoversClass(Department::class)]
#[CoversClass(Company::class)]
#[CoversClass(Office::class)]
#[CoversClass(OfficeType::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
#[UsesClass(Department::class)]
class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_department()
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

        $department = Department::factory()->create([
            'name' => 'IT Department',
            'code' => 'IT',
            'description' => 'Information Technology Department',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backoffice_departments', [
            'name' => 'IT Department',
            'code' => 'IT',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->assertEquals('IT Department', $department->name);
        $this->assertEquals($company->id, $department->company_id);
    }

    #[Test]
    public function it_belongs_to_company()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $department = Department::factory()->create([
            'name' => 'HR Department',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->assertEquals($company->id, $department->company->id);
        $this->assertEquals('Test Company', $department->company->name);
    }

    #[Test]
    public function it_can_create_department_hierarchy()
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

        $parentDepartment = Department::factory()->create([
            'name' => 'Technology',
            'code' => 'TECH',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $childDepartment = Department::factory()->create([
            'name' => 'Software Development',
            'code' => 'SOFTDEV',
            'company_id' => $company->id,
            'parent_department_id' => $parentDepartment->id,
            'is_active' => true,
        ]);

        $this->assertEquals($parentDepartment->id, $childDepartment->parent_department_id);
        $this->assertTrue($parentDepartment->childDepartments->contains($childDepartment));
        $this->assertEquals($parentDepartment->id, $childDepartment->parentDepartment->id);
    }

    #[Test]
    public function it_can_get_all_departments_in_hierarchy()
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

        $rootDepartment = Department::factory()->create([
            'name' => 'Technology',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $child1 = Department::factory()->create([
            'name' => 'Software Development',
            'company_id' => $company->id,
            'parent_department_id' => $rootDepartment->id,
            'is_active' => true,
        ]);

        $child2 = Department::factory()->create([
            'name' => 'Quality Assurance',
            'company_id' => $company->id,
            'parent_department_id' => $rootDepartment->id,
            'is_active' => true,
        ]);

        $grandchild = Department::factory()->create([
            'name' => 'Frontend Development',
            'company_id' => $company->id,
            'parent_department_id' => $child1->id,
            'is_active' => true,
        ]);

        $descendants = $rootDepartment->allChildDepartments();
        
        $this->assertCount(3, $descendants);
        $this->assertTrue($descendants->contains('id', $child1->id));
        $this->assertTrue($descendants->contains('id', $child2->id));
        $this->assertTrue($descendants->contains('id', $grandchild->id));
    }

    #[Test]
    public function it_can_scope_by_company()
    {
        $company1 = Company::factory()->create([
            'name' => 'Company 1',
            'is_active' => true,
        ]);

        $company2 = Company::factory()->create([
            'name' => 'Company 2',
            'is_active' => true,
        ]);

        $dept1 = Department::factory()->create([
            'name' => 'IT Department',
            'company_id' => $company1->id,
            'is_active' => true,
        ]);

        $dept2 = Department::factory()->create([
            'name' => 'HR Department',
            'company_id' => $company2->id,
            'is_active' => true,
        ]);

        $company1Departments = Department::forCompany($company1->id)->get();
        
        $this->assertCount(1, $company1Departments);
        $this->assertTrue($company1Departments->contains('id', $dept1->id));
        $this->assertFalse($company1Departments->contains('id', $dept2->id));
    }

    #[Test]
    public function it_can_scope_active_departments()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        $activeDepartment = Department::factory()->create([
            'name' => 'Active Department',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $inactiveDepartment = Department::factory()->create([
            'name' => 'Inactive Department',
            'company_id' => $company->id,
            'is_active' => false,
        ]);

        $activeDepartments = Department::active()->get();
        
        $this->assertCount(1, $activeDepartments);
        $this->assertTrue($activeDepartments->contains('id', $activeDepartment->id));
        $this->assertFalse($activeDepartments->contains('id', $inactiveDepartment->id));
    }

    #[Test]
    public function it_requires_name_and_company()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Department::factory()->create([
            'name' => null,
            'company_id' => null,
            'code' => 'TEST',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_defaults_to_active()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        $department = Department::factory()->create([
            'name' => 'Test Department',
            'company_id' => $company->id,
        ]);

        $this->assertTrue($department->is_active);
    }
}