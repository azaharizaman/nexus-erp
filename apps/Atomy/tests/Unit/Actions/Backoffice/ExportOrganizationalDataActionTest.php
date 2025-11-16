<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Backoffice;

use PHPUnit\Framework\TestCase;
use Mockery;
use Nexus\Erp\Actions\Backoffice\ExportOrganizationalDataAction;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Enums\StaffStatus;
use Illuminate\Database\Eloquent\Collection;

class ExportOrganizationalDataActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_exports_companies_data()
    {
        $companies = new Collection([
            (object) [
                'id' => 1,
                'name' => 'Company A',
                'registration_number' => 'REG001',
                'description' => 'First company',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'parent' => null,
                'children' => new Collection(),
            ],
            (object) [
                'id' => 2,
                'name' => 'Company B',
                'registration_number' => 'REG002',
                'description' => 'Second company',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'parent' => null,
                'children' => new Collection(),
            ]
        ]);

        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('query')
            ->andReturnSelf();
        $companyMock->shouldReceive('where')
            ->with('is_active', true)
            ->andReturnSelf();
        $companyMock->shouldReceive('with')
            ->with(['parent', 'children'])
            ->andReturnSelf();
        $companyMock->shouldReceive('get')
            ->andReturn($companies);

        $action = new ExportOrganizationalDataAction();
        $result = $action->execute('companies', [
            'format' => 'array',
            'include_inactive' => false,
            'include_relationships' => true,
        ]);

        $this->assertTrue(is_array($result));
        $this->assertEquals('companies', $result['type']);
        $this->assertEquals('array', $result['format']);
        $this->assertEquals(2, $result['record_count']);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
    }

    public function test_exports_staff_data_with_filters()
    {
        $staff = new Collection([
            (object) [
                'id' => 1,
                'employee_id' => 'EMP-001-001',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'hire_date' => now()->subMonths(6),
                'resignation_date' => null,
                'termination_date' => null,
                'status' => StaffStatus::ACTIVE,
                'is_active' => true,
                'company_id' => 1,
                'office_id' => 1,
                'department_id' => 1,
                'position_id' => 1,
                'supervisor_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'company' => (object) ['id' => 1, 'name' => 'Company A'],
                'office' => (object) ['id' => 1, 'name' => 'Main Office'],
                'department' => (object) ['id' => 1, 'name' => 'IT Department'],
                'position' => (object) ['id' => 1, 'title' => 'Developer'],
                'supervisor' => null,
            ]
        ]);

        $staffMock = Mockery::mock('alias:' . Staff::class);
        $staffMock->shouldReceive('query')
            ->andReturnSelf();
        $staffMock->shouldReceive('where')
            ->with('status', StaffStatus::ACTIVE)
            ->andReturnSelf();
        $staffMock->shouldReceive('where')
            ->with('company_id', 1)
            ->andReturnSelf();
        $staffMock->shouldReceive('with')
            ->with(['company', 'office', 'department', 'position', 'supervisor'])
            ->andReturnSelf();
        $staffMock->shouldReceive('get')
            ->andReturn($staff);

        $action = new ExportOrganizationalDataAction();
        $result = $action->execute('staff', [
            'format' => 'array',
            'include_inactive' => false,
            'company_id' => 1,
            'include_relationships' => true,
        ]);

        $this->assertEquals('staff', $result['type']);
        $this->assertEquals(1, $result['record_count']);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        
        $exportedStaff = $result['data'][0];
        $this->assertEquals('John Doe', $exportedStaff['name']);
        $this->assertEquals('EMP-001-001', $exportedStaff['employee_id']);
        $this->assertArrayHasKey('company', $exportedStaff);
        $this->assertEquals('Company A', $exportedStaff['company']['name']);
    }

    public function test_exports_data_as_json()
    {
        $companies = new Collection([
            (object) [
                'id' => 1,
                'name' => 'Company A',
                'registration_number' => 'REG001',
                'description' => 'First company',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'parent' => null,
                'children' => new Collection(),
            ]
        ]);

        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('query')
            ->andReturnSelf();
        $companyMock->shouldReceive('where')
            ->with('is_active', true)
            ->andReturnSelf();
        $companyMock->shouldReceive('with')
            ->with(['parent', 'children'])
            ->andReturnSelf();
        $companyMock->shouldReceive('get')
            ->andReturn($companies);

        $action = new ExportOrganizationalDataAction();
        $result = $action->execute('companies', [
            'format' => 'json',
            'include_inactive' => false,
            'include_relationships' => true,
        ]);

        $this->assertEquals('json', $result['format']);
        $this->assertTrue(is_string($result['data']));
        
        // Verify that the JSON can be decoded
        $decodedData = json_decode($result['data'], true);
        $this->assertNotNull($decodedData);
        $this->assertCount(1, $decodedData);
    }

    public function test_exports_full_organization()
    {
        // Mock all models to return empty collections
        $emptyCollection = new Collection();

        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('query')->andReturnSelf();
        $companyMock->shouldReceive('where')->andReturnSelf();
        $companyMock->shouldReceive('with')->andReturnSelf();
        $companyMock->shouldReceive('get')->andReturn($emptyCollection);

        // Mock other models similarly...
        $staffMock = Mockery::mock('alias:' . Staff::class);
        $staffMock->shouldReceive('query')->andReturnSelf();
        $staffMock->shouldReceive('where')->andReturnSelf();
        $staffMock->shouldReceive('with')->andReturnSelf();
        $staffMock->shouldReceive('get')->andReturn($emptyCollection);

        $action = new ExportOrganizationalDataAction();
        $result = $action->execute('full', ['format' => 'array']);

        $this->assertEquals('full', $result['type']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('companies', $result['data']);
        $this->assertArrayHasKey('offices', $result['data']);
        $this->assertArrayHasKey('departments', $result['data']);
        $this->assertArrayHasKey('positions', $result['data']);
        $this->assertArrayHasKey('staff', $result['data']);
    }

    public function test_throws_exception_for_invalid_export_type()
    {
        $action = new ExportOrganizationalDataAction();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown export type: invalid');
        
        $action->execute('invalid');
    }

    public function test_throws_exception_for_invalid_format()
    {
        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('query')
            ->andReturnSelf();
        $companyMock->shouldReceive('where')
            ->andReturnSelf();
        $companyMock->shouldReceive('with')
            ->andReturnSelf();
        $companyMock->shouldReceive('get')
            ->andReturn(new Collection());

        $action = new ExportOrganizationalDataAction();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format: xml');
        
        $action->execute('companies', ['format' => 'xml']);
    }
}