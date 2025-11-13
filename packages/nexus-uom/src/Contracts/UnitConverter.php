<?php

namespace Nexus\Uom\Contracts;

use Brick\Math\BigDecimal;
use Nexus\Uom\Models\UomUnit;

/**
 * Handles conversion between units using package models and configuration.
 */
interface UnitConverter
{
    /**
     * Convert a quantity from one unit to another.
     *
     * @param BigDecimal|int|float|string $value
     * @param UomUnit|string $from
     * @param UomUnit|string $to
     */
    public function convert(BigDecimal|int|float|string $value, UomUnit|string $from, UomUnit|string $to, ?int $precision = null): BigDecimal;

    /**
     * Convert a quantity from the given unit into its base unit.
     *
     * @param BigDecimal|int|float|string $value
     * @param UomUnit|string $unit
     */
    public function convertToBase(BigDecimal|int|float|string $value, UomUnit|string $unit, ?int $precision = null): BigDecimal;

    /**
     * Convert a quantity from the base unit of a type into the provided unit.
     *
     * @param BigDecimal|int|float|string $value
     * @param UomUnit|string $unit
     */
    public function convertFromBase(BigDecimal|int|float|string $value, UomUnit|string $unit, ?int $precision = null): BigDecimal;
}
