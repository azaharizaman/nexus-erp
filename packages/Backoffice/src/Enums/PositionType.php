<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Enums;

/**
 * Position type enum representing different hierarchical levels in an organization.
 * 
 * @package Nexus\BackofficeManagement\Enums
 */
enum PositionType: string
{
    case C_LEVEL = 'c_level';
    case TOP_MANAGEMENT = 'top_management';
    case MANAGEMENT = 'management';
    case JUNIOR_MANAGEMENT = 'junior_management';
    case SENIOR_EXECUTIVE = 'senior_executive';
    case EXECUTIVE = 'executive';
    case JUNIOR_EXECUTIVE = 'junior_executive';
    case NON_EXECUTIVE = 'non_executive';
    case CLERICAL = 'clerical';
    case ASSISTANT = 'assistant';

    /**
     * Get human-readable label for the position type.
     */
    public function label(): string
    {
        return match ($this) {
            self::C_LEVEL => 'C-Level',
            self::TOP_MANAGEMENT => 'Top Management',
            self::MANAGEMENT => 'Management',
            self::JUNIOR_MANAGEMENT => 'Junior Management',
            self::SENIOR_EXECUTIVE => 'Senior Executive',
            self::EXECUTIVE => 'Executive',
            self::JUNIOR_EXECUTIVE => 'Junior Executive',
            self::NON_EXECUTIVE => 'Non-Executive',
            self::CLERICAL => 'Clerical',
            self::ASSISTANT => 'Assistant',
        };
    }

    /**
     * Get all position types as an array of values.
     * 
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all position types as an associative array of value => label.
     * 
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    /**
     * Get the hierarchical level (lower number = higher level).
     */
    public function level(): int
    {
        return match ($this) {
            self::C_LEVEL => 1,
            self::TOP_MANAGEMENT => 2,
            self::MANAGEMENT => 3,
            self::JUNIOR_MANAGEMENT => 4,
            self::SENIOR_EXECUTIVE => 5,
            self::EXECUTIVE => 6,
            self::JUNIOR_EXECUTIVE => 7,
            self::NON_EXECUTIVE => 8,
            self::CLERICAL => 9,
            self::ASSISTANT => 10,
        };
    }

    /**
     * Check if this position type is management level.
     */
    public function isManagement(): bool
    {
        return in_array($this, [
            self::C_LEVEL,
            self::TOP_MANAGEMENT,
            self::MANAGEMENT,
            self::JUNIOR_MANAGEMENT,
        ]);
    }

    /**
     * Check if this position type is executive level.
     */
    public function isExecutive(): bool
    {
        return in_array($this, [
            self::SENIOR_EXECUTIVE,
            self::EXECUTIVE,
            self::JUNIOR_EXECUTIVE,
        ]);
    }
}
