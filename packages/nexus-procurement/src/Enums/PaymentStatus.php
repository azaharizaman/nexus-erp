<?php

declare(strict_types=1);

namespace Nexus\Procurement\Enums;

/**
 * Payment Status Enum
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case PAID = 'paid';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::AUTHORIZED => 'Authorized',
            self::PAID => 'Paid',
        };
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this === self::PAID;
    }

    /**
     * Check if payment is authorized.
     */
    public function isAuthorized(): bool
    {
        return in_array($this, [self::AUTHORIZED, self::PAID]);
    }
}