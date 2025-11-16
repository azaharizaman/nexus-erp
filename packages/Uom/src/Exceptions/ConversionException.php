<?php

namespace Nexus\Uom\Exceptions;

use Nexus\Uom\Models\UomCompoundUnit;
use Nexus\Uom\Models\UomConversion;
use Nexus\Uom\Models\UomUnit;
use RuntimeException;
use Throwable;

class ConversionException extends RuntimeException
{
    public static function unitNotFound(string $identifier): self
    {
        return new self("Unit '{$identifier}' could not be found for conversion.");
    }

    public static function incompatibleTypes(UomUnit $from, UomUnit $to): self
    {
        return new self(sprintf(
            'Units %s and %s belong to different types and cannot be converted without explicit conversion rules.',
            $from->code,
            $to->code
        ));
    }

    public static function baseUnitMissing(int $typeId): self
    {
        return new self("No base unit is registered for unit type ID {$typeId}.");
    }

    public static function nonLinearConversion(UomConversion $conversion): self
    {
        return new self(sprintf(
            'Conversion record %d is non-linear or uses a custom formula which is not supported by the default converter.',
            $conversion->id
        ));
    }

    public static function conversionDivisionByZero(UomConversion $conversion): self
    {
        return new self(sprintf(
            'Conversion record %d specifies a zero factor which would result in division by zero.',
            $conversion->id
        ));
    }

    public static function unitHasZeroFactor(UomUnit $unit): self
    {
        return new self(sprintf(
            'Unit %s declares a zero conversion factor and cannot be used for conversion.',
            $unit->code
        ));
    }

    public static function invalidInput(mixed $value, ?Throwable $previous = null): self
    {
        $display = is_scalar($value) ? (string) $value : get_debug_type($value);

        return new self("Value '{$display}' cannot be converted to a numeric representation for conversion.", previous: $previous);
    }

    public static function pathNotFound(UomUnit $from, UomUnit $to): self
    {
        return new self(sprintf(
            'No conversion path found between %s and %s.',
            $from->code,
            $to->code
        ));
    }

    public static function compoundUnitNotFound(string|int $identifier): self
    {
        return new self("Compound unit '{$identifier}' could not be found for conversion.");
    }

    /**
     * @param UomCompoundUnit $from
     * @param UomCompoundUnit $to
     */
    public static function compoundStructureMismatch($from, $to): self
    {
        if (! $from instanceof UomCompoundUnit || ! $to instanceof UomCompoundUnit) {
            return new self('Compound unit conversion attempted with invalid arguments.');
        }

        $fromLabel = $from->symbol ?: $from->name ?: (string) $from->id;
        $toLabel = $to->symbol ?: $to->name ?: (string) $to->id;

        return new self(sprintf(
            'Compound units %s and %s do not share the same dimensional structure.',
            $fromLabel,
            $toLabel
        ));
    }

    /**
     * @param UomCompoundUnit $compound
     */
    public static function compoundComponentMissingType($compound): self
    {
        if (! $compound instanceof UomCompoundUnit) {
            return new self('Compound unit references could not be validated because an invalid model instance was provided.');
        }

        $label = $compound->symbol ?: $compound->name ?: (string) $compound->id;

        return new self(sprintf(
            'Compound unit %s references a component without an associated unit type.',
            $label
        ));
    }

    public static function packagingPathNotFound(UomUnit $base, UomUnit $package): self
    {
        return new self(sprintf(
            'No packaging relationship exists between base unit %s and package unit %s.',
            $base->code,
            $package->code
        ));
    }

    public static function customUnitConflict(string $code): self
    {
        return new self(sprintf(
            "Custom unit code '%s' already exists for the given owner context.",
            strtoupper($code)
        ));
    }

    public static function customUnitHasZeroFactor(string $code): self
    {
        return new self(sprintf(
            "Custom unit '%s' declares a zero conversion factor and cannot be registered.",
            strtoupper($code)
        ));
    }

    public static function customUnitNotFound(string|int $identifier): self
    {
        return new self("Custom unit '{$identifier}' could not be found.");
    }

    public static function customFormulaNotAllowed(): self
    {
        return new self('Custom unit formulas are disabled by configuration and cannot be registered.');
    }

    public static function customConversionHasZeroFactor(string $sourceCode, string $targetCode): self
    {
        return new self(sprintf(
            "Custom conversion between '%s' and '%s' specifies a zero factor and cannot be registered.",
            strtoupper($sourceCode),
            strtoupper($targetCode)
        ));
    }

    public static function packagingRecordNotFound(string|int $identifier): self
    {
        return new self("Packaging record '{$identifier}' could not be found.");
    }
}
