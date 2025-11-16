<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Backoffice;

use PHPUnit\Framework\TestCase;
use Mockery;
use Nexus\Erp\Actions\Backoffice\CreateCompanyAction;
use Nexus\Backoffice\Models\Company;
use Illuminate\Validation\ValidationException;

class CreateCompanyActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_company_successfully()
    {
        // Mock the Company model
        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Test Company',
                'registration_number' => 'REG123',
                'description' => 'A test company',
                'parent_id' => null,
                'is_active' => true,
            ])
            ->andReturn((object) [
                'id' => 1,
                'name' => 'Test Company',
                'registration_number' => 'REG123',
                'description' => 'A test company',
                'parent_id' => null,
                'is_active' => true,
            ]);

        $action = new CreateCompanyAction();
        $result = $action->execute([
            'name' => 'Test Company',
            'registration_number' => 'REG123',
            'description' => 'A test company',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Company created successfully', $result['message']);
        $this->assertEquals(1, $result['data']['id']);
        $this->assertEquals('Test Company', $result['data']['name']);
    }

    public function test_validates_required_fields()
    {
        $action = new CreateCompanyAction();
        
        $this->expectException(ValidationException::class);
        
        $action->execute([
            'registration_number' => 'REG123',
            'description' => 'A test company',
        ]);
    }

    public function test_validates_unique_registration_number()
    {
        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('where')
            ->with('registration_number', 'EXISTING123')
            ->andReturnSelf();
        $companyMock->shouldReceive('exists')
            ->andReturn(true);

        $action = new CreateCompanyAction();
        
        $this->expectException(ValidationException::class);
        
        $action->execute([
            'name' => 'Test Company',
            'registration_number' => 'EXISTING123',
            'description' => 'A test company',
        ]);
    }

    public function test_validates_parent_company_exists()
    {
        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('find')
            ->with(999)
            ->andReturn(null);

        $action = new CreateCompanyAction();
        
        $this->expectException(ValidationException::class);
        
        $action->execute([
            'name' => 'Test Company',
            'registration_number' => 'REG123',
            'description' => 'A test company',
            'parent_id' => 999,
        ]);
    }

    public function test_prevents_circular_hierarchy()
    {
        $parentCompany = (object) [
            'id' => 1,
            'parent_id' => 2,
        ];

        $companyMock = Mockery::mock('alias:' . Company::class);
        $companyMock->shouldReceive('find')
            ->with(1)
            ->andReturn($parentCompany);
        
        // Mock hierarchy check
        $companyMock->shouldReceive('where')
            ->with('id', 2)
            ->andReturnSelf();
        $companyMock->shouldReceive('whereNull')
            ->with('parent_id')
            ->andReturnSelf();
        $companyMock->shouldReceive('exists')
            ->andReturn(false);

        $action = new CreateCompanyAction();
        
        $this->expectException(ValidationException::class);
        
        $action->execute([
            'name' => 'Test Company',
            'registration_number' => 'REG123',
            'description' => 'A test company',
            'parent_id' => 1,
        ]);
    }
}