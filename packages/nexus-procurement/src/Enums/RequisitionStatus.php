<?php

declare(strict_types=1);

namespace Nexus\Procurement\Enums;

/**
 * Purchase Requisition Status Enum
 */
enum RequisitionStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CONVERTED = 'converted';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CONVERTED => 'Converted to PO',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if status allows editing.
     */
    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED]);
    }

    /**
     * Check if status allows approval.
     */
    public function canApprove(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }
}