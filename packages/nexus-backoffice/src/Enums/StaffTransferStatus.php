<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Enums;

/**
 * Staff Transfer Status Enum
 * 
 * Represents the various states a staff transfer can be in throughout its lifecycle.
 * 
 * @package Nexus\BackofficeManagement\Enums
 */
enum StaffTransferStatus: string
{
    /**
     * Transfer has been requested but not yet approved
     */
    case PENDING = 'pending';
    
    /**
     * Transfer has been approved and is waiting for effective date
     */
    case APPROVED = 'approved';
    
    /**
     * Transfer request has been rejected
     */
    case REJECTED = 'rejected';
    
    /**
     * Transfer has been completed and staff has been moved
     */
    case COMPLETED = 'completed';
    
    /**
     * Transfer has been cancelled before completion
     */
    case CANCELLED = 'cancelled';
    
    /**
     * Get all possible status values
     * 
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Check if the status allows modification
     * 
     * @return bool
     */
    public function canBeModified(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED]);
    }
    
    /**
     * Check if the status is final (cannot be changed)
     * 
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::REJECTED, self::CANCELLED]);
    }
    
    /**
     * Check if the transfer can be processed
     * 
     * @return bool
     */
    public function canBeProcessed(): bool
    {
        return $this === self::APPROVED;
    }
    
    /**
     * Get the display label for the status
     * 
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    /**
     * Get CSS class for status styling
     * 
     * @return string
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::PENDING => 'status-pending',
            self::APPROVED => 'status-approved',
            self::REJECTED => 'status-rejected',
            self::COMPLETED => 'status-completed',
            self::CANCELLED => 'status-cancelled',
        };
    }
}