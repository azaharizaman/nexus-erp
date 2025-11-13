<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Unit\Services;

use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomConversion;
use Nexus\Uom\Models\UomType;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Services\DefaultUnitConverter;
use Nexus\Uom\Tests\TestCase;
use Brick\Math\BigDecimal;

class DefaultUnitConverterTest extends TestCase
{
    private DefaultUnitConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = $this->app->make(DefaultUnitConverter::class);
    }

    public function testConvertReturnsOriginalValueForIdenticalUnits(): void
    {
        $type = UomType::factory()->create();
        $meter = UomUnit::factory()->for($type, 'type')->base()->create(['code' => 'M']);

        $result = $this->converter->convert('5.500', 'm', 'M');

        $this->assertInstanceOf(BigDecimal::class, $result);
        $this->assertSame('5.5000', $result->toScale(4)->__toString());
        $this->assertTrue($this->converter->convertToBase('5.5', $meter)->isEqualTo($result));
    }

    public function testConvertBetweenUnitsViaBase(): void
    {
        $type = UomType::factory()->create();
        UomUnit::factory()->for($type, 'type')->base()->create([
            'code' => 'M',
            'conversion_factor' => '1',
            'offset' => '0',
            'precision' => 4,
        ]);

        $centimetre = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'CM',
            'conversion_factor' => '0.01',
            'offset' => '0',
            'precision' => 3,
        ]);

        $kilometre = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'KM',
            'conversion_factor' => '1000',
            'offset' => '0',
            'precision' => 6,
        ]);

        $result = $this->converter->convert('250', 'CM', 'KM', precision: 6);

        $this->assertSame('0.002500', $result->__toString());

        $toBase = $this->converter->convertToBase('500', $centimetre);
        $this->assertSame('5.0000', $toBase->toScale(4)->__toString());

        $fromBase = $this->converter->convertFromBase('2', $centimetre, precision: 2);
        $this->assertSame('200.00', $fromBase->__toString());
    }

    public function testDirectConversionIsPreferredWhenAvailable(): void
    {
        $type = UomType::factory()->create();
        $gallon = UomUnit::factory()->for($type, 'type')->base()->create([
            'code' => 'GAL',
            'conversion_factor' => '1',
            'offset' => '0',
        ]);
        $litre = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'L',
            'conversion_factor' => '3.78541',
            'offset' => '0',
        ]);

        UomConversion::factory()
            ->for($gallon, 'sourceUnit')
            ->for($litre, 'targetUnit')
            ->linear()
            ->create([
                'factor' => '3.78541',
                'offset' => '0',
                'direction' => 'both',
            ]);

        $forward = $this->converter->convert(1, 'GAL', 'L', precision: 5);
        $this->assertSame('3.78541', $forward->__toString());

        $reverse = $this->converter->convert('7.57082', 'L', 'GAL', precision: 5);
        $this->assertSame('2.00000', $reverse->__toString());
    }

    public function testConvertThrowsWhenTypesDiffer(): void
    {
        $length = UomType::factory()->create();
        $mass = UomType::factory()->create();

        $meter = UomUnit::factory()->for($length, 'type')->base()->create(['code' => 'M']);
        $gram = UomUnit::factory()->for($mass, 'type')->base()->create(['code' => 'G']);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Units M and G belong to different types');

        $this->converter->convert('1', $meter, $gram);
    }

    public function testConvertValidatesInput(): void
    {
        $type = UomType::factory()->create();
        UomUnit::factory()->for($type, 'type')->base()->create(['code' => 'M']);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Value 'abc'");

        $this->converter->convert('abc', 'M', 'M');
    }

    public function testConvertToBaseRejectsZeroFactorUnits(): void
    {
        $type = UomType::factory()->create();
        UomUnit::factory()->for($type, 'type')->base()->create(['code' => 'M']);
        $unit = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'ERR',
            'conversion_factor' => '0',
            'offset' => '0',
        ]);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('declares a zero conversion factor');

        $this->converter->convertToBase('10', $unit);
    }

    public function testConvertFromBaseDetectsMissingBaseUnit(): void
    {
        $type = UomType::factory()->create();
        $unit = UomUnit::factory()->for($type, 'type')->create([
            'code' => 'NEB',
            'is_base' => false,
            'conversion_factor' => '2',
            'offset' => '0',
        ]);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('No base unit is registered');

        $this->converter->convertFromBase('1', $unit);
    }
}
