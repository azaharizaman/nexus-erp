<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Enums;

/**
 * Office Type Status Enum
 * 
 * Defines the different statuses that office types can have.
 */
enum OfficeTypeStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DEPRECATED = 'deprecated';

    /**
     * Get all available status values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DEPRECATED => 'Deprecated',
        };
    }

    /**
     * Check if the status is active.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get CSS class for the status.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'text-green-600 bg-green-100',
            self::INACTIVE => 'text-gray-600 bg-gray-100',
            self::DEPRECATED => 'text-red-600 bg-red-100',
        };
    }
}