<?php

declare(strict_types=1);

namespace Nexus\Uom\Tests\Unit\Support;

use Nexus\Uom\Contracts\UnitConverter;
use Nexus\Uom\Support\UnitConversion;
use Nexus\Uom\Tests\TestCase;
use Brick\Math\BigDecimal;

class UnitConversionTest extends TestCase
{
    public function testHelpersProxyToUnitConverterBinding(): void
    {
        $stub = new class implements UnitConverter {
            public array $calls = [];

            public function convert(BigDecimal|int|float|string $value, $from, $to, ?int $precision = null): BigDecimal
            {
                $this->calls[] = ['convert', $value, $from, $to, $precision];

                return BigDecimal::of('5');
            }

            public function convertToBase(BigDecimal|int|float|string $value, $unit, ?int $precision = null): BigDecimal
            {
                $this->calls[] = ['toBase', $value, $unit, $precision];

                return BigDecimal::of('10');
            }

            public function convertFromBase(BigDecimal|int|float|string $value, $unit, ?int $precision = null): BigDecimal
            {
                $this->calls[] = ['fromBase', $value, $unit, $precision];

                return BigDecimal::of('15');
            }
        };

        $this->app->instance(UnitConverter::class, $stub);

        $this->assertSame('5', UnitConversion::convert('2.5', 'M', 'CM')->__toString());
        $this->assertSame('10', UnitConversion::toBase('4', 'M')->__toString());
        $this->assertSame('15', UnitConversion::fromBase('8', 'CM')->__toString());

        $this->assertSame(
            [
                ['convert', '2.5', 'M', 'CM', null],
                ['toBase', '4', 'M', null],
                ['fromBase', '8', 'CM', null],
            ],
            $stub->calls
        );
    }
}
