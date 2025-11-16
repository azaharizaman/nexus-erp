<?php

declare(strict_types=1);

namespace Nexus\Procurement\Enums;

/**
 * Blanket PO Release Status
 */
enum BlanketPOReleaseStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CONVERTED_TO_PO = 'converted_to_po';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CONVERTED_TO_PO => 'Converted to PO',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING_APPROVAL => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CONVERTED_TO_PO => 'blue',
            self::CANCELLED => 'red',
        };
    }
}