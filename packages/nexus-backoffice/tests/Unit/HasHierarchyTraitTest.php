<?php

namespace Nexus\Backoffice\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Models\StaffTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Backoffice\BackOfficeServiceProvider;
use Nexus\Backoffice\Observers\CompanyObserver;

#[CoversClass(Company::class)]
#[CoversClass(CompanyObserver::class)]
#[CoversClass(StaffTransfer::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
class HasHierarchyTraitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_identify_root_nodes()
    {
        $rootCompany = Company::factory()->create([
            'name' => 'Root Company',
            'is_active' => true,
        ]);

        $childCompany = Company::factory()->create([
            'name' => 'Child Company',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $this->assertTrue($rootCompany->isRoot());
        $this->assertFalse($childCompany->isRoot());
    }

    #[Test]
    public function it_can_identify_leaf_nodes()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'is_active' => true,
        ]);

        $childCompany = Company::factory()->create([
            'name' => 'Child Company',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $this->assertFalse($parentCompany->isLeaf());
        $this->assertTrue($childCompany->isLeaf());
    }

    #[Test]
    public function it_can_calculate_depth()
    {
        $rootCompany = Company::factory()->create([
            'name' => 'Root Company',
            'is_active' => true,
        ]);

        $level1Company = Company::factory()->create([
            'name' => 'Level 1 Company',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::factory()->create([
            'name' => 'Level 2 Company',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
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
            'parent_company_id' => null,
            'is_active' => true,
        ]);

        $level1Company = Company::factory()->create([
            'name' => 'Level 1 Company',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::factory()->create([
            'name' => 'Level 2 Company',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        $path = $level2Company->fresh()->getPath();
        
        $this->assertCount(3, $path, 'Path should contain all 3 companies');
        $this->assertTrue($path->contains('name', 'Root Company'), 'Path should contain root');
        $this->assertTrue($path->contains('name', 'Level 1 Company'), 'Path should contain level 1');
        $this->assertTrue($path->contains('name', 'Level 2 Company'), 'Path should contain level 2');
        
        // Verify order
        $this->assertEquals('Root Company', $path->first()->name, 'Root should be first');
        $this->assertEquals('Level 2 Company', $path->last()->name, 'Level 2 should be last');
    }

    #[Test]
    public function it_can_check_ancestor_descendant_relationships()
    {
        $rootCompany = Company::factory()->create([
            'name' => 'Root Company',
            'is_active' => true,
        ]);

        $level1Company = Company::factory()->create([
            'name' => 'Level 1 Company',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::factory()->create([
            'name' => 'Level 2 Company',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        // Test ancestor relationships
        $this->assertTrue($rootCompany->isAncestorOf($level1Company));
        $this->assertTrue($rootCompany->isAncestorOf($level2Company));
        $this->assertTrue($level1Company->isAncestorOf($level2Company));
        $this->assertFalse($level1Company->isAncestorOf($rootCompany));

        // Test descendant relationships
        $this->assertTrue($level1Company->isDescendantOf($rootCompany));
        $this->assertTrue($level2Company->isDescendantOf($rootCompany));
        $this->assertTrue($level2Company->isDescendantOf($level1Company));
        $this->assertFalse($rootCompany->isDescendantOf($level1Company));
    }

    #[Test]
    public function it_can_get_all_descendants()
    {
        $rootCompany = Company::factory()->create([
            'name' => 'Root Company',
            'is_active' => true,
        ]);

        $child1 = Company::factory()->create([
            'name' => 'Child 1',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $child2 = Company::factory()->create([
            'name' => 'Child 2',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $grandchild = Company::factory()->create([
            'name' => 'Grandchild',
            'parent_company_id' => $child1->id,
            'is_active' => true,
        ]);

        $descendants = $rootCompany->allChildCompanies();
        
        $this->assertCount(3, $descendants);
        $this->assertTrue($descendants->contains('id', $child1->id));
        $this->assertTrue($descendants->contains('id', $child2->id));
        $this->assertTrue($descendants->contains('id', $grandchild->id));
    }

    #[Test]
    public function it_can_get_all_ancestors()
    {
        $rootCompany = Company::factory()->create([
            'name' => 'Root Company',
            'is_active' => true,
        ]);

        $level1Company = Company::factory()->create([
            'name' => 'Level 1 Company',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::factory()->create([
            'name' => 'Level 2 Company',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        $ancestors = $level2Company->allParentCompanies();
        
        $this->assertCount(2, $ancestors);
        $this->assertTrue($ancestors->contains('id', $level1Company->id));
        $this->assertTrue($ancestors->contains('id', $rootCompany->id));
    }

    #[Test]
    public function it_can_get_siblings()
    {
        $parentCompany = Company::factory()->create([
            'name' => 'Parent Company',
            'is_active' => true,
        ]);

        $child1 = Company::factory()->create([
            'name' => 'Child 1',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $child2 = Company::factory()->create([
            'name' => 'Child 2',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $child3 = Company::factory()->create([
            'name' => 'Child 3',
            'parent_company_id' => $parentCompany->id,
            'is_active' => true,
        ]);

        $siblings = $child1->getSiblings();
        
        $this->assertCount(2, $siblings);
        $this->assertTrue($siblings->contains('id', $child2->id));
        $this->assertTrue($siblings->contains('id', $child3->id));
        $this->assertFalse($siblings->contains('id', $child1->id));
    }

    #[Test]
    public function it_can_get_root_node()
    {
        $rootCompany = Company::factory()->create([
            'name' => 'Root Company',
            'is_active' => true,
        ]);

        $level1Company = Company::factory()->create([
            'name' => 'Level 1 Company',
            'parent_company_id' => $rootCompany->id,
            'is_active' => true,
        ]);

        $level2Company = Company::factory()->create([
            'name' => 'Level 2 Company',
            'parent_company_id' => $level1Company->id,
            'is_active' => true,
        ]);

        $this->assertEquals($rootCompany->id, $level2Company->rootCompany()->id);
        $this->assertEquals($rootCompany->id, $level1Company->rootCompany()->id);
        $this->assertEquals($rootCompany->id, $rootCompany->rootCompany()->id);
    }
}