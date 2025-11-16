<?php

namespace Nexus\Uom\Tests\Feature;

use Nexus\Uom\Contracts\AliasResolver;
use Nexus\Uom\Contracts\PackagingCalculator;
use Nexus\Uom\Contracts\UnitConverter;
use Nexus\Uom\Tests\TestCase;

class ConversionFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineDataset();
    }

    public function test_it_converts_between_units(): void
    {
        $converter = $this->app->make(UnitConverter::class);

        $result = $converter->convert('2', 'KG', 'G', 3);

        $this->assertSame('2000.000', $result->__toString());
    }

    public function test_alias_resolution_prefers_known_units(): void
    {
        $resolver = $this->app->make(AliasResolver::class);

        $unit = $resolver->resolveOrFail('kilo');

        $this->assertSame('KG', $unit->code);
    }

    public function test_packaging_calculator_translates_between_package_and_base(): void
    {
        $packagingCalculator = $this->app->make(PackagingCalculator::class);
        $packaging = $packagingCalculator->resolvePackaging('G', 'KG');

        $packages = $packagingCalculator->baseToPackages('2500', $packaging, 2);
        $baseQuantity = $packagingCalculator->packagesToBase('2', $packaging, 0);

        $this->assertSame('2.50', $packages->__toString());
        $this->assertSame('2000', $baseQuantity->__toString());
    }
}
