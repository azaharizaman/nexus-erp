<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Enums;

/**
 * Staff Status Enum
 * 
 * Defines the different statuses that staff can have.
 */
enum StaffStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';
    case TERMINATED = 'terminated';
    case RESIGNED = 'resigned';
    case RETIRED = 'retired';

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
            self::ON_LEAVE => 'On Leave',
            self::TERMINATED => 'Terminated',
            self::RESIGNED => 'Resigned',
            self::RETIRED => 'Retired',
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
     * Check if the staff is available for work.
     */
    public function isAvailable(): bool
    {
        return in_array($this, [self::ACTIVE, self::ON_LEAVE]);
    }

    /**
     * Check if the status is considered terminated.
     */
    public function isTerminated(): bool
    {
        return in_array($this, [self::TERMINATED, self::RESIGNED, self::RETIRED]);
    }

    /**
     * Check if the staff has resigned.
     */
    public function isResigned(): bool
    {
        return $this === self::RESIGNED;
    }

    /**
     * Get CSS class for the status.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'text-green-600 bg-green-100',
            self::INACTIVE => 'text-gray-600 bg-gray-100',
            self::ON_LEAVE => 'text-yellow-600 bg-yellow-100',
            self::TERMINATED => 'text-red-600 bg-red-100',
            self::RESIGNED => 'text-orange-600 bg-orange-100',
            self::RETIRED => 'text-purple-600 bg-purple-100',
        };
    }
}