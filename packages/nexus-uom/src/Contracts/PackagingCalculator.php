<?php

namespace Nexus\Uom\Contracts;

use Nexus\Uom\Models\UomPackaging;
use Nexus\Uom\Models\UomUnit;
use Brick\Math\BigDecimal;

interface PackagingCalculator
{
    public function resolvePackaging(UomUnit|string|int $base, UomUnit|string|int $package): UomPackaging;

    public function packagesToBase(BigDecimal|int|float|string $packages, UomPackaging|int $packaging, ?int $precision = null): BigDecimal;

    public function baseToPackages(BigDecimal|int|float|string $baseQuantity, UomPackaging|int $packaging, ?int $precision = null): BigDecimal;
}
