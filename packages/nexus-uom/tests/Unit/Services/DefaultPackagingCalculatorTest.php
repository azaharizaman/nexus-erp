<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Unit\Services;

use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomAlias;
use Nexus\Uom\Models\UomPackaging;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Services\DefaultPackagingCalculator;
use Nexus\Uom\Tests\TestCase;
use Brick\Math\BigDecimal;

class DefaultPackagingCalculatorTest extends TestCase
{
    private DefaultPackagingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = $this->app->make(DefaultPackagingCalculator::class);
    }

    public function testResolvePackagingUsesAliases(): void
    {
        $base = UomUnit::factory()->create(['code' => 'EA']);
        $package = UomUnit::factory()->create(['code' => 'BOX']);
        UomAlias::factory()->for($base, 'unit')->create(['alias' => 'each', 'is_preferred' => true]);
        UomAlias::factory()->for($package, 'unit')->create(['alias' => 'box', 'is_preferred' => true]);

        $packaging = UomPackaging::factory()->for($base, 'baseUnit')->for($package, 'packageUnit')->create(['quantity' => 12]);

        $resolved = $this->calculator->resolvePackaging('each', 'box');

        $this->assertTrue($resolved->is($packaging));
        $this->assertTrue($resolved->relationLoaded('baseUnit'));
        $this->assertTrue($resolved->relationLoaded('packageUnit'));
    }

    public function testPackagesToBaseAndBack(): void
    {
        $base = UomUnit::factory()->create();
        $package = UomUnit::factory()->create();
        $packaging = UomPackaging::factory()->for($base, 'baseUnit')->for($package, 'packageUnit')->create(['quantity' => 6]);

        $toBase = $this->calculator->packagesToBase(4, $packaging, precision: 2);
        $this->assertInstanceOf(BigDecimal::class, $toBase);
        $this->assertSame('24.00', $toBase->__toString());

        $fromBase = $this->calculator->baseToPackages('18', $packaging, precision: 3);
        $this->assertSame('3.000', $fromBase->__toString());
    }

    public function testBaseToPackagesRejectsZeroQuantity(): void
    {
        $packaging = UomPackaging::factory()->create(['quantity' => 0]);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('No packaging relationship exists');

        $this->calculator->baseToPackages('10', $packaging);
    }

    public function testResolvePackagingThrowsWhenMissing(): void
    {
        $base = UomUnit::factory()->create(['code' => 'EA']);
        $package = UomUnit::factory()->create(['code' => 'CASE']);

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('No packaging relationship exists');

        $this->calculator->resolvePackaging($base, $package);
    }

    public function testPackagesToBaseValidatesInput(): void
    {
        $packaging = UomPackaging::factory()->create();

        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage("Value 'string'");

        $this->calculator->packagesToBase('string', $packaging);
    }

    public function testPackagesLookupById(): void
    {
        $packaging = UomPackaging::factory()->create(['quantity' => 3]);

        $result = $this->calculator->packagesToBase(2, $packaging->getKey());

        $this->assertSame('6.0000', $result->toScale(4)->__toString());
    }

    public function testPackagesToBaseAcceptsBigDecimalAndDefaultsPrecision(): void
    {
        $packaging = UomPackaging::factory()->create(['quantity' => 4]);

        $result = $this->calculator->packagesToBase(BigDecimal::of('2.5'), $packaging);

        $this->assertSame('10.0000', $result->toScale(4)->__toString());
    }

    public function testResolvePackagingModelThrowsForUnknownId(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Packaging record');

        $this->calculator->packagesToBase(1, 999999);
    }
}
