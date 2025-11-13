<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Unit\Services;

use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomCompoundComponent;
use Nexus\Uom\Models\UomCompoundUnit;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Services\DefaultCompoundUnitConverter;
use Nexus\Uom\Tests\TestCase;
use Brick\Math\BigDecimal;
use ReflectionMethod;

class DefaultCompoundUnitConverterTest extends TestCase
{
    private DefaultCompoundUnitConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = $this->app->make(DefaultCompoundUnitConverter::class);
    }

    public function testConvertBetweenCompoundUnits(): void
    {
        [$metresPerSecond, $kilometresPerHour] = $this->createVelocityCompounds();

        $result = $this->converter->convert('1', $kilometresPerHour, $metresPerSecond, precision: 6);

        $this->assertInstanceOf(BigDecimal::class, $result);
        $this->assertSame('0.277778', $result->__toString());

        $identity = $this->converter->convert('3', 'KM/HR', 'KM/HR');
        $this->assertSame('3.0000', $identity->toScale(4)->__toString());
    }

    public function testConvertSupportsNumericAndNamedIdentifiers(): void
    {
        [$metresPerSecond, $kilometresPerHour] = $this->createVelocityCompounds();

        $bigDecimal = BigDecimal::of('2');

        $result = $this->converter->convert(
            $bigDecimal,
            (string) $kilometresPerHour->getKey(),
            'Metres Per Second',
            precision: 6
        );

        $this->assertSame('0.555556', $result->__toString());
    }

    public function testConvertFailsWhenCompoundSignatureDiffers(): void
    {
        $type = UomType::factory()->create();
        $meter = UomUnit::factory()->for($type, 'type')->base()->create(['code' => 'M']);

        $compoundA = UomCompoundUnit::factory()->create();
        UomCompoundComponent::factory()->for($compoundA, 'compoundUnit')->for($meter, 'unit')->create(['exponent' => 1]);

        $compoundB = UomCompoundUnit::factory()->create();
        UomCompoundComponent::factory()->for($compoundB, 'compoundUnit')->for($meter, 'unit')->create(['exponent' => 2]);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('do not share the same dimensional structure');

        $this->converter->convert(1, $compoundA, $compoundB);
    }

    public function testConvertValidatesInput(): void
    {
        $compound = UomCompoundUnit::factory()->create();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Value 'invalid'");

        $this->converter->convert('invalid', $compound, $compound);
    }

    public function testConvertDeterminesScaleFromComponentPrecision(): void
    {
        $length = UomType::factory()->create();
        $time = UomType::factory()->create();
        $compoundType = UomType::factory()->create();

        $metre = UomUnit::factory()->for($length, 'type')->base()->create(['precision' => 2]);
        $second = UomUnit::factory()->for($time, 'type')->base()->create(['precision' => 5]);

        $compoundA = UomCompoundUnit::factory()->for($compoundType, 'type')->create();
        UomCompoundComponent::factory()->for($compoundA, 'compoundUnit')->for($metre, 'unit')->create(['exponent' => 1]);

        $compoundB = UomCompoundUnit::factory()->for($compoundType, 'type')->create();
        UomCompoundComponent::factory()->for($compoundB, 'compoundUnit')->for($second, 'unit')->create(['exponent' => -1]);

        $method = new ReflectionMethod(DefaultCompoundUnitConverter::class, 'determineScale');
        $method->setAccessible(true);

        $scale = $method->invoke($this->converter, null, $compoundA->fresh('components.unit'), $compoundB->fresh('components.unit'));

        $this->assertSame(5, $scale);
    }

    public function testResolveCompoundThrowsWhenModelIsMissing(): void
    {
        $compound = UomCompoundUnit::factory()->create();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Compound unit 'unknown'");

        $this->converter->convert('1', $compound, 'unknown');
    }

    public function testSignatureRequiresComponentType(): void
    {
        $compound = UomCompoundUnit::factory()->create();
        $component = new UomCompoundComponent(['exponent' => 1]);
        $component->setRelation('unit', new UomUnit(['uom_type_id' => null]));
        $compound->setRelation('components', collect([$component]));

        $method = new ReflectionMethod(DefaultCompoundUnitConverter::class, 'signatureFor');
        $method->setAccessible(true);

        $this->expectException(ConversionException::class);
    $this->expectExceptionMessage('associated unit type');

        $method->invoke($this->converter, $compound);
    }

    public function testCompoundFactorSkipsZeroAndHandlesNegativeExponents(): void
    {
        $type = UomType::factory()->create();
        $base = UomUnit::factory()->for($type, 'type')->base()->create([
            'code' => 'BASE',
            'conversion_factor' => '1',
        ]);

        $double = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'DBL',
            'conversion_factor' => '2',
            'offset' => '0',
        ]);
        $inverse = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'INV',
            'conversion_factor' => '4',
            'offset' => '0',
        ]);
        $neutral = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'NEU',
            'conversion_factor' => '3',
            'offset' => '0',
        ]);

        $compound = UomCompoundUnit::factory()->create();

        $positiveComponent = new UomCompoundComponent(['exponent' => 2]);
        $positiveComponent->setRelation('unit', $double);

        $zeroComponent = new UomCompoundComponent(['exponent' => 0]);
        $zeroComponent->setRelation('unit', $neutral);

        $negativeComponent = new UomCompoundComponent(['exponent' => -1]);
        $negativeComponent->setRelation('unit', $inverse);

        $compound->setRelation('components', collect([$positiveComponent, $zeroComponent, $negativeComponent]));

        $method = new ReflectionMethod(DefaultCompoundUnitConverter::class, 'compoundFactor');
        $method->setAccessible(true);

        $factor = $method->invoke($this->converter, $compound, 6);

        $this->assertSame('1.000000', $factor->__toString());
    }

    public function testCompoundFactorRequiresComponentType(): void
    {
        $compound = UomCompoundUnit::factory()->create();
        $component = new UomCompoundComponent(['exponent' => 1]);
        $component->setRelation('unit', new UomUnit(['uom_type_id' => null]));
        $compound->setRelation('components', collect([$component]));

        $method = new ReflectionMethod(DefaultCompoundUnitConverter::class, 'compoundFactor');
        $method->setAccessible(true);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('associated unit type');

        $method->invoke($this->converter, $compound, 4);
    }

    private function createVelocityCompounds(): array
    {
        $length = UomType::factory()->create();
        $time = UomType::factory()->create();
        $compoundType = UomType::factory()->create();

        $metre = UomUnit::factory()->for($length, 'type')->base()->create([
            'code' => 'M',
            'precision' => 4,
        ]);
        $second = UomUnit::factory()->for($time, 'type')->base()->create([
            'code' => 'S',
            'precision' => 6,
        ]);

        $kilometre = UomUnit::factory()->for($length, 'type')->create([
            'code' => 'KM',
            'conversion_factor' => '1000',
            'offset' => '0',
            'precision' => 3,
        ]);
        $hour = UomUnit::factory()->for($time, 'type')->create([
            'code' => 'HR',
            'conversion_factor' => '3600',
            'offset' => '0',
            'precision' => 5,
        ]);

        $metresPerSecond = UomCompoundUnit::factory()->for($compoundType, 'type')->create([
            'symbol' => 'M/S',
            'name' => 'Metres Per Second',
        ]);
        UomCompoundComponent::factory()->for($metresPerSecond, 'compoundUnit')->for($metre, 'unit')->create(['exponent' => 1]);
        UomCompoundComponent::factory()->for($metresPerSecond, 'compoundUnit')->for($second, 'unit')->create(['exponent' => -1]);

        $kilometresPerHour = UomCompoundUnit::factory()->for($compoundType, 'type')->create([
            'symbol' => 'KM/HR',
            'name' => 'Kilometres Per Hour',
        ]);
        UomCompoundComponent::factory()->for($kilometresPerHour, 'compoundUnit')->for($kilometre, 'unit')->create(['exponent' => 1]);
        UomCompoundComponent::factory()->for($kilometresPerHour, 'compoundUnit')->for($hour, 'unit')->create(['exponent' => -1]);
        $neutralUnit = UomUnit::factory()->for($length, 'type')->create([
            'conversion_factor' => '1',
            'offset' => '0',
        ]);
        UomCompoundComponent::factory()->for($kilometresPerHour, 'compoundUnit')->for($neutralUnit, 'unit')->create(['exponent' => 0]);

        return [$metresPerSecond->fresh('components.unit'), $kilometresPerHour->fresh('components.unit')];
    }
}
