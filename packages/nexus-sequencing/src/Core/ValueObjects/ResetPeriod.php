<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\ValueObjects;

/**
 * Reset Period Enumeration
 * 
 * Defines when sequence counters should reset to their starting value.
 * This is a pure enum with no external dependencies.
 * 
 * @package Nexus\Sequencing\Core\ValueObjects
 */
enum ResetPeriod: string
{
    case NEVER = 'never';
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * Get human-readable label for the reset period
     */
    public function label(): string
    {
        return match($this) {
            self::NEVER => 'Never Reset',
            self::DAILY => 'Daily Reset',
            self::MONTHLY => 'Monthly Reset',
            self::YEARLY => 'Yearly Reset',
        };
    }

    /**
     * Check if this period requires time-based reset calculation
     */
    public function isTimeBased(): bool
    {
        return $this !== self::NEVER;
    }

    /**
     * Get all available reset periods as array
     * 
     * @return array<string, string> Key-value pairs of value => label
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}