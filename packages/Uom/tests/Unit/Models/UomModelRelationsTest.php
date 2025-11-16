<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Unit\Models;

use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomCompoundComponent;
use Nexus\Uom\Models\UomCompoundUnit;
use Nexus\Uom\Models\UomConversion;
use Nexus\Uom\Models\UomConversionLog;
use Nexus\Uom\Models\UomCustomConversion;
use Nexus\Uom\Models\UomCustomUnit;
use Nexus\Uom\Models\UomItem;
use Nexus\Uom\Models\UomItemPackaging;
use Nexus\Uom\Models\UomPackaging;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Models\UomUnitGroup;
use Nexus\Uom\Tests\TestCase;

class UomModelRelationsTest extends TestCase
{
    public function testCompoundUnitRelationships(): void
    {
        $type = UomType::factory()->create();
        $unit = UomUnit::factory()->for($type, 'type')->create();
        $compound = UomCompoundUnit::factory()->for($type, 'type')->create();

        $component = UomCompoundComponent::factory()
            ->for($compound, 'compoundUnit')
            ->for($unit, 'unit')
            ->create(['exponent' => 2]);

        $compound->load('components');

        $this->assertTrue($compound->type->is($type));
        $this->assertTrue($compound->components->contains($component));
        $this->assertTrue($component->compoundUnit->is($compound));
        $this->assertTrue($component->unit->is($unit));
        $this->assertTrue($unit->compoundComponents->contains($component));
    }

    public function testConversionRelationships(): void
    {
        $type = UomType::factory()->create();
        $source = UomUnit::factory()->for($type, 'type')->create();
        $target = UomUnit::factory()->for($type, 'type')->create();

        $conversion = UomConversion::factory()
            ->for($source, 'sourceUnit')
            ->for($target, 'targetUnit')
            ->linear()
            ->create(['factor' => '2', 'offset' => '0']);

        $log = UomConversionLog::factory()
            ->for($source, 'sourceUnit')
            ->for($target, 'targetUnit')
            ->create();

        $this->assertTrue($conversion->sourceUnit->is($source));
        $this->assertTrue($conversion->targetUnit->is($target));
        $this->assertTrue($log->sourceUnit->is($source));
        $this->assertTrue($log->targetUnit->is($target));
        $this->assertNull($log->performedBy);
        $this->assertTrue($source->conversionsFrom->contains($conversion));
        $this->assertTrue($target->conversionsTo->contains($conversion));
        $this->assertTrue($source->conversionLogsAsSource->contains($log));
        $this->assertTrue($target->conversionLogsAsTarget->contains($log));
    }

    public function testCustomUnitRelationships(): void
    {
        $type = UomType::factory()->create();
        $owner = UomItem::factory()->create();
        $source = UomCustomUnit::factory()->for($type, 'type')->create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
        ]);
        $target = UomCustomUnit::factory()->for($type, 'type')->create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
        ]);

        $conversion = UomCustomConversion::factory()
            ->for($source, 'sourceUnit')
            ->for($target, 'targetUnit')
            ->create(['factor' => '3', 'offset' => '0']);

        $this->assertTrue($source->type->is($type));
        $this->assertTrue($source->owner->is($owner));
        $this->assertTrue($source->conversionsFrom->contains($conversion));
        $this->assertTrue($target->conversionsTo->contains($conversion));
    }

    public function testPackagingRelationships(): void
    {
        $base = UomUnit::factory()->create();
        $package = UomUnit::factory()->create();
        $packaging = UomPackaging::factory()
            ->for($base, 'baseUnit')
            ->for($package, 'packageUnit')
            ->create(['quantity' => 6]);
        /** @var UomItem $item */
        $item = UomItem::factory()->create(['default_unit_id' => $base->getKey()]);

        $itemPackaging = UomItemPackaging::factory()
            ->for($item, 'item')
            ->for($packaging, 'packaging')
            ->create();

        $this->assertTrue($packaging->baseUnit->is($base));
        $this->assertTrue($packaging->packageUnit->is($package));
        $this->assertTrue($packaging->itemPackagings->contains($itemPackaging));
        $this->assertTrue($itemPackaging->packaging->is($packaging));
        $this->assertTrue($itemPackaging->item->is($packaging->itemPackagings->first()->item));
        $this->assertTrue($item->packagings->contains($itemPackaging));
        $this->assertNotNull($item->defaultUnit);
        $this->assertTrue($item->defaultUnit->is($item->packagings->first()->packaging->baseUnit));
        $this->assertTrue($package->packagingAsPackage->contains($packaging));
    }

    public function testAliasAndGroupRelationships(): void
    {
        $unit = UomUnit::factory()->create();
        $alias = UomAlias::factory()->for($unit, 'unit')->create(['alias' => 'ALT']);
        $group = UomUnitGroup::factory()->create();
        $group->units()->attach($unit);

        $this->assertTrue($alias->unit->is($unit));
        $this->assertTrue($group->units->contains($unit));
        $this->assertTrue($unit->aliases->contains($alias));
        $this->assertTrue($unit->groups->contains($group));
    }

    public function testTypeAndUnitRelationships(): void
    {
        $type = UomType::factory()->create();
        $base = UomUnit::factory()->for($type, 'type')->base()->create([
            'code' => 'BASE',
            'uom_type_id' => $type->getKey(),
        ]);
        $unit = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'ALT',
            'is_base' => false,
        ]);

        $compound = UomCompoundUnit::factory()->for($type, 'type')->create();
        $type->load(['units', 'compoundUnits', 'customUnits']);

        $this->assertTrue($unit->type->is($type));
        $this->assertTrue($unit->packagingAsBase->isEmpty());
        $this->assertTrue($type->units->contains($unit));
        $this->assertTrue($type->compoundUnits->contains($compound));

        $custom = UomCustomUnit::factory()->for($type, 'type')->create();
        $this->assertTrue($type->customUnits()->whereKey($custom->getKey())->exists());

        $activeUnits = UomUnit::query()->active()->get();
        $this->assertTrue($activeUnits->contains($unit));
        $this->assertTrue($activeUnits->contains($base));
    }
}
