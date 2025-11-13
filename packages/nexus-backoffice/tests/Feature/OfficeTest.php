<?php

namespace Nexus\Backoffice\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\QueryException;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Models\OfficeType;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\Observers\OfficeObserver;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;

#[CoversClass(Office::class)]
#[CoversClass(Company::class)]
#[CoversClass(OfficeType::class)]
#[CoversClass(OfficeObserver::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
class OfficeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_office()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'description' => 'Main office',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Main Office',
            'code' => 'MAIN',
            'description' => 'Main office location',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Attach office type using many-to-many relationship
        $office->officeTypes()->attach($officeType);

        $this->assertDatabaseHas('backoffice_offices', [
            'name' => 'Main Office',
            'code' => 'MAIN',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        $this->assertEquals('Main Office', $office->name);
        $this->assertEquals($company->id, $office->company_id);
        $this->assertTrue($office->officeTypes->contains($officeType));
    }

    #[Test]
    public function it_belongs_to_company()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
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

        $this->assertEquals($company->id, $office->company->id);
        $this->assertEquals('Test Company', $office->company->name);
    }

    #[Test]
    public function it_belongs_to_office_type()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Regional Office',
            'code' => 'REGIONAL',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Test Office',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        // Attach office type using many-to-many relationship
        $office->officeTypes()->attach($officeType);

        $this->assertTrue($office->officeTypes->contains($officeType));
        $this->assertEquals('Regional Office', $office->officeTypes->first()->name);
    }

    #[Test]
    public function it_can_create_office_hierarchy()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $parentOffice = Office::factory()->create([
            'name' => 'Parent Office',
            'code' => 'PARENT',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        $childOffice = Office::factory()->create([
            'name' => 'Child Office',
            'code' => 'CHILD',
            'company_id' => $company->id,
            
            'parent_office_id' => $parentOffice->id,
            'is_active' => true,
        ]);

        $this->assertEquals($parentOffice->id, $childOffice->parent_office_id);
        $this->assertTrue($parentOffice->childOffices->contains($childOffice));
        $this->assertEquals($parentOffice->id, $childOffice->parentOffice->id);
    }

    #[Test]
    public function it_can_get_root_office()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $rootOffice = Office::factory()->create([
            'name' => 'Root Office',
            'code' => 'ROOT',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        $level1Office = Office::factory()->create([
            'name' => 'Level 1 Office',
            'code' => 'L1',
            'company_id' => $company->id,
            
            'parent_office_id' => $rootOffice->id,
            'is_active' => true,
        ]);

        $level2Office = Office::factory()->create([
            'name' => 'Level 2 Office',
            'code' => 'L2',
            'company_id' => $company->id,
            
            'parent_office_id' => $level1Office->id,
            'is_active' => true,
        ]);

        $this->assertEquals($rootOffice->id, $level2Office->rootOffice()->id);
        $this->assertTrue($level2Office->isDescendantOf($rootOffice));
        $this->assertTrue($rootOffice->isAncestorOf($level2Office));
    }

    #[Test]
    public function it_can_scope_active_offices()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $activeOffice = Office::factory()->create([
            'name' => 'Active Office',
            'code' => 'ACTIVE',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        $inactiveOffice = Office::factory()->create([
            'name' => 'Inactive Office',
            'code' => 'INACTIVE',
            'company_id' => $company->id,
            
            'is_active' => false,
        ]);

        $activeOffices = Office::active()->get();
        
        $this->assertCount(1, $activeOffices);
        $this->assertTrue($activeOffices->contains('id', $activeOffice->id));
        $this->assertFalse($activeOffices->contains('id', $inactiveOffice->id));
    }

    #[Test]
    public function it_can_scope_by_company()
    {
        $company1 = Company::factory()->create([
            'name' => 'Company 1',
            'code' => 'COMP1',
            'is_active' => true,
        ]);

        $company2 = Company::factory()->create([
            'name' => 'Company 2',
            'code' => 'COMP2',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office1 = Office::factory()->create([
            'name' => 'Office 1',
            'company_id' => $company1->id,
            
            'is_active' => true,
        ]);

        $office2 = Office::factory()->create([
            'name' => 'Office 2',
            'company_id' => $company2->id,
            
            'is_active' => true,
        ]);

        $company1Offices = Office::forCompany($company1->id)->get();
        
        $this->assertCount(1, $company1Offices);
        $this->assertTrue($company1Offices->contains('id', $office1->id));
        $this->assertFalse($company1Offices->contains('id', $office2->id));
    }

    #[Test]
    public function it_can_scope_by_office_type()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $branchType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $hqType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $branchOffice = Office::factory()->create([
            'name' => 'Branch Office',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);
        $branchOffice->officeTypes()->attach($branchType);

        $hqOffice = Office::factory()->create([
            'name' => 'HQ Office',
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);
        $hqOffice->officeTypes()->attach($hqType);

        $branchOffices = Office::withType($branchType->id)->get();
        
        $this->assertCount(1, $branchOffices);
        $this->assertTrue($branchOffices->contains('id', $branchOffice->id));
        $this->assertFalse($branchOffices->contains('id', $hqOffice->id));
    }

    #[Test]
    public function it_requires_name_and_company()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Office::factory()->create([
            'name' => null,
            'company_id' => null,
            'code' => 'TEST',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_have_nullable_code()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Branch',
            'code' => 'BRANCH',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Test Office',
            'code' => null,
            'company_id' => $company->id,
            
            'is_active' => true,
        ]);

        $this->assertNull($office->code);
    }

    #[Test]
    public function it_defaults_to_active()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
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
            
        ]);

        $this->assertTrue($office->is_active);
    }
}