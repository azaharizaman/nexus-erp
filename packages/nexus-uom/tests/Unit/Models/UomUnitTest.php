<?php

namespace Nexus\Uom\Tests\Unit\Models;

use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Models\UomUnitGroup;
use Nexus\Uom\Tests\TestCase;

class UomUnitTest extends TestCase
{
    public function test_factory_creates_unit_with_relationships(): void
    {
    $type = UomType::factory()->create(['slug' => 'test-type']);
        $group = UomUnitGroup::factory()->create();

        $unit = UomUnit::factory()->for($type, 'type')->create(['code' => 'UNITX']);
        $alias = UomAlias::factory()->for($unit, 'unit')->create(['alias' => 'unitx-alt']);
        $group->units()->attach($unit->getKey());

        $unit->refresh();

        $this->assertTrue($unit->type->is($type));
        $this->assertTrue($unit->aliases->contains($alias));
        $this->assertTrue($unit->groups->contains($group));
    }
}
