<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Models\Company;

/**
 * Notification Contract
 * 
 * Abstracts notification functionality to remove direct dependencies
 * on specific notification systems.
 */
interface NotificationContract
{
    /**
     * Notify about a staff transfer.
     */
    public function notifyStaffTransfer(StaffTransfer $transfer): void;
    
    /**
     * Notify about a staff resignation.
     */
    public function notifyResignation(Staff $staff): void;
    
    /**
     * Notify about organizational changes.
     */
    public function notifyOrganizationalChange(array $changes): void;
    
    /**
     * Notify about company hierarchy changes.
     */
    public function notifyCompanyHierarchyChange(Company $company, array $changes): void;
    
    /**
     * Notify about staff creation.
     */
    public function notifyStaffCreated(Staff $staff): void;
    
    /**
     * Notify about department changes.
     */
    public function notifyDepartmentChange(object $department, string $action): void;
    
    /**
     * Notify about office changes.
     */
    public function notifyOfficeChange(object $office, string $action): void;
    
    /**
     * Send a custom notification with data.
     */
    public function sendCustomNotification(string $type, array $data): void;
}