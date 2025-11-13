<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\QueryException;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use Nexus\Backoffice\Models\Position;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Enums\PositionType;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Observers\StaffObserver;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;
use Nexus\Backoffice\Observers\DepartmentObserver;
use Nexus\Backoffice\Database\Factories\PositionFactory;

#[CoversClass(Position::class)]
#[CoversClass(PositionType::class)]
#[CoversClass(Company::class)]
#[CoversClass(Office::class)]
#[CoversClass(Staff::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(DepartmentObserver::class)]
#[CoversClass(StaffObserver::class)]
#[CoversClass(Department::class)]
#[CoversClass(PositionFactory::class)]
#[CoversClass(BackOfficeServiceProvider::class)]

class PositionTest extends TestCase
{
    protected Company $company;
    protected Department $department;
    protected Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->office = Office::factory()->for($this->company)->create();
        $this->department = Department::factory()->for($this->company)->create();
    }

    #[Test]
    public function it_can_create_a_position(): void
    {
        $position = Position::factory()->for($this->company)->create([
            'name' => 'Senior Manager',
            'code' => 'MGR-001',
            'gred' => 'M52',
            'type' => PositionType::MANAGEMENT,
            'description' => 'Senior management position',
        ]);

        $this->assertDatabaseHas('backoffice_positions', [
            'id' => $position->id,
            'company_id' => $this->company->id,
            'name' => 'Senior Manager',
            'code' => 'MGR-001',
            'gred' => 'M52',
            'type' => PositionType::MANAGEMENT->value,
        ]);
    }

    #[Test]
    public function it_belongs_to_company(): void
    {
        $position = Position::factory()->for($this->company)->create();

        $this->assertInstanceOf(Company::class, $position->company);
        $this->assertEquals($this->company->id, $position->company_id);
    }

    #[Test]
    public function it_can_have_default_department(): void
    {
        $position = Position::factory()
            ->for($this->company)
            ->withDepartment($this->department)
            ->create();

        $this->assertInstanceOf(Department::class, $position->department);
        $this->assertEquals($this->department->id, $position->department_id);
        $this->assertTrue($position->hasDefaultDepartment());
    }

    #[Test]
    public function it_can_have_no_default_department(): void
    {
        $position = Position::factory()->for($this->company)->create([
            'department_id' => null,
        ]);

        $this->assertNull($position->department_id);
        $this->assertFalse($position->hasDefaultDepartment());
    }

    #[Test]
    public function it_has_staff_relationship(): void
    {
        $position = Position::factory()->for($this->company)->create();
        
        $staff1 = Staff::factory()->for($this->office)->create([
            'position_id' => $position->id,
        ]);
        $staff2 = Staff::factory()->for($this->office)->create([
            'position_id' => $position->id,
        ]);

        $this->assertCount(2, $position->staff);
        $this->assertTrue($position->staff->contains($staff1));
        $this->assertTrue($position->staff->contains($staff2));
    }

    #[Test]
    public function it_can_get_active_staff_only(): void
    {
        $position = Position::factory()->for($this->company)->create();
        
        $activeStaff = Staff::factory()->for($this->office)->active()->create([
            'position_id' => $position->id,
        ]);
        $inactiveStaff = Staff::factory()->for($this->office)->inactive()->create([
            'position_id' => $position->id,
        ]);

        $this->assertCount(1, $position->activeStaff);
        $this->assertTrue($position->activeStaff->contains($activeStaff));
        $this->assertFalse($position->activeStaff->contains($inactiveStaff));
    }

    #[Test]
    public function it_can_scope_active_positions(): void
    {
        Position::factory()->for($this->company)->active()->create();
        Position::factory()->for($this->company)->active()->create();
        Position::factory()->for($this->company)->inactive()->create();

        $activePositions = Position::active()->get();

        $this->assertCount(2, $activePositions);
        $this->assertTrue($activePositions->every(fn ($p) => $p->is_active));
    }

    #[Test]
    public function it_can_scope_by_company(): void
    {
        $company2 = Company::factory()->create();
        
        Position::factory()->for($this->company)->count(3)->create();
        Position::factory()->for($company2)->count(2)->create();

        $companyPositions = Position::byCompany($this->company)->get();

        $this->assertCount(3, $companyPositions);
        $this->assertTrue($companyPositions->every(fn ($p) => $p->company_id === $this->company->id));
    }

    #[Test]
    public function it_can_scope_by_department(): void
    {
        $department2 = Department::factory()->for($this->company)->create();
        
        Position::factory()->for($this->company)->withDepartment($this->department)->count(2)->create();
        Position::factory()->for($this->company)->withDepartment($department2)->create();

        $deptPositions = Position::byDepartment($this->department)->get();

        $this->assertCount(2, $deptPositions);
        $this->assertTrue($deptPositions->every(fn ($p) => $p->department_id === $this->department->id));
    }

    #[Test]
    public function it_can_scope_by_type(): void
    {
        Position::factory()->for($this->company)->management()->create();
        Position::factory()->for($this->company)->executive()->create();
        Position::factory()->for($this->company)->clerical()->create();

        $managementPositions = Position::byType(PositionType::MANAGEMENT)->get();

        $this->assertCount(1, $managementPositions);
        $this->assertEquals(PositionType::MANAGEMENT, $managementPositions->first()->type);
    }

    #[Test]
    public function it_can_scope_management_positions(): void
    {
        Position::factory()->for($this->company)->cLevel()->create();
        Position::factory()->for($this->company)->seniorManagement()->create();
        Position::factory()->for($this->company)->management()->create();
        Position::factory()->for($this->company)->executive()->create();

        $managementPositions = Position::management()->get();

        $this->assertCount(3, $managementPositions);
        $this->assertTrue($managementPositions->every(fn ($p) => $p->type->isManagement()));
    }

    #[Test]
    public function it_can_scope_executive_positions(): void
    {
        Position::factory()->for($this->company)->seniorExecutive()->create();
        Position::factory()->for($this->company)->executive()->create();
        Position::factory()->for($this->company)->juniorExecutive()->create();
        Position::factory()->for($this->company)->management()->create();

        $executivePositions = Position::executive()->get();

        $this->assertCount(3, $executivePositions);
        $this->assertTrue($executivePositions->every(fn ($p) => $p->type->isExecutive()));
    }

    #[Test]
    public function it_can_check_if_management_level(): void
    {
        $cLevel = Position::factory()->for($this->company)->cLevel()->create();
        $executive = Position::factory()->for($this->company)->executive()->create();

        $this->assertTrue($cLevel->isManagement());
        $this->assertFalse($executive->isManagement());
        $this->assertTrue($executive->isExecutive());
    }

    #[Test]
    public function it_can_get_hierarchical_level(): void
    {
        $cLevel = Position::factory()->for($this->company)->cLevel()->create();
        $management = Position::factory()->for($this->company)->management()->create();
        $assistant = Position::factory()->for($this->company)->assistant()->create();

        $this->assertEquals(1, $cLevel->getLevel());
        $this->assertEquals(3, $management->getLevel());
        $this->assertEquals(10, $assistant->getLevel());
    }

    #[Test]
    public function it_can_count_staff_in_position(): void
    {
        $position = Position::factory()->for($this->company)->create();
        
        Staff::factory()->for($this->office)->count(3)->create([
            'position_id' => $position->id,
        ]);

        $this->assertEquals(3, $position->getStaffCount());
        $this->assertEquals(3, $position->getActiveStaffCount());
    }

    #[Test]
    public function it_can_count_active_staff_separately(): void
    {
        $position = Position::factory()->for($this->company)->create();
        
        Staff::factory()->for($this->office)->active()->count(2)->create([
            'position_id' => $position->id,
        ]);
        Staff::factory()->for($this->office)->inactive()->create([
            'position_id' => $position->id,
        ]);

        $this->assertEquals(3, $position->getStaffCount());
        $this->assertEquals(2, $position->getActiveStaffCount());
    }

    #[Test]
    public function it_requires_company_name_code_and_type(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Position::create([
            'description' => 'Test position',
        ]);
    }

    #[Test]
    public function it_requires_unique_code(): void
    {
        Position::factory()->for($this->company)->create([
            'code' => 'MGR-001',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Position::factory()->for($this->company)->create([
            'code' => 'MGR-001',
        ]);
    }

    #[Test]
    public function it_defaults_to_active(): void
    {
        $position = Position::factory()->for($this->company)->create();

        $this->assertTrue($position->is_active);
    }

    #[Test]
    public function staff_inherits_position_department_when_no_department_set(): void
    {
        // Create position with default department
        $position = Position::factory()
            ->for($this->company)
            ->withDepartment($this->department)
            ->create();

        // Create staff with position but no department
        $staff = Staff::factory()->for($this->office)->create([
            'position_id' => $position->id,
            'department_id' => null,
        ]);

        // Staff should get effective department from position
        $this->assertNull($staff->department_id);
        $this->assertEquals($this->department->id, $staff->getEffectiveDepartmentId());
        $this->assertEquals($this->department->id, $staff->getEffectiveDepartment()->id);
    }

    #[Test]
    public function staff_department_takes_precedence_over_position_department(): void
    {
        $positionDept = Department::factory()->for($this->company)->create([
            'name' => 'Position Department',
        ]);
        $staffDept = Department::factory()->for($this->company)->create([
            'name' => 'Staff Department',
        ]);

        // Create position with default department
        $position = Position::factory()
            ->for($this->company)
            ->withDepartment($positionDept)
            ->create();

        // Create staff with position AND own department
        $staff = Staff::factory()->for($this->office)->create([
            'position_id' => $position->id,
            'department_id' => $staffDept->id,
        ]);

        // Staff's own department should take precedence
        $this->assertEquals($staffDept->id, $staff->department_id);
        $this->assertEquals($staffDept->id, $staff->getEffectiveDepartmentId());
        $this->assertEquals($staffDept->id, $staff->getEffectiveDepartment()->id);
    }

    #[Test]
    public function staff_without_position_or_department_has_no_effective_department(): void
    {
        $staff = Staff::factory()->for($this->office)->create([
            'position_id' => null,
            'department_id' => null,
        ]);

        $this->assertNull($staff->getEffectiveDepartmentId());
        $this->assertNull($staff->getEffectiveDepartment());
    }

    #[Test]
    public function staff_with_position_without_default_department_has_no_effective_department(): void
    {
        // Create position without default department
        $position = Position::factory()->for($this->company)->create([
            'department_id' => null,
        ]);

        $staff = Staff::factory()->for($this->office)->create([
            'position_id' => $position->id,
            'department_id' => null,
        ]);

        $this->assertNull($staff->getEffectiveDepartmentId());
        $this->assertNull($staff->getEffectiveDepartment());
    }
}
