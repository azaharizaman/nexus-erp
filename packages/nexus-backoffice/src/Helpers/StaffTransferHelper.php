<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Nexus\Backoffice\Models\{Staff, Office, Department, StaffTransfer, Company};

/**
 * Staff Transfer Helper
 * 
 * Utility class for managing staff transfers, validation, statistics,
 * and reporting line management across the organization.
 * 
 * @package Nexus\BackofficeManagement\Helpers
 */
class StaffTransferHelper
{
    /**
     * Get transfer statistics for a company.
     */
    public static function getTransferStatistics(Company $company): array
    {
        $staffIds = $company->getAllStaff()->pluck('id');
        
        $baseQuery = StaffTransfer::whereIn('staff_id', $staffIds);
        
        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'totals' => [
                'total_transfers' => $baseQuery->count(),
                'pending_transfers' => $baseQuery->pending()->count(),
                'approved_transfers' => $baseQuery->approved()->count(),
                'completed_transfers' => $baseQuery->completed()->count(),
                'rejected_transfers' => $baseQuery->rejected()->count(),
                'cancelled_transfers' => $baseQuery->cancelled()->count(),
            ],
            'recent_activity' => [
                'last_30_days' => $baseQuery->where('created_at', '>=', now()->subDays(30))->count(),
                'last_7_days' => $baseQuery->where('created_at', '>=', now()->subDays(7))->count(),
                'today' => $baseQuery->whereDate('created_at', today())->count(),
            ],
            'processing_queue' => [
                'due_today' => $baseQuery->approved()->where('effective_date', '<=', today())->count(),
                'due_this_week' => $baseQuery->approved()->whereBetween('effective_date', [today(), today()->addDays(7)])->count(),
                'due_this_month' => $baseQuery->approved()->whereBetween('effective_date', [today(), today()->addMonth()])->count(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Get transfer statistics for an office.
     */
    public static function getOfficeTransferStatistics(Office $office): array
    {
        $incomingTransfers = StaffTransfer::where('to_office_id', $office->id);
        $outgoingTransfers = StaffTransfer::where('from_office_id', $office->id);
        
        return [
            'office' => [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
            ],
            'incoming' => [
                'total' => $incomingTransfers->count(),
                'pending' => $incomingTransfers->pending()->count(),
                'approved' => $incomingTransfers->approved()->count(),
                'completed' => $incomingTransfers->completed()->count(),
            ],
            'outgoing' => [
                'total' => $outgoingTransfers->count(),
                'pending' => $outgoingTransfers->pending()->count(),
                'approved' => $outgoingTransfers->approved()->count(),
                'completed' => $outgoingTransfers->completed()->count(),
            ],
            'net_flow' => [
                'total' => $incomingTransfers->completed()->count() - $outgoingTransfers->completed()->count(),
                'last_30_days' => $incomingTransfers->completed()->where('completed_at', '>=', now()->subDays(30))->count() - 
                                 $outgoingTransfers->completed()->where('completed_at', '>=', now()->subDays(30))->count(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Get transfer statistics for a staff member.
     */
    public static function getStaffTransferStatistics(Staff $staff): array
    {
        $transfers = $staff->transfers();
        
        return [
            'staff' => [
                'id' => $staff->id,
                'employee_id' => $staff->employee_id,
                'name' => $staff->full_name,
                'current_office' => $staff->office?->name,
                'current_department' => $staff->department?->name,
            ],
            'transfer_history' => [
                'total_transfers' => $transfers->count(),
                'completed_transfers' => $transfers->completed()->count(),
                'rejected_transfers' => $transfers->rejected()->count(),
                'cancelled_transfers' => $transfers->cancelled()->count(),
            ],
            'current_status' => [
                'has_active_transfer' => $staff->hasActiveTransfer(),
                'active_transfer_id' => $staff->activeTransfer()->first()?->id,
                'can_be_transferred' => $staff->canBeTransferred(),
            ],
            'mobility_metrics' => [
                'average_tenure_per_office' => static::calculateAverageTenure($staff),
                'last_transfer_date' => $staff->lastCompletedTransfer()?->completed_at?->toDateString(),
                'days_since_last_transfer' => $staff->lastCompletedTransfer() ? 
                    now()->diffInDays($staff->lastCompletedTransfer()->completed_at) : null,
            ],
            'generated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Get pending transfers that need approval.
     */
    public static function getPendingTransfersForApproval(Staff $approver): Collection
    {
        return StaffTransfer::query()
            ->with(['staff', 'fromOffice', 'toOffice', 'requestedBy'])
            ->pending()
            ->where(function ($query) use ($approver) {
                // Transfers where approver is supervisor of staff being transferred
                $query->whereHas('staff', function ($q) use ($approver) {
                    $q->where('supervisor_id', $approver->id);
                })
                // Or transfers involving offices under approver's management
                ->orWhereHas('fromOffice', function ($q) use ($approver) {
                    $q->where('id', $approver->office_id);
                })
                ->orWhereHas('toOffice', function ($q) use ($approver) {
                    $q->where('id', $approver->office_id);
                });
            })
            ->orderBy('requested_at', 'asc')
            ->get();
    }
    
    /**
     * Get transfers due for processing.
     */
    public static function getTransfersDueForProcessing(?Carbon $asOfDate = null): Collection
    {
        $date = $asOfDate ?? now();
        
        return StaffTransfer::query()
            ->with(['staff', 'fromOffice', 'toOffice', 'toDepartment', 'toSupervisor'])
            ->dueForProcessing()
            ->where('effective_date', '<=', $date->toDateString())
            ->orderBy('effective_date', 'asc')
            ->orderBy('requested_at', 'asc')
            ->get();
    }
    
    /**
     * Validate a transfer request.
     */
    public static function validateTransferRequest(
        Staff $staff,
        Office $toOffice,
        ?Department $toDepartment = null,
        ?Staff $toSupervisor = null,
        ?Carbon $effectiveDate = null
    ): array {
        $errors = [];
        
        // Check if staff can be transferred
        if (!$staff->canBeTransferred()) {
            $errors[] = 'Staff member cannot be transferred at this time';
        }
        
        // Check if transferring to same office
        if ($staff->office_id === $toOffice->id) {
            $errors[] = 'Cannot transfer staff to the same office';
        }
        
        // Validate department belongs to target office
        if ($toDepartment && $toDepartment->company_id !== $toOffice->company_id) {
            $errors[] = 'Department does not belong to the target office company';
        }
        
        // Validate supervisor
        if ($toSupervisor) {
            $supervisorErrors = static::validateSupervisorAssignment($staff, $toSupervisor, $toOffice);
            $errors = array_merge($errors, $supervisorErrors);
        }
        
        // Validate effective date
        if ($effectiveDate && $effectiveDate->isPast()) {
            $errors[] = 'Effective date cannot be in the past';
        }
        
        // Check for conflicts
        $conflictErrors = static::checkTransferConflicts($staff, $toOffice, $effectiveDate);
        $errors = array_merge($errors, $conflictErrors);
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => static::getTransferWarnings($staff, $toOffice, $toDepartment, $toSupervisor),
        ];
    }
    
    /**
     * Validate supervisor assignment for transfer.
     */
    public static function validateSupervisorAssignment(Staff $staff, Staff $toSupervisor, Office $toOffice): array
    {
        $errors = [];
        
        // Cannot supervise themselves
        if ($staff->id === $toSupervisor->id) {
            $errors[] = 'Staff cannot be their own supervisor';
        }
        
        // Check for circular reference
        if ($toSupervisor->reportsTo($staff)) {
            $errors[] = 'Cannot assign supervisor who reports to this staff member';
        }
        
        // Supervisor should be in the target office or a parent office
        if ($toSupervisor->office_id !== $toOffice->id) {
            $supervisorOffice = $toSupervisor->office;
            $targetOfficeAncestors = $toOffice->getAncestors();
            
            if (!$targetOfficeAncestors->contains('id', $toSupervisor->office_id)) {
                $errors[] = 'Supervisor must be in the target office or a parent office';
            }
        }
        
        // Check supervisor's span of control
        $currentSpan = $toSupervisor->getSpanOfControl();
        if ($currentSpan >= 15) { // Configurable limit
            $errors[] = "Supervisor already has {$currentSpan} direct reports, which may be too many";
        }
        
        return $errors;
    }
    
    /**
     * Check for transfer conflicts.
     */
    protected static function checkTransferConflicts(Staff $staff, Office $toOffice, ?Carbon $effectiveDate): array
    {
        $errors = [];
        
        // Check if there are other pending transfers for the same staff
        $existingTransfer = StaffTransfer::forStaff($staff)
            ->whereIn('status', [StaffTransferStatus::PENDING, StaffTransferStatus::APPROVED])
            ->first();
            
        if ($existingTransfer) {
            $errors[] = 'Staff already has a pending or approved transfer';
        }
        
        // Check office capacity (if configured)
        if ($effectiveDate && static::hasOfficeCapacityLimits($toOffice)) {
            $projected = static::getProjectedOfficeStaffCount($toOffice, $effectiveDate);
            $capacity = static::getOfficeCapacity($toOffice);
            
            if ($projected >= $capacity) {
                $errors[] = "Target office will be at capacity ({$projected}/{$capacity}) on effective date";
            }
        }
        
        return $errors;
    }
    
    /**
     * Get transfer warnings.
     */
    protected static function getTransferWarnings(Staff $staff, Office $toOffice, ?Department $toDepartment, ?Staff $toSupervisor): array
    {
        $warnings = [];
        
        // Warn if staff has recent transfer
        if ($staff->hasRecentTransfer(90)) {
            $warnings[] = 'Staff has been transferred within the last 90 days';
        }
        
        // Warn if changing reporting line significantly
        if ($toSupervisor && $staff->supervisor_id !== $toSupervisor->id) {
            $currentLevel = $staff->getReportingLevel();
            $newLevel = $toSupervisor->getReportingLevel() + 1;
            
            if (abs($currentLevel - $newLevel) > 2) {
                $warnings[] = 'Transfer will significantly change reporting level in hierarchy';
            }
        }
        
        // Warn if moving between different company cultures/locations
        if ($staff->office && $staff->office->company_id !== $toOffice->company_id) {
            $warnings[] = 'Transfer involves different companies - consider cultural and policy differences';
        }
        
        return $warnings;
    }
    
    /**
     * Calculate average tenure per office for a staff member.
     */
    protected static function calculateAverageTenure(Staff $staff): ?float
    {
        $completedTransfers = $staff->transfers()->completed()->orderBy('completed_at')->get();
        
        if ($completedTransfers->isEmpty()) {
            // Calculate current tenure
            return $staff->hire_date ? now()->diffInDays($staff->hire_date) : null;
        }
        
        $tenures = [];
        $startDate = $staff->hire_date ?? $completedTransfers->first()->completed_at;
        
        foreach ($completedTransfers as $transfer) {
            $tenures[] = $startDate->diffInDays($transfer->completed_at);
            $startDate = $transfer->completed_at;
        }
        
        // Add current tenure
        $tenures[] = $startDate->diffInDays(now());
        
        return array_sum($tenures) / count($tenures);
    }
    
    /**
     * Check if office has capacity limits.
     */
    protected static function hasOfficeCapacityLimits(Office $office): bool
    {
        // This would typically be configurable or stored in office metadata
        return false; // Implement based on your requirements
    }
    
    /**
     * Get projected staff count for office on a given date.
     */
    protected static function getProjectedOfficeStaffCount(Office $office, Carbon $date): int
    {
        // Current staff in office
        $currentCount = Staff::active()->where('office_id', $office->id)->count();
        
        // Add incoming transfers
        $incomingCount = StaffTransfer::approved()
            ->where('to_office_id', $office->id)
            ->where('effective_date', '<=', $date->toDateString())
            ->count();
            
        // Subtract outgoing transfers
        $outgoingCount = StaffTransfer::approved()
            ->where('from_office_id', $office->id)
            ->where('effective_date', '<=', $date->toDateString())
            ->count();
            
        return $currentCount + $incomingCount - $outgoingCount;
    }
    
    /**
     * Get office capacity limit.
     */
    protected static function getOfficeCapacity(Office $office): int
    {
        // This would typically be stored in office metadata or configuration
        return 1000; // Default high limit
    }
    
    /**
     * Generate transfer impact report.
     */
    public static function generateTransferImpactReport(StaffTransfer $transfer): array
    {
        $staff = $transfer->staff;
        
        return [
            'transfer' => [
                'id' => $transfer->id,
                'status' => $transfer->status->value,
                'effective_date' => $transfer->effective_date->toDateString(),
            ],
            'organizational_impact' => [
                'reporting_line_changes' => static::getReportingLineChanges($transfer),
                'team_impact' => static::getTeamImpact($transfer),
                'office_impact' => static::getOfficeImpact($transfer),
            ],
            'recommendations' => static::getTransferRecommendations($transfer),
            'generated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Get reporting line changes from transfer.
     */
    protected static function getReportingLineChanges(StaffTransfer $transfer): array
    {
        $changes = [];
        
        if ($transfer->from_supervisor_id !== $transfer->to_supervisor_id) {
            $changes['supervisor_change'] = [
                'from' => $transfer->fromSupervisor?->full_name,
                'to' => $transfer->toSupervisor?->full_name,
            ];
        }
        
        // Check if any subordinates will be affected
        $subordinates = $transfer->staff->subordinates;
        if ($subordinates->isNotEmpty()) {
            $changes['subordinates_affected'] = $subordinates->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'name' => $sub->full_name,
                    'position' => $sub->position,
                ];
            })->toArray();
        }
        
        return $changes;
    }
    
    /**
     * Get team impact from transfer.
     */
    protected static function getTeamImpact(StaffTransfer $transfer): array
    {
        $staff = $transfer->staff;
        
        return [
            'current_team_size' => $staff->office ? Staff::where('office_id', $staff->office_id)->count() : 0,
            'target_team_size' => Staff::where('office_id', $transfer->to_office_id)->count(),
            'specialized_role' => str_contains(strtolower($staff->position ?? ''), 'specialist') ||
                                str_contains(strtolower($staff->position ?? ''), 'expert'),
            'management_role' => $staff->subordinates()->exists(),
        ];
    }
    
    /**
     * Get office impact from transfer.
     */
    protected static function getOfficeImpact(StaffTransfer $transfer): array
    {
        return [
            'source_office' => [
                'current_staff_count' => Staff::where('office_id', $transfer->from_office_id)->count(),
                'will_lose_manager' => $transfer->staff->subordinates()->exists(),
            ],
            'target_office' => [
                'current_staff_count' => Staff::where('office_id', $transfer->to_office_id)->count(),
                'will_gain_manager' => $transfer->staff->subordinates()->exists(),
            ],
        ];
    }
    
    /**
     * Get transfer recommendations.
     */
    protected static function getTransferRecommendations(StaffTransfer $transfer): array
    {
        $recommendations = [];
        
        // If staff has subordinates, recommend reassignment
        if ($transfer->staff->subordinates()->exists()) {
            $recommendations[] = [
                'type' => 'subordinate_reassignment',
                'message' => 'Consider reassigning subordinates before transfer completion',
                'priority' => 'high',
            ];
        }
        
        // If specialized role, recommend knowledge transfer
        if (str_contains(strtolower($transfer->staff->position ?? ''), 'specialist')) {
            $recommendations[] = [
                'type' => 'knowledge_transfer',
                'message' => 'Arrange knowledge transfer session due to specialized role',
                'priority' => 'medium',
            ];
        }
        
        // If cross-department transfer, recommend orientation
        if ($transfer->from_department_id !== $transfer->to_department_id) {
            $recommendations[] = [
                'type' => 'orientation',
                'message' => 'Schedule department orientation for smooth transition',
                'priority' => 'medium',
            ];
        }
        
        return $recommendations;
    }
}