<?php

namespace Nexus\Uom\Support;

use Nexus\Uom\Contracts\UnitConverter;
use Nexus\Uom\Models\UomUnit;
use Brick\Math\BigDecimal;

/**
 * Convenience helpers around the package's unit conversion service binding.
 */
class UnitConversion
{
    public static function convert(BigDecimal|int|float|string $value, UomUnit|string $from, UomUnit|string $to, ?int $precision = null): BigDecimal
    {
        return app(UnitConverter::class)->convert($value, $from, $to, $precision);
    }

    public static function toBase(BigDecimal|int|float|string $value, UomUnit|string $unit, ?int $precision = null): BigDecimal
    {
        return app(UnitConverter::class)->convertToBase($value, $unit, $precision);
    }

    public static function fromBase(BigDecimal|int|float|string $value, UomUnit|string $unit, ?int $precision = null): BigDecimal
    {
        return app(UnitConverter::class)->convertFromBase($value, $unit, $precision);
    }
}
