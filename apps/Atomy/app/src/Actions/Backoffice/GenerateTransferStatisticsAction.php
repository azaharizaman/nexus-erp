<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Enums\StaffStatus;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Carbon\Carbon;

/**
 * Generate Transfer Statistics Action
 * 
 * Orchestrates generation of transfer statistics and reports.
 */
class GenerateTransferStatisticsAction extends Action
{
    /**
     * Generate transfer statistics.
     * 
     * @param array $options Statistics options (company, date range, grouping, etc.)
     * @return array Statistics results
     */
    public function handle(...$parameters): array
    {
        $options = $parameters[0] ?? [];
        
        // Set default options
        $options = array_merge([
            'company_id' => null,
            'start_date' => now()->startOfMonth(),
            'end_date' => now(),
            'group_by' => 'month', // day, week, month, quarter, year
            'include_pending' => false,
            'include_rejected' => false,
            'include_cancelled' => false,
        ], $options);
        
        // Convert dates to Carbon instances if needed
        if (!$options['start_date'] instanceof Carbon) {
            $options['start_date'] = Carbon::parse($options['start_date']);
        }
        if (!$options['end_date'] instanceof Carbon) {
            $options['end_date'] = Carbon::parse($options['end_date']);
        }
        
        return [
            'period' => [
                'start_date' => $options['start_date']->format('Y-m-d'),
                'end_date' => $options['end_date']->format('Y-m-d'),
                'group_by' => $options['group_by'],
            ],
            'options' => $options,
            'generated_at' => now()->toISOString(),
            'summary' => $this->generateSummaryStatistics($options),
            'timeline' => $this->generateTimelineStatistics($options),
            'departments' => $this->generateDepartmentStatistics($options),
            'companies' => $this->generateCompanyStatistics($options),
            'staff_statistics' => $this->generateStaffStatistics($options),
        ];
    }

    /**
     * This action doesn't modify data, so no transactions needed.
     */
    protected function useTransactions(): bool
    {
        return false;
    }

    /**
     * Generate summary statistics.
     * 
     * @param array $options
     * @return array
     */
    protected function generateSummaryStatistics(array $options): array
    {
        $query = $this->buildTransferQuery($options);
        
        $totalTransfers = $query->count();
        
        $statusBreakdown = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $averageProcessingTime = $query->whereIn('status', [
                StaffTransferStatus::COMPLETED,
                StaffTransferStatus::REJECTED
            ])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->value('avg_hours');
        
        return [
            'total_transfers' => $totalTransfers,
            'status_breakdown' => array_map(function ($status) use ($statusBreakdown) {
                return [
                    'status' => $status->value,
                    'count' => $statusBreakdown[$status->value] ?? 0,
                ];
            }, StaffTransferStatus::cases()),
            'average_processing_time_hours' => round((float)$averageProcessingTime, 2),
            'completion_rate' => $totalTransfers > 0 
                ? round((($statusBreakdown[StaffTransferStatus::COMPLETED->value] ?? 0) / $totalTransfers) * 100, 2)
                : 0,
        ];
    }

    /**
     * Generate timeline statistics.
     * 
     * @param array $options
     * @return array
     */
    protected function generateTimelineStatistics(array $options): array
    {
        $query = $this->buildTransferQuery($options);
        
        $dateFormat = match ($options['group_by']) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'quarter' => '%Y-Q%q',
            'year' => '%Y',
            default => '%Y-%m'
        };
        
        $timeline = $query->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period')
            ->map(function ($item) {
                return (int) $item->count;
            })
            ->toArray();
        
        // Fill in missing periods with zeros
        $filledTimeline = $this->fillMissingPeriods(
            $timeline, 
            $options['start_date'], 
            $options['end_date'], 
            $options['group_by']
        );
        
        return [
            'data' => $filledTimeline,
            'peak_period' => array_keys($timeline, max($timeline))[0] ?? null,
            'total_periods' => count($filledTimeline),
            'average_per_period' => count($filledTimeline) > 0 
                ? round(array_sum($filledTimeline) / count($filledTimeline), 2)
                : 0,
        ];
    }

    /**
     * Generate department statistics.
     * 
     * @param array $options
     * @return array
     */
    protected function generateDepartmentStatistics(array $options): array
    {
        $query = $this->buildTransferQuery($options);
        
        // Transfers from departments
        $fromDepartments = $query->join('staff', 'staff_transfers.staff_id', '=', 'staff.id')
            ->join('departments as from_dept', 'staff.department_id', '=', 'from_dept.id')
            ->selectRaw('from_dept.name as department_name, COUNT(*) as outgoing_count')
            ->groupBy('from_dept.id', 'from_dept.name')
            ->get()
            ->keyBy('department_name')
            ->map(function ($item) {
                return (int) $item->outgoing_count;
            })
            ->toArray();
        
        // Transfers to departments
        $toDepartments = $query->join('departments as to_dept', 'staff_transfers.to_department_id', '=', 'to_dept.id')
            ->selectRaw('to_dept.name as department_name, COUNT(*) as incoming_count')
            ->groupBy('to_dept.id', 'to_dept.name')
            ->get()
            ->keyBy('department_name')
            ->map(function ($item) {
                return (int) $item->incoming_count;
            })
            ->toArray();
        
        // Combine data
        $allDepartments = array_unique(array_merge(
            array_keys($fromDepartments),
            array_keys($toDepartments)
        ));
        
        $departmentStats = [];
        foreach ($allDepartments as $department) {
            $outgoing = $fromDepartments[$department] ?? 0;
            $incoming = $toDepartments[$department] ?? 0;
            $departmentStats[$department] = [
                'outgoing_transfers' => $outgoing,
                'incoming_transfers' => $incoming,
                'net_transfers' => $incoming - $outgoing,
            ];
        }
        
        return $departmentStats;
    }

    /**
     * Generate company statistics.
     * 
     * @param array $options
     * @return array
     */
    protected function generateCompanyStatistics(array $options): array
    {
        if ($options['company_id']) {
            // Single company view - show office/department breakdown
            return $this->generateSingleCompanyStatistics($options);
        }
        
        $query = $this->buildTransferQuery($options);
        
        $companyStats = $query->join('staff', 'staff_transfers.staff_id', '=', 'staff.id')
            ->join('companies', 'staff.company_id', '=', 'companies.id')
            ->selectRaw('companies.name as company_name, COUNT(*) as transfer_count')
            ->groupBy('companies.id', 'companies.name')
            ->get()
            ->keyBy('company_name')
            ->map(function ($item) {
                return (int) $item->transfer_count;
            })
            ->toArray();
        
        return $companyStats;
    }

    /**
     * Generate single company statistics.
     * 
     * @param array $options
     * @return array
     */
    protected function generateSingleCompanyStatistics(array $options): array
    {
        $query = $this->buildTransferQuery($options)
            ->join('staff', 'staff_transfers.staff_id', '=', 'staff.id')
            ->where('staff.company_id', $options['company_id']);
        
        // Office breakdown
        $officeStats = $query->join('offices', 'staff.office_id', '=', 'offices.id')
            ->selectRaw('offices.name as office_name, COUNT(*) as transfer_count')
            ->groupBy('offices.id', 'offices.name')
            ->get()
            ->keyBy('office_name')
            ->map(function ($item) {
                return (int) $item->transfer_count;
            })
            ->toArray();
        
        return [
            'offices' => $officeStats,
            'total_transfers' => array_sum($officeStats),
        ];
    }

    /**
     * Generate staff statistics.
     * 
     * @param array $options
     * @return array
     */
    protected function generateStaffStatistics(array $options): array
    {
        $staffQuery = Staff::query();
        
        if ($options['company_id']) {
            $staffQuery->where('company_id', $options['company_id']);
        }
        
        $totalStaff = $staffQuery->count();
        $activeStaff = $staffQuery->where('status', StaffStatus::ACTIVE)->count();
        
        $transferQuery = $this->buildTransferQuery($options);
        $uniqueStaffWithTransfers = $transferQuery->distinct('staff_id')->count('staff_id');
        
        return [
            'total_staff' => $totalStaff,
            'active_staff' => $activeStaff,
            'staff_with_transfers' => $uniqueStaffWithTransfers,
            'transfer_rate' => $activeStaff > 0 
                ? round(($uniqueStaffWithTransfers / $activeStaff) * 100, 2)
                : 0,
        ];
    }

    /**
     * Build the base transfer query with filters.
     * 
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildTransferQuery(array $options)
    {
        $query = StaffTransfer::whereBetween('created_at', [
            $options['start_date'],
            $options['end_date']
        ]);
        
        // Filter by company if specified
        if ($options['company_id']) {
            $query->whereHas('staff', function ($q) use ($options) {
                $q->where('company_id', $options['company_id']);
            });
        }
        
        // Filter by status
        $allowedStatuses = [StaffTransferStatus::APPROVED, StaffTransferStatus::COMPLETED];
        
        if ($options['include_pending']) {
            $allowedStatuses[] = StaffTransferStatus::PENDING;
        }
        
        if ($options['include_rejected']) {
            $allowedStatuses[] = StaffTransferStatus::REJECTED;
        }
        
        if ($options['include_cancelled']) {
            $allowedStatuses[] = StaffTransferStatus::CANCELLED;
        }
        
        $query->whereIn('status', $allowedStatuses);
        
        return $query;
    }

    /**
     * Fill in missing periods with zeros.
     * 
     * @param array $data
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy
     * @return array
     */
    protected function fillMissingPeriods(array $data, Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $filled = [];
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            $period = match ($groupBy) {
                'day' => $current->format('Y-m-d'),
                'week' => $current->format('Y-W'),
                'month' => $current->format('Y-m'),
                'quarter' => $current->format('Y') . '-Q' . $current->quarter,
                'year' => $current->format('Y'),
                default => $current->format('Y-m')
            };
            
            $filled[$period] = $data[$period] ?? 0;
            
            match ($groupBy) {
                'day' => $current->addDay(),
                'week' => $current->addWeek(),
                'month' => $current->addMonth(),
                'quarter' => $current->addMonths(3),
                'year' => $current->addYear(),
                default => $current->addMonth()
            };
        }
        
        return $filled;
    }
}