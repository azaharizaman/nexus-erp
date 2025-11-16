<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts;

use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Models\Company;

/**
 * Audit Contract
 * 
 * Abstracts audit logging functionality to remove direct dependencies
 * on specific audit logging systems.
 */
interface AuditContract
{
    /**
     * Log changes to a staff member.
     */
    public function logStaffChange(Staff $staff, array $changes): void;
    
    /**
     * Log a staff transfer.
     */
    public function logTransfer(StaffTransfer $transfer): void;
    
    /**
     * Log changes to a company.
     */
    public function logCompanyChange(Company $company, array $changes): void;
    
    /**
     * Log department changes.
     */
    public function logDepartmentChange(object $department, array $changes): void;
    
    /**
     * Log office changes.
     */
    public function logOfficeChange(object $office, array $changes): void;
    
    /**
     * Log organizational hierarchy changes.
     */
    public function logHierarchyChange(object $entity, string $type, array $changes): void;
    
    /**
     * Log custom events with context.
     */
    public function logCustomEvent(string $event, array $context): void;
    
    /**
     * Log mass operations.
     */
    public function logMassOperation(string $operation, int $affectedCount, array $context = []): void;
}