<?php

namespace Nexus\Uom\Services;

use Nexus\Uom\Contracts\AliasResolver;
use Nexus\Uom\Contracts\PackagingCalculator;
use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomPackaging;
use Nexus\Uom\Models\UomUnit;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Config\Repository;

class DefaultPackagingCalculator implements PackagingCalculator
{
    private int $defaultPrecision;

    private RoundingMode $roundingMode = RoundingMode::HALF_UP;

    public function __construct(
        private readonly AliasResolver $aliases,
        Repository $config
    ) {
        $this->defaultPrecision = (int) $config->get('uom.conversion.default_precision', 4);
    }

    public function resolvePackaging(UomUnit|string|int $base, UomUnit|string|int $package): UomPackaging
    {
        $baseUnit = $base instanceof UomUnit ? $base : $this->aliases->resolveOrFail((string) $base);
        $packageUnit = $package instanceof UomUnit ? $package : $this->aliases->resolveOrFail((string) $package);

        $packaging = UomPackaging::query()
            ->with(['baseUnit', 'packageUnit'])
            ->where('base_unit_id', $baseUnit->id)
            ->where('package_unit_id', $packageUnit->id)
            ->first();

        if (! $packaging) {
            throw ConversionException::packagingPathNotFound($baseUnit, $packageUnit);
        }

        return $packaging;
    }

    public function packagesToBase(BigDecimal|int|float|string $packages, UomPackaging|int $packaging, ?int $precision = null): BigDecimal
    {
        $model = $this->resolvePackagingModel($packaging);
        $quantity = $this->toBigDecimal($packages);
        $perPackage = BigDecimal::of($model->quantity);

        $result = $quantity->multipliedBy($perPackage);

        return $result->toScale($this->precision($precision), $this->roundingMode);
    }

    public function baseToPackages(BigDecimal|int|float|string $baseQuantity, UomPackaging|int $packaging, ?int $precision = null): BigDecimal
    {
        $model = $this->resolvePackagingModel($packaging);
        $quantity = $this->toBigDecimal($baseQuantity);
        $perPackage = BigDecimal::of($model->quantity);

        if ($perPackage->isZero()) {
            throw ConversionException::packagingPathNotFound($model->baseUnit, $model->packageUnit);
        }

        $result = $quantity->dividedBy($perPackage, $this->precision($precision) + 2, $this->roundingMode);

        return $result->toScale($this->precision($precision), $this->roundingMode);
    }

    private function resolvePackagingModel(UomPackaging|int $packaging): UomPackaging
    {
        if ($packaging instanceof UomPackaging) {
            return $packaging->loadMissing(['baseUnit', 'packageUnit']);
        }

        $model = UomPackaging::query()
            ->with(['baseUnit', 'packageUnit'])
            ->find((int) $packaging);

        if (! $model) {
            throw ConversionException::packagingRecordNotFound($packaging);
        }

        return $model;
    }

    private function toBigDecimal(BigDecimal|int|float|string $value): BigDecimal
    {
        if ($value instanceof BigDecimal) {
            return $value;
        }

        try {
            return BigDecimal::of($value);
        } catch (MathException $exception) {
            throw ConversionException::invalidInput($value, $exception);
        }
    }

    private function precision(?int $requested): int
    {
        if ($requested !== null) {
            return max(0, $requested);
        }

        return $this->defaultPrecision;
    }
}
