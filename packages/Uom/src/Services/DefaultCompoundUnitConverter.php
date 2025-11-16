<?php

namespace Nexus\Uom\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\MathException;
use Illuminate\Contracts\Config\Repository;
use Nexus\Uom\Models\UomUnit;
use Nexus\Uom\Models\UomCompoundUnit;
use Nexus\Uom\Contracts\UnitConverter;
use Nexus\Uom\Exceptions\ConversionException;
use Nexus\Uom\Contracts\CompoundUnitConverter;

class DefaultCompoundUnitConverter implements CompoundUnitConverter
{
    private int $defaultPrecision;

    private int $mathScale;

    private RoundingMode $roundingMode = RoundingMode::HALF_UP;

    public function __construct(
        private readonly UnitConverter $unitConverter,
        Repository $config
    ) {
        $this->defaultPrecision = (int) $config->get('uom.conversion.default_precision', 4);
        $configuredScale = (int) $config->get('uom.conversion.math_scale', 12);
        $this->mathScale = max($configuredScale, $this->defaultPrecision + 4, 8);
    }

    public function convert(BigDecimal|int|float|string $value, UomCompoundUnit|int|string $from, UomCompoundUnit|int|string $to, ?int $precision = null): BigDecimal
    {
        $fromUnit = $this->resolveCompound($from);
        $toUnit = $this->resolveCompound($to);

        if ($fromUnit->is($toUnit)) {
            return $this->toBigDecimal($value)->toScale($precision ?? $this->defaultPrecision, $this->roundingMode);
        }

        $fromSignature = $this->signatureFor($fromUnit);
        $toSignature = $this->signatureFor($toUnit);

        if ($fromSignature !== $toSignature) {
            throw ConversionException::compoundStructureMismatch($fromUnit, $toUnit);
        }

        $scale = $this->determineScale($precision, $fromUnit, $toUnit);
        $intermediateScale = max($this->mathScale, $scale + 4);

        $valueDecimal = $this->toBigDecimal($value);
        $fromFactor = $this->compoundFactor($fromUnit, $intermediateScale);
        $toFactor = $this->compoundFactor($toUnit, $intermediateScale);

        $result = $valueDecimal
            ->multipliedBy($fromFactor)
            ->dividedBy($toFactor, $intermediateScale, $this->roundingMode);

        return $result->toScale($scale, $this->roundingMode);
    }

    private function resolveCompound(UomCompoundUnit|int|string $compound): UomCompoundUnit
    {
        if ($compound instanceof UomCompoundUnit) {
            return $compound->loadMissing('components.unit');
        }

        if (is_int($compound) || (is_string($compound) && ctype_digit($compound))) {
            $model = UomCompoundUnit::query()->with('components.unit')->find((int) $compound);
        } else {
            $identifier = trim((string) $compound);
            $model = UomCompoundUnit::query()
                ->with('components.unit')
                ->where(function ($query) use ($identifier): void {
                    $query->where('symbol', $identifier)
                        ->orWhere('name', $identifier);
                })
                ->first();
        }

        if (! $model) {
            throw ConversionException::compoundUnitNotFound((string) $compound);
        }

        return $model;
    }

    /**
     * @return array<int, int>
     */
    private function signatureFor(UomCompoundUnit $compound): array
    {
        $signature = [];

        foreach ($compound->components as $component) {
            $unit = $component->unit;

            if (! $unit instanceof UomUnit || $unit->uom_type_id === null) {
                throw ConversionException::compoundComponentMissingType($compound);
            }

            $typeId = (int) $unit->uom_type_id;
            $signature[$typeId] = ($signature[$typeId] ?? 0) + (int) $component->exponent;
        }

        ksort($signature);

        return $signature;
    }

    private function compoundFactor(UomCompoundUnit $compound, int $scale): BigDecimal
    {
        $factor = BigDecimal::one();

        foreach ($compound->components as $component) {
            $unit = $component->unit;

            if (! $unit instanceof UomUnit || $unit->uom_type_id === null) {
                throw ConversionException::compoundComponentMissingType($compound);
            }

            $componentFactor = $this->unitConverter->convertToBase(BigDecimal::one(), $unit, $scale);
            $power = abs((int) $component->exponent);

            if ($power === 0) {
                continue;
            }

            $raised = $componentFactor->power($power);

            if ($component->exponent >= 0) {
                $factor = $factor->multipliedBy($raised);
            } else {
                $factor = $factor->dividedBy($raised, $scale, $this->roundingMode);
            }
        }

        return $factor->toScale($scale, $this->roundingMode);
    }

    private function determineScale(?int $requested, UomCompoundUnit $from, UomCompoundUnit $to): int
    {
        if ($requested !== null) {
            return max(0, $requested);
        }

        $precisions = [];

        $collect = function (UomCompoundUnit $compound) use (&$precisions): void {
            foreach ($compound->components as $component) {
                if ($component->unit instanceof UomUnit && $component->unit->precision !== null) {
                    $precisions[] = (int) $component->unit->precision;
                }
            }
        };

        $collect($from);
        $collect($to);
        $precisions[] = $this->defaultPrecision;

        return (int) max($precisions);
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
}
