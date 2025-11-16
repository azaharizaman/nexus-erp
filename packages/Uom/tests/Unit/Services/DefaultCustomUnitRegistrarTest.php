<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Unit\Services;

use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomCustomConversion;
use Nexus\Uom\Models\UomCustomUnit;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Services\DefaultCustomUnitRegistrar;
use Nexus\Uom\Tests\TestCase;
use InvalidArgumentException;
use ReflectionMethod;

class DefaultCustomUnitRegistrarTest extends TestCase
{
    private DefaultCustomUnitRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registrar = $this->app->make(DefaultCustomUnitRegistrar::class);
    }

    public function testRegisterPersistsCustomUnitAndConversions(): void
    {
        $type = UomType::factory()->create();
        $owner = UomUnit::factory()->create();
        $target = UomCustomUnit::factory()->for($type, 'type')->create([
            'code' => 'CUS2',
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
        ]);

        $unit = $this->registrar->register([
            'code' => 'CUS1',
            'name' => 'Custom Unit',
            'uom_type_id' => $type->getKey(),
            'conversion_factor' => '1.5',
        ], $owner, [
            [
                'target' => $target->code,
                'factor' => '2',
                'offset' => '0',
                'is_linear' => true,
            ],
        ]);

        $this->assertInstanceOf(UomCustomUnit::class, $unit);
        $this->assertSame('CUS1', $unit->code);
        $this->assertSame($owner->getMorphClass(), $unit->owner_type);
        $this->assertSame($owner->getKey(), $unit->owner_id);

        $conversions = UomCustomConversion::query()->where('source_custom_unit_id', $unit->getKey())->get();
        $this->assertCount(1, $conversions);
        $this->assertSame('2.000000000000', $conversions->first()->factor);
    }

    public function testRegisterRejectsDuplicateCodesWithinOwnerScope(): void
    {
        $type = UomType::factory()->create();
        $owner = ['owner_type' => 'App\\Models\\Tenant', 'owner_id' => 5];

        UomCustomUnit::factory()->for($type, 'type')->create([
            'code' => 'DUPE',
            'owner_type' => $owner['owner_type'],
            'owner_id' => $owner['owner_id'],
        ]);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Custom unit code 'DUPE'");

        $this->registrar->register([
            'code' => 'DUPE',
            'name' => 'Duplicate',
            'uom_type_id' => $type->getKey(),
            'conversion_factor' => '1',
        ], $owner);
    }

    public function testRegisterRejectsZeroConversionFactor(): void
    {
        $type = UomType::factory()->create();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('declares a zero conversion factor');

        $this->registrar->register([
            'code' => 'ZERO',
            'name' => 'Zero Factor',
            'uom_type_id' => $type->getKey(),
            'conversion_factor' => '0',
        ]);
    }

    public function testRegisterRejectsNonLinearConversionsWithZeroFactor(): void
    {
        $type = UomType::factory()->create();
        $target = UomCustomUnit::factory()->for($type, 'type')->create(['code' => 'TARGET']);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('specifies a zero factor');

        $this->registrar->register([
            'code' => 'CUS3',
            'name' => 'With Conversion',
            'uom_type_id' => $type->getKey(),
            'conversion_factor' => '1',
        ], null, [
            [
                'target' => $target->code,
                'factor' => '0',
                'is_linear' => true,
            ],
        ]);
    }

    public function testRegisterRejectsFormulasWhenDisabled(): void
    {
        $type = UomType::factory()->create();
        $target = UomCustomUnit::factory()->for($type, 'type')->create(['code' => 'FORM']);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('formulas are disabled');

        $this->registrar->register([
            'code' => 'CUS4',
            'name' => 'Formula Unit',
            'uom_type_id' => $type->getKey(),
            'conversion_factor' => '1',
        ], null, [
            [
                'target' => $target->code,
                'formula' => 'x * 2',
                'is_linear' => false,
            ],
        ]);
    }

    public function testRegisterRequiresNonEmptyCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty code');

        $this->registrar->register([
            'code' => '   ',
            'name' => 'Invalid',
        ]);
    }

    public function testRegisterRequiresNonEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty name');

        $this->registrar->register([
            'code' => 'NONAME',
            'name' => '',
        ]);
    }

    public function testRegisterNormalisesOwnerAttributesAndMetadata(): void
    {
        $type = UomType::factory()->create();

        $unit = $this->registrar->register([
            'code' => 'META',
            'name' => 'Metadata Unit',
            'uom_type_id' => $type->getKey(),
            'metadata' => (object) ['foo' => 'bar'],
            'owner_type' => 'App\\Models\\Team',
            'owner_id' => '007',
        ]);

        $this->assertSame('App\\Models\\Team', $unit->owner_type);
        $this->assertSame(7, $unit->owner_id);
        $this->assertSame(['foo' => 'bar'], $unit->metadata);

        $unitWithScalarMetadata = $this->registrar->register([
            'code' => 'SCALAR',
            'name' => 'Scalar Metadata',
            'uom_type_id' => $type->getKey(),
            'metadata' => 'should-be-null',
            'owner_type' => 'App\\Models\\Team',
            'owner_id' => 'not-numeric',
        ]);

        $this->assertNull($unitWithScalarMetadata->metadata);
        $this->assertNull($unitWithScalarMetadata->owner_id);
    }

    public function testRegisterNormalisesOwnerArrayContext(): void
    {
        $type = UomType::factory()->create();

        $unit = $this->registrar->register([
            'code' => 'OWNER',
            'name' => 'Owner Array',
            'uom_type_id' => $type->getKey(),
        ], ['owner_type' => 'App\Models\Tenant', 'owner_id' => null, 'id' => '21']);

        $this->assertSame('App\Models\Tenant', $unit->owner_type);
        $this->assertSame(21, $unit->owner_id);

        $unitWithoutNumericId = $this->registrar->register([
            'code' => 'OWNERNULL',
            'name' => 'Owner Null',
            'uom_type_id' => $type->getKey(),
        ], ['owner_type' => 'App\Models\Tenant', 'owner_id' => 'abc']);

        $this->assertNull($unitWithoutNumericId->owner_id);
    }

    public function testRegisterSkipsInvalidOrSelfConversions(): void
    {
        $type = UomType::factory()->create();

        $unit = $this->registrar->register([
            'code' => 'SKIP',
            'name' => 'Skip Conversions',
            'uom_type_id' => $type->getKey(),
        ], null, [
            'not-an-array',
            [
                'target' => 'SKIP',
                'factor' => '2',
                'offset' => '1',
            ],
        ]);

        $this->assertSame(0, UomCustomConversion::query()->where('source_custom_unit_id', $unit->getKey())->count());
    }

    public function testRegisterAllowsFormulasWhenEnabled(): void
    {
        $this->app['config']->set('uom.conversion.allow_custom_formulas', true);

        $type = UomType::factory()->create();
        $target = UomCustomUnit::factory()->for($type, 'type')->create(['code' => 'FMT']);

        $registrar = $this->app->make(DefaultCustomUnitRegistrar::class);

        $unit = $registrar->register([
            'code' => 'CUSF',
            'name' => 'Formula Enabled',
            'uom_type_id' => $type->getKey(),
        ], null, [
            [
                'target' => $target->code,
                'formula' => 'value * 3',
                'is_linear' => false,
                'metadata' => (object) ['note' => 'custom'],
            ],
        ]);

    $conversion = UomCustomConversion::query()->where('source_custom_unit_id', $unit->getKey())->first();

    $this->assertNotNull($conversion);
    $this->assertSame('value * 3', $conversion->formula);
    $this->assertSame(['note' => 'custom'], $conversion->metadata);
    }

    public function testRegisterResolvesTargetByNumericIdentifier(): void
    {
        $type = UomType::factory()->create();
        $target = UomCustomUnit::factory()->for($type, 'type')->create(['code' => 'NUM']);

        $unit = $this->registrar->register([
            'code' => 'SOURCE',
            'name' => 'Numeric Target',
            'uom_type_id' => $type->getKey(),
        ], null, [
            [
                'target_custom_unit_id' => $target->getKey(),
                'factor' => '3',
                'offset' => '0',
            ],
        ]);

    $conversion = UomCustomConversion::query()->where('source_custom_unit_id', $unit->getKey())->first();

    $this->assertNotNull($conversion);
    $this->assertSame($target->getKey(), $conversion->target_custom_unit_id);
    $this->assertSame('3.000000000000', $conversion->factor);
    }

    public function testRegisterRejectsUnknownTargetUnit(): void
    {
        $type = UomType::factory()->create();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Custom unit 'UNKNOWN'");

        $this->registrar->register([
            'code' => 'FAIL',
            'name' => 'Missing Target',
            'uom_type_id' => $type->getKey(),
        ], null, [
            [
                'target' => 'UNKNOWN',
                'factor' => '1',
            ],
        ]);
    }

    public function testRegisterRejectsInvalidConversionFactorInput(): void
    {
        $type = UomType::factory()->create();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Value 'abc'");

        $this->registrar->register([
            'code' => 'BAD',
            'name' => 'Bad Factor',
            'uom_type_id' => $type->getKey(),
            'conversion_factor' => 'abc',
        ]);
    }

    public function testHelperMethodsHandleEdgeCases(): void
    {
        $resolveOwner = new ReflectionMethod(DefaultCustomUnitRegistrar::class, 'resolveOwner');
        $resolveOwner->setAccessible(true);

        $this->assertSame([
            'owner_type' => null,
            'owner_id' => null,
        ], $resolveOwner->invoke($this->registrar, null, null));

        $normaliseMetadata = new ReflectionMethod(DefaultCustomUnitRegistrar::class, 'normaliseMetadata');
        $normaliseMetadata->setAccessible(true);
        $this->assertSame(['foo' => 'bar'], $normaliseMetadata->invoke($this->registrar, (object) ['foo' => 'bar']));

        $source = UomCustomUnit::factory()->create(['code' => 'SRC']);

        $resolveTargetUnit = new ReflectionMethod(DefaultCustomUnitRegistrar::class, 'resolveTargetUnit');
        $resolveTargetUnit->setAccessible(true);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('could not be found');

        $resolveTargetUnit->invoke($this->registrar, 'MISSING', $source);
    }
}
