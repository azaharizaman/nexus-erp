<?php

declare(strict_types=1);

namespace Nexus\Procurement\Enums;

/**
 * Three-Way Match Status Enum
 */
enum MatchStatus: string
{
    case PENDING = 'pending';
    case MATCHED = 'matched';
    case VARIANCE = 'variance';
    case REJECTED = 'rejected';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending Match',
            self::MATCHED => 'Matched',
            self::VARIANCE => 'Variance Found',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Check if status allows payment authorization.
     */
    public function canAuthorizePayment(): bool
    {
        return $this === self::MATCHED;
    }

    /**
     * Check if status requires manual review.
     */
    public function requiresReview(): bool
    {
        return in_array($this, [self::VARIANCE, self::REJECTED]);
    }
}