<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

/**
 * Generation Exception
 *
 * Thrown when serial number generation fails due to business logic errors.
 */
class GenerationException extends \RuntimeException
{
    /**
     * Create exception for counter overflow.
     *
     * @param  string  $sequenceName
     * @return static
     */
    public static function counterOverflow(string $sequenceName): static
    {
        return new static("Counter overflow for sequence '{$sequenceName}'. Consider resetting or increasing padding.");
    }

    /**
     * Create exception for lock timeout.
     *
     * @param  string  $sequenceName
     * @return static
     */
    public static function lockTimeout(string $sequenceName): static
    {
        return new static("Failed to acquire lock for sequence '{$sequenceName}'. Please try again.");
    }

    /**
     * Create exception for invalid context.
     *
     * @param  string  $message
     * @return static
     */
    public static function invalidContext(string $message): static
    {
        return new static("Invalid generation context: {$message}");
    }
}
