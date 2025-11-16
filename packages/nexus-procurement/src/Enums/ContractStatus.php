<?php

declare(strict_types=1);

namespace Nexus\Procurement\Enums;

/**
 * Procurement Contract Status
 */
enum ContractStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::EXPIRED => 'Expired',
            self::TERMINATED => 'Terminated',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING_APPROVAL => 'yellow',
            self::ACTIVE => 'green',
            self::SUSPENDED => 'orange',
            self::EXPIRED => 'red',
            self::TERMINATED => 'red',
            self::CANCELLED => 'red',
        };
    }
}