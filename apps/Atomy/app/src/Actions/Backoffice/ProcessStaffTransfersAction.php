<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\Helpers\StaffTransferHelper;
use Nexus\Backoffice\Enums\StaffTransferStatus;
use Carbon\Carbon;

/**
 * Process Staff Transfers Action
 * 
 * Orchestrates processing of approved staff transfers that are due.
 */
class ProcessStaffTransfersAction extends Action
{
    /**
     * Process staff transfers that are due for the given company.
     * 
     * @param Company|null $company If null, processes transfers for all companies
     * @param Carbon|null $asOfDate Date to process transfers for (defaults to today)
     * @return array Processing results
     */
    public function handle(...$parameters): array
    {
        $company = $parameters[0] ?? null;
        $asOfDate = $parameters[1] ?? now();
        
        if ($company !== null && !$company instanceof Company) {
            throw new \InvalidArgumentException('First parameter must be a Company instance or null');
        }
        
        if (!$asOfDate instanceof Carbon) {
            $asOfDate = now();
        }
        
        // Get transfers that are due for processing
        $dueTransfers = $this->getDueTransfers($company, $asOfDate);
        
        $results = [
            'processed' => 0,
            'failed' => 0,
            'transfers' => [],
            'errors' => [],
        ];
        
        foreach ($dueTransfers as $transfer) {
            try {
                $this->processTransfer($transfer);
                $results['processed']++;
                $results['transfers'][] = [
                    'id' => $transfer->id,
                    'staff_name' => $transfer->staff->name,
                    'status' => 'processed',
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'transfer_id' => $transfer->id,
                    'staff_name' => $transfer->staff->name,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * This action handles its own transaction management per transfer.
     */
    protected function useTransactions(): bool
    {
        return false;
    }

    /**
     * Get transfers that are due for processing.
     * 
     * @param Company|null $company
     * @param Carbon $asOfDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getDueTransfers(?Company $company, Carbon $asOfDate)
    {
        $query = StaffTransfer::approved()
            ->where('effective_date', '<=', $asOfDate)
            ->with(['staff', 'toOffice', 'toDepartment', 'toPosition', 'toSupervisor']);
        
        if ($company) {
            // Filter by company through staff relationship
            $query->whereHas('staff', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });
        }
        
        return $query->get();
    }

    /**
     * Process a single transfer.
     * 
     * @param StaffTransfer $transfer
     * @throws \Exception
     */
    protected function processTransfer(StaffTransfer $transfer): void
    {
        \DB::transaction(function () use ($transfer) {
            // Update staff record with new assignments
            $transfer->staff->update([
                'office_id' => $transfer->to_office_id,
                'department_id' => $transfer->to_department_id,
                'position_id' => $transfer->to_position_id ?? $transfer->staff->position_id,
                'supervisor_id' => $transfer->to_supervisor_id,
            ]);
            
            // Mark transfer as completed
            $transfer->update([
                'status' => StaffTransferStatus::COMPLETED,
                'processed_at' => now(),
                'processed_by' => 'system', // Could be parameterized
            ]);
        });
    }
}