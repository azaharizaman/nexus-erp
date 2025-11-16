<?php

namespace Nexus\Backoffice\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;

#[CoversClass(Company::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(CompanyObserver::class)]
#[UsesClass(Company::class)]
class CompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_company()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'description' => 'A test company',
        ]);

        $this->assertDatabaseHas('backoffice_companies', [
            'name' => 'Test Company',
            'code' => 'TEST',
            'description' => 'A test company',
            'is_active' => true,
        ]);

        $this->assertEquals('Test Company', $company->name);
        $this->assertEquals('TEST', $company->code);
        $this->assertTrue($company->is_active);
        $this->assertTrue($company->isActive());
    }

    #[Test]
    public function it_can_create_company_hierarchy()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
        ]);

        $childCompany = Company::factory()->childOf($parentCompany)->create([
            'name' => 'Child Company',
            'code' => 'CHILD',
        ]);

        $this->assertEquals($parentCompany->id, $childCompany->parent_company_id);
        $this->assertTrue($parentCompany->childCompanies->contains($childCompany));
        $this->assertEquals($parentCompany->id, $childCompany->parentCompany->id);
    }

    #[Test]
    public function it_can_get_root_company()
    {
        $rootCompany = Company::factory()->root()->create([
            'name' => 'Root Company',
            'code' => 'ROOT',
        ]);

        $level1Company = Company::factory()->childOf($rootCompany)->create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
        ]);

        $level2Company = Company::factory()->childOf($level1Company)->create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
        ]);

        $this->assertEquals($rootCompany->id, $level2Company->rootCompany()->id);
        $this->assertTrue($level2Company->isDescendantOf($rootCompany));
        $this->assertTrue($rootCompany->isAncestorOf($level2Company));
    }

    #[Test]
    public function it_can_get_all_descendants()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
        ]);

        $child1 = Company::factory()->childOf($parentCompany)->create([
            'name' => 'Child 1',
            'code' => 'CHILD1',
        ]);

        $child2 = Company::factory()->childOf($parentCompany)->create([
            'name' => 'Child 2',
            'code' => 'CHILD2',
        ]);

        $grandchild = Company::factory()->childOf($child1)->create([
            'name' => 'Grandchild',
            'code' => 'GRANDCHILD',
        ]);

        $descendants = $parentCompany->allChildCompanies();
        
        $this->assertCount(3, $descendants);
        $this->assertTrue($descendants->contains('id', $child1->id));
        $this->assertTrue($descendants->contains('id', $child2->id));
        $this->assertTrue($descendants->contains('id', $grandchild->id));
    }

    #[Test]
    public function it_can_get_ancestors()
    {
        $rootCompany = Company::factory()->root()->create([
            'name' => 'Root Company',
            'code' => 'ROOT',
        ]);

        $level1Company = Company::factory()->childOf($rootCompany)->create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
        ]);

        $level2Company = Company::factory()->childOf($level1Company)->create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
        ]);

        $ancestors = $level2Company->allParentCompanies();
        
        $this->assertCount(2, $ancestors);
        $this->assertTrue($ancestors->contains('id', $level1Company->id));
        $this->assertTrue($ancestors->contains('id', $rootCompany->id));
    }

    #[Test]
    public function it_can_check_if_company_is_root()
    {
        $rootCompany = Company::factory()->root()->create([
            'name' => 'Root Company',
            'code' => 'ROOT',
        ]);

        $childCompany = Company::factory()->childOf($rootCompany)->create([
            'name' => 'Child Company',
            'code' => 'CHILD',
        ]);

        $this->assertTrue($rootCompany->isRoot());
        $this->assertFalse($childCompany->isRoot());
    }

    #[Test]
    public function it_can_check_if_company_is_leaf()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
        ]);

        $childCompany = Company::factory()->childOf($parentCompany)->create([
            'name' => 'Child Company',
            'code' => 'CHILD',
        ]);

        $this->assertFalse($parentCompany->isLeaf());
        $this->assertTrue($childCompany->isLeaf());
    }

    #[Test]
    public function it_can_get_hierarchy_depth()
    {
        $rootCompany = Company::factory()->root()->create([
            'name' => 'Root Company',
            'code' => 'ROOT',
        ]);

        $level1Company = Company::factory()->childOf($rootCompany)->create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
        ]);

        $level2Company = Company::factory()->childOf($level1Company)->create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
        ]);

        $this->assertEquals(0, $rootCompany->getDepth());
        $this->assertEquals(1, $level1Company->getDepth());
        $this->assertEquals(2, $level2Company->getDepth());
    }

    #[Test]
    public function it_can_get_hierarchy_path()
    {
        $rootCompany = Company::factory()->create([
            'name' => 'Root Company',
            'code' => 'ROOT',
            'parent_company_id' => null,
        ]);

        $level1Company = Company::factory()->create([
            'name' => 'Level 1 Company',
            'code' => 'L1',
            'parent_company_id' => $rootCompany->id,
        ]);

        $level2Company = Company::factory()->create([
            'name' => 'Level 2 Company',
            'code' => 'L2',
            'parent_company_id' => $level1Company->id,
        ]);

        $path = $level2Company->fresh()->getPath();
        
        $this->assertCount(3, $path, 'Path should contain root, level1, and level2 companies');
        $this->assertTrue($path->contains('name', 'Root Company'), 'Path should contain root company');
        $this->assertTrue($path->contains('name', 'Level 1 Company'), 'Path should contain level 1 company');
        $this->assertTrue($path->contains('name', 'Level 2 Company'), 'Path should contain level 2 company');
        
        // Verify order: root should be first, level2 should be last
        $this->assertEquals('Root Company', $path->first()->name, 'Root company should be first in path');
        $this->assertEquals('Level 2 Company', $path->last()->name, 'Level 2 company should be last in path');
    }

    #[Test]
    public function it_can_get_siblings()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'code' => 'PARENT',
        ]);

        $child1 = Company::factory()->childOf($parentCompany)->create([
            'name' => 'Child 1',
            'code' => 'CHILD1',
        ]);

        $child2 = Company::factory()->childOf($parentCompany)->create([
            'name' => 'Child 2',
            'code' => 'CHILD2',
        ]);

        $child3 = Company::factory()->childOf($parentCompany)->create([
            'name' => 'Child 3',
            'code' => 'CHILD3',
        ]);

        $siblings = $child1->getSiblings();
        
        $this->assertCount(2, $siblings);
        $this->assertTrue($siblings->contains('id', $child2->id));
        $this->assertTrue($siblings->contains('id', $child3->id));
        $this->assertFalse($siblings->contains('id', $child1->id));
    }

    #[Test]
    public function it_can_scope_root_companies()
    {
        $rootCompany1 = Company::factory()->root()->create([
            'name' => 'Root Company 1',
            'code' => 'ROOT1',
        ]);

        $rootCompany2 = Company::factory()->root()->create([
            'name' => 'Root Company 2',
            'code' => 'ROOT2',
        ]);

        $childCompany = Company::factory()->childOf($rootCompany1)->create([
            'name' => 'Child Company',
            'code' => 'CHILD',
        ]);

        $rootCompanies = Company::root()->get();
        
        $this->assertCount(2, $rootCompanies);
        $this->assertTrue($rootCompanies->contains('id', $rootCompany1->id));
        $this->assertTrue($rootCompanies->contains('id', $rootCompany2->id));
        $this->assertFalse($rootCompanies->contains('id', $childCompany->id));
    }

    #[Test]
    public function it_can_scope_active_companies()
    {
        $activeCompany = Company::factory()->active()->create([
            'name' => 'Active Company',
            'code' => 'ACTIVE',
        ]);

        $inactiveCompany = Company::factory()->inactive()->create([
            'name' => 'Inactive Company',
            'code' => 'INACTIVE',
        ]);

        $activeCompanies = Company::active()->get();
        
        $this->assertCount(1, $activeCompanies);
        $this->assertTrue($activeCompanies->contains('id', $activeCompany->id));
        $this->assertFalse($activeCompanies->contains('id', $inactiveCompany->id));
    }

    #[Test]
    public function it_requires_name()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Company::create([
            'name' => null,
            'code' => 'TEST',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_have_nullable_code()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => null,
        ]);

        $this->assertNull($company->code);
    }

    #[Test]
    public function it_can_have_nullable_description()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'description' => null,
        ]);

        $this->assertNull($company->description);
    }

    #[Test]
    public function it_defaults_to_active()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'code' => 'TEST',
        ]);

        $this->assertTrue($company->is_active);
    }
}