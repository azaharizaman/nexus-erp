<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

/**
 * Sequence Configuration Exception
 *
 * Thrown when sequence configuration is invalid or inconsistent.
 */
class SequenceConfigurationException extends \InvalidArgumentException
{
    /**
     * Create exception for invalid reset period.
     *
     * @param  string  $resetPeriod
     * @return static
     */
    public static function invalidResetPeriod(string $resetPeriod): static
    {
        return new static("Invalid reset period '{$resetPeriod}'. Must be one of: never, daily, monthly, yearly.");
    }

    /**
     * Create exception for invalid step size.
     *
     * @param  int  $stepSize
     * @return static
     */
    public static function invalidStepSize(int $stepSize): static
    {
        return new static("Invalid step size '{$stepSize}'. Must be greater than 0.");
    }

    /**
     * Create exception for invalid padding.
     *
     * @param  int  $padding
     * @return static
     */
    public static function invalidPadding(int $padding): static
    {
        return new static("Invalid padding '{$padding}'. Must be between 1 and 20.");
    }

    /**
     * Create exception for duplicate sequence.
     *
     * @param  string  $tenantId
     * @param  string  $sequenceName
     * @return static
     */
    public static function duplicateSequence(string $tenantId, string $sequenceName): static
    {
        return new static("Sequence '{$sequenceName}' already exists for tenant '{$tenantId}'.");
    }
}
