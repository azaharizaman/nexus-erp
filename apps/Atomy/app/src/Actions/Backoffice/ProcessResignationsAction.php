<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Enums\StaffStatus;
use Carbon\Carbon;

/**
 * Process Resignations Action
 * 
 * Orchestrates processing of staff resignations that are due.
 */
class ProcessResignationsAction extends Action
{
    /**
     * Process staff resignations that are due.
     * 
     * @param Company|null $company If null, processes resignations for all companies
     * @param Carbon|null $asOfDate Date to process resignations for (defaults to today)
     * @param bool $dryRun If true, only returns what would be processed without making changes
     * @return array Processing results
     */
    public function handle(...$parameters): array
    {
        $company = $parameters[0] ?? null;
        $asOfDate = $parameters[1] ?? now();
        $dryRun = $parameters[2] ?? false;
        
        if ($company !== null && !$company instanceof Company) {
            throw new \InvalidArgumentException('First parameter must be a Company instance or null');
        }
        
        if (!$asOfDate instanceof Carbon) {
            $asOfDate = now();
        }
        
        // Get staff with resignations due for processing
        $dueResignations = $this->getDueResignations($company, $asOfDate);
        
        $results = [
            'processed' => 0,
            'failed' => 0,
            'dry_run' => $dryRun,
            'resignations' => [],
            'errors' => [],
        ];
        
        if ($dryRun) {
            foreach ($dueResignations as $staff) {
                $results['resignations'][] = [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'resignation_date' => $staff->resignation_date?->format('Y-m-d'),
                    'status' => 'would_be_processed',
                ];
            }
            $results['processed'] = $dueResignations->count();
            return $results;
        }
        
        foreach ($dueResignations as $staff) {
            try {
                $this->processResignation($staff);
                $results['processed']++;
                $results['resignations'][] = [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'resignation_date' => $staff->resignation_date?->format('Y-m-d'),
                    'status' => 'processed',
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->name,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * This action handles its own transaction management per resignation.
     */
    protected function useTransactions(): bool
    {
        return false;
    }

    /**
     * Get staff with resignations due for processing.
     * 
     * @param Company|null $company
     * @param Carbon $asOfDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getDueResignations(?Company $company, Carbon $asOfDate)
    {
        $query = Staff::where('status', StaffStatus::RESIGNED)
            ->whereNotNull('resignation_date')
            ->where('resignation_date', '<=', $asOfDate)
            ->whereNull('termination_date'); // Not already processed
        
        if ($company) {
            $query->where('company_id', $company->id);
        }
        
        return $query->get();
    }

    /**
     * Process a single resignation.
     * 
     * @param Staff $staff
     * @throws \Exception
     */
    protected function processResignation(Staff $staff): void
    {
        \DB::transaction(function () use ($staff) {
            // Update staff status and set termination date
            $staff->update([
                'status' => StaffStatus::TERMINATED,
                'termination_date' => $staff->resignation_date,
                'is_active' => false,
            ]);
            
            // Clear supervisor relationship to prevent orphaned references
            Staff::where('supervisor_id', $staff->id)->update([
                'supervisor_id' => null,
            ]);
        });
    }
}