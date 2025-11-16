<?php

namespace Nexus\Uom\Services;

use Nexus\Uom\Contracts\UnitConverter;
use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Models\UomConversion;
use Nexus\Uom\Models\UomUnit;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Config\Repository;

class DefaultUnitConverter implements UnitConverter
{
    private int $defaultPrecision;

    private int $mathScale;

    private RoundingMode $roundingMode = RoundingMode::HALF_UP;

    /**
     * @var array<int, UomUnit>
     */
    private array $baseUnitCache = [];

    public function __construct(Repository $config)
    {
        $this->defaultPrecision = (int) $config->get('uom.conversion.default_precision', 4);
        $configuredScale = (int) $config->get('uom.conversion.math_scale', 12);
        $this->mathScale = max($configuredScale, $this->defaultPrecision + 4, 8);
    }

    public function convert(BigDecimal|int|float|string $value, UomUnit|string $from, UomUnit|string $to, ?int $precision = null): BigDecimal
    {
        $fromUnit = $this->resolveUnit($from);
        $toUnit = $this->resolveUnit($to);
        $scale = $this->determineScale($precision, [$fromUnit, $toUnit]);
        $intermediateScale = $this->intermediateScale($scale);
        $decimalValue = $this->toBigDecimal($value);

        if ($this->isSameUnit($fromUnit, $toUnit)) {
            return $decimalValue->toScale($scale, $this->roundingMode);
        }

        if ($direct = $this->attemptDirectConversion($decimalValue, $fromUnit, $toUnit, $scale)) {
            return $direct;
        }

        if ($fromUnit->uom_type_id !== null && $fromUnit->uom_type_id === $toUnit->uom_type_id) {
            return $this->convertViaBase($decimalValue, $fromUnit, $toUnit, $scale, $intermediateScale);
        }

        throw ConversionException::incompatibleTypes($fromUnit, $toUnit);
    }

    public function convertToBase(BigDecimal|int|float|string $value, UomUnit|string $unit, ?int $precision = null): BigDecimal
    {
        $uomUnit = $this->resolveUnit($unit);
        $baseUnit = $this->getBaseUnitForType((int) $uomUnit->uom_type_id);
        $scale = $this->determineScale($precision, [$uomUnit, $baseUnit]);
        $intermediateScale = $this->intermediateScale($scale);
        $decimalValue = $this->toBigDecimal($value);

        $result = $this->convertUnitToBase($decimalValue, $uomUnit, $intermediateScale);

        return $result->toScale($scale, $this->roundingMode);
    }

    public function convertFromBase(BigDecimal|int|float|string $value, UomUnit|string $unit, ?int $precision = null): BigDecimal
    {
        $target = $this->resolveUnit($unit);
        $baseUnit = $this->getBaseUnitForType((int) $target->uom_type_id);
        $scale = $this->determineScale($precision, [$baseUnit, $target]);
        $intermediateScale = $this->intermediateScale($scale);
        $decimalValue = $this->toBigDecimal($value);

        $result = $this->convertBaseToUnit($decimalValue, $target, $intermediateScale);

        return $result->toScale($scale, $this->roundingMode);
    }

    private function attemptDirectConversion(BigDecimal $value, UomUnit $from, UomUnit $to, int $scale): ?BigDecimal
    {
        $conversion = $from->conversionsFrom()->where('target_unit_id', $to->id)->first();
        if ($conversion) {
            $result = $this->applyConversionRecord($conversion, $value, $scale, false);
            if ($result !== null) {
                return $result;
            }
        }

        $reverse = $to->conversionsFrom()->where('target_unit_id', $from->id)->first();
        if ($reverse) {
            $result = $this->applyConversionRecord($reverse, $value, $scale, true);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    private function applyConversionRecord(UomConversion $conversion, BigDecimal $value, int $scale, bool $reverse): ?BigDecimal
    {
        if (! $conversion->is_linear || $conversion->formula) {
            throw ConversionException::nonLinearConversion($conversion);
        }

        $direction = $conversion->direction ?? 'both';
        $factor = $this->toBigDecimal($conversion->factor ?? '1');
        $offset = $this->toBigDecimal($conversion->offset ?? '0');

        if ($reverse) {
            if ($direction === 'to_target') {
                return null;
            }

            if ($factor->isZero()) {
                throw ConversionException::conversionDivisionByZero($conversion);
            }

            $result = $value;

            if (! $offset->isZero()) {
                $result = $result->minus($offset);
            }

            $result = $result->dividedBy($factor, $this->mathScale, $this->roundingMode);

            return $result->toScale($scale, $this->roundingMode);
        }

        if ($direction === 'from_target') {
            return null;
        }

        $result = $value->multipliedBy($factor);

        if (! $offset->isZero()) {
            $result = $result->plus($offset);
        }

        return $result->toScale($scale, $this->roundingMode);
    }

    private function convertViaBase(BigDecimal $value, UomUnit $from, UomUnit $to, int $scale, int $intermediateScale): BigDecimal
    {
        $base = $this->getBaseUnitForType((int) $from->uom_type_id);

        $baseValue = $this->convertUnitToBase($value, $from, $intermediateScale);
        $projected = $this->convertBaseToUnit($baseValue, $to, $intermediateScale);

        return $projected->toScale($scale, $this->roundingMode);
    }

    private function convertUnitToBase(BigDecimal $value, UomUnit $unit, int $scale): BigDecimal
    {
        if ($unit->is_base) {
            return $value->toScale($scale, $this->roundingMode);
        }

        $factor = $this->toBigDecimal($unit->conversion_factor ?? '1');

        if ($factor->isZero()) {
            throw ConversionException::unitHasZeroFactor($unit);
        }

        $offset = $this->toBigDecimal($unit->offset ?? '0');

        $result = $value->multipliedBy($factor);

        if (! $offset->isZero()) {
            $result = $result->plus($offset);
        }

        return $result->toScale($scale, $this->roundingMode);
    }

    private function convertBaseToUnit(BigDecimal $value, UomUnit $unit, int $scale): BigDecimal
    {
        if ($unit->is_base) {
            return $value->toScale($scale, $this->roundingMode);
        }

        $offset = $this->toBigDecimal($unit->offset ?? '0');
        $result = $value;

        if (! $offset->isZero()) {
            $result = $result->minus($offset);
        }

        $factor = $this->toBigDecimal($unit->conversion_factor ?? '1');

        if ($factor->isZero()) {
            throw ConversionException::unitHasZeroFactor($unit);
        }

        $result = $result->dividedBy($factor, $this->mathScale, $this->roundingMode);

        return $result->toScale($scale, $this->roundingMode);
    }

    private function resolveUnit(UomUnit|string $unit): UomUnit
    {
        if ($unit instanceof UomUnit) {
            return $unit;
        }

        $trimmed = trim($unit);
        $code = strtoupper($trimmed);

        if ($code === '') {
            throw ConversionException::unitNotFound($trimmed);
        }

        $record = UomUnit::query()->where('code', $code)->first();

        if (! $record) {
            throw ConversionException::unitNotFound($code);
        }

        return $record;
    }

    private function getBaseUnitForType(int $typeId): UomUnit
    {
        if (! isset($this->baseUnitCache[$typeId])) {
            $base = UomUnit::query()
                ->where('uom_type_id', $typeId)
                ->where('is_base', true)
                ->first();

            if (! $base) {
                throw ConversionException::baseUnitMissing($typeId);
            }

            $this->baseUnitCache[$typeId] = $base;
        }

        return $this->baseUnitCache[$typeId];
    }

    private function determineScale(?int $requested, array $units): int
    {
        if ($requested !== null) {
            return max(0, $requested);
        }

        $candidates = array_map(
            fn (UomUnit $unit) => $unit->precision ?? null,
            $units
        );

        $candidates[] = $this->defaultPrecision;

        $filtered = array_filter($candidates, static fn ($value) => $value !== null);

        return (int) max($filtered ?: [$this->defaultPrecision]);
    }

    private function intermediateScale(int $desired): int
    {
        return max($this->mathScale, $desired + 4);
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

    private function isSameUnit(UomUnit $from, UomUnit $to): bool
    {
        if ($from->id !== null && $to->id !== null && $from->id === $to->id) {
            return true;
        }

        return strcasecmp($from->code, $to->code) === 0;
    }
}
