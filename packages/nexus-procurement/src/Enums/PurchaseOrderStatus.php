<?php

declare(strict_types=1);

namespace Nexus\Procurement\Enums;

/**
 * Purchase Order Status Enum
 */
enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case SENT = 'sent';
    case PARTIALLY_RECEIVED = 'partially_received';
    case FULLY_RECEIVED = 'fully_received';
    case CLOSED = 'closed';
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
            self::SENT => 'Sent to Vendor',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::FULLY_RECEIVED => 'Fully Received',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Check if status allows editing.
     */
    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if status allows sending to vendor.
     */
    public function canSend(): bool
    {
        return in_array($this, [self::DRAFT, self::APPROVED]);
    }

    /**
     * Check if PO is in receiving phase.
     */
    public function isReceiving(): bool
    {
        return in_array($this, [self::SENT, self::PARTIALLY_RECEIVED]);
    }
}