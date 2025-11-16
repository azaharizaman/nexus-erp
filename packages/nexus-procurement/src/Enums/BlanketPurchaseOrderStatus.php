<?php

declare(strict_types=1);

namespace Nexus\Procurement\Enums;

/**
 * Blanket Purchase Order Status
 */
enum BlanketPurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'green',
            self::SUSPENDED => 'yellow',
            self::EXPIRED => 'red',
            self::CANCELLED => 'red',
        };
    }
}