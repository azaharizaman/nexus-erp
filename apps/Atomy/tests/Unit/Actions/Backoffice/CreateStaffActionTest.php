<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Backoffice;

use PHPUnit\Framework\TestCase;
use Mockery;
use Nexus\Erp\Actions\Backoffice\CreateStaffAction;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\Position;
use Nexus\Backoffice\Enums\StaffStatus;
use Illuminate\Validation\ValidationException;

class CreateStaffActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_staff_successfully()
    {
        // Mock related models
        $company = (object) ['id' => 1, 'name' => 'Test Company'];
        $office = (object) ['id' => 1, 'company_id' => 1, 'name' => 'Main Office'];
        $department = (object) ['id' => 1, 'office_id' => 1, 'name' => 'IT Department'];
        $position = (object) ['id' => 1, 'department_id' => 1, 'title' => 'Developer'];

        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('find')->with(1)->andReturn($company);

        $officeMock = Mockery::mock('alias:' . Office::class);
        $officeMock->shouldReceive('find')->with(1)->andReturn($office);

        $departmentMock = Mockery::mock('alias:' . Department::class);
        $departmentMock->shouldReceive('find')->with(1)->andReturn($department);

        $positionMock = Mockery::mock('alias:' . Position::class);
        $positionMock->shouldReceive('find')->with(1)->andReturn($position);

        // Mock Staff model
        $staffMock = Mockery::mock('alias:' . Staff::class);
        $staffMock->shouldReceive('where')
            ->with('email', 'test@example.com')
            ->andReturnSelf();
        $staffMock->shouldReceive('exists')
            ->andReturn(false);
        
        $staffMock->shouldReceive('where')
            ->with('company_id', 1)
            ->andReturnSelf();
        $staffMock->shouldReceive('count')
            ->andReturn(5);

        $createdStaff = (object) [
            'id' => 1,
            'employee_id' => 'EMP-001-006',
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'hire_date' => now(),
            'status' => StaffStatus::ACTIVE,
            'company_id' => 1,
            'office_id' => 1,
            'department_id' => 1,
            'position_id' => 1,
            'supervisor_id' => null,
            'is_active' => true,
        ];

        $staffMock->shouldReceive('create')
            ->once()
            ->andReturn($createdStaff);

        $action = new CreateStaffAction();
        $result = $action->execute([
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'hire_date' => now(),
            'company_id' => 1,
            'office_id' => 1,
            'department_id' => 1,
            'position_id' => 1,
            'supervisor_id' => null,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Staff created successfully', $result['message']);
        $this->assertEquals(1, $result['data']['id']);
        $this->assertEquals('John Doe', $result['data']['name']);
    }

    public function test_validates_required_fields()
    {
        $action = new CreateStaffAction();
        
        $this->expectException(ValidationException::class);
        
        $action->execute([
            'email' => 'test@example.com',
            'phone' => '+1234567890',
        ]);
    }

    public function test_validates_unique_email()
    {
        $staffMock = Mockery::mock('alias:' . Staff::class);
        $staffMock->shouldReceive('where')
            ->with('email', 'existing@example.com')
            ->andReturnSelf();
        $staffMock->shouldReceive('exists')
            ->andReturn(true);

        $action = new CreateStaffAction();
        
        $this->expectException(ValidationException::class);
        
        $action->execute([
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'phone' => '+1234567890',
            'hire_date' => now(),
            'company_id' => 1,
            'office_id' => 1,
            'department_id' => 1,
            'position_id' => 1,
        ]);
    }

    public function test_validates_organizational_hierarchy()
    {
        $company = (object) ['id' => 1, 'name' => 'Test Company'];
        $office = (object) ['id' => 1, 'company_id' => 2, 'name' => 'Other Office']; // Different company
        
        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('find')->with(1)->andReturn($company);

        $officeMock = Mockery::mock('alias:' . Office::class);
        $officeMock->shouldReceive('find')->with(1)->andReturn($office);

        $action = new CreateStaffAction();
        
        $this->expectException(ValidationException::class);
        
        $action->execute([
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'hire_date' => now(),
            'company_id' => 1,
            'office_id' => 1,
            'department_id' => 1,
            'position_id' => 1,
        ]);
    }

    public function test_generates_employee_id_automatically()
    {
        // Mock related models
        $company = (object) ['id' => 1, 'name' => 'Test Company'];
        $office = (object) ['id' => 1, 'company_id' => 1, 'name' => 'Main Office'];
        $department = (object) ['id' => 1, 'office_id' => 1, 'name' => 'IT Department'];
        $position = (object) ['id' => 1, 'department_id' => 1, 'title' => 'Developer'];

        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('find')->with(1)->andReturn($company);

        $officeMock = Mockery::mock('alias:' . Office::class);
        $officeMock->shouldReceive('find')->with(1)->andReturn($office);

        $departmentMock = Mockery::mock('alias:' . Department::class);
        $departmentMock->shouldReceive('find')->with(1)->andReturn($department);

        $positionMock = Mockery::mock('alias:' . Position::class);
        $positionMock->shouldReceive('find')->with(1)->andReturn($position);

        $staffMock = Mockery::mock('alias:' . Staff::class);
        $staffMock->shouldReceive('where')
            ->with('email', 'test@example.com')
            ->andReturnSelf();
        $staffMock->shouldReceive('exists')
            ->andReturn(false);
        
        $staffMock->shouldReceive('where')
            ->with('company_id', 1)
            ->andReturnSelf();
        $staffMock->shouldReceive('count')
            ->andReturn(0); // First employee

        $staffMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['employee_id'] === 'EMP-001-001'; // First employee ID
            }))
            ->andReturn((object) ['id' => 1, 'employee_id' => 'EMP-001-001']);

        $action = new CreateStaffAction();
        $result = $action->execute([
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'hire_date' => now(),
            'company_id' => 1,
            'office_id' => 1,
            'department_id' => 1,
            'position_id' => 1,
        ]);

        $this->assertTrue($result['success']);
    }
}