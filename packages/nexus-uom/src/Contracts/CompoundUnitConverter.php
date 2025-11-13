<?php

namespace Nexus\Uom\Contracts;

use Nexus\Uom\Models\UomCompoundUnit;
use Brick\Math\BigDecimal;

interface CompoundUnitConverter
{
    public function convert(BigDecimal|int|float|string $value, UomCompoundUnit|int|string $from, UomCompoundUnit|int|string $to, ?int $precision = null): BigDecimal;
}
