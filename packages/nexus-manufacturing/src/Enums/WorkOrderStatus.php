<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Enums;

enum WorkOrderStatus: string
{
    case PLANNED = 'planned';
    case RELEASED = 'released';
    case IN_PRODUCTION = 'in_production';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PLANNED => 'Planned',
            self::RELEASED => 'Released',
            self::IN_PRODUCTION => 'In Production',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canRelease(): bool
    {
        return $this === self::PLANNED;
    }

    public function canStartProduction(): bool
    {
        return $this === self::RELEASED;
    }

    public function canPause(): bool
    {
        return $this === self::IN_PRODUCTION;
    }

    public function canResume(): bool
    {
        return $this === self::ON_HOLD;
    }

    public function canComplete(): bool
    {
        return $this === self::IN_PRODUCTION;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::PLANNED, self::RELEASED, self::IN_PRODUCTION, self::ON_HOLD]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::RELEASED, self::IN_PRODUCTION, self::ON_HOLD]);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }
}
