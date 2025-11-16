<?php

declare(strict_types=1);

namespace Nexus\Atomy\Console\Commands\Backoffice;

use Illuminate\Console\Command;
use Nexus\Atomy\Actions\Backoffice\ProcessStaffTransfersAction;
use Nexus\Backoffice\Models\Company;
use Carbon\Carbon;

/**
 * Process Staff Transfers Command
 * 
 * Uses ProcessStaffTransfersAction to handle business logic.
 */
class ProcessStaffTransfersCommand extends Command
{
    protected $signature = 'nexus:backoffice:process-transfers
                           {--dry-run : Show what would be processed without making changes}
                           {--date= : Process transfers for specific date (YYYY-MM-DD)}
                           {--company= : Process transfers for specific company ID only}';

    protected $description = 'Process approved staff transfers that have reached their effective date';

    public function handle(): int
    {
        $this->info('Starting Nexus Backoffice staff transfer processing...');
        
        $dryRun = $this->option('dry-run');
        $date = $this->option('date');
        $companyId = $this->option('company');
        
        if ($date && !$this->isValidDate($date)) {
            $this->error('Invalid date format. Please use YYYY-MM-DD format.');
            return self::FAILURE;
        }
        
        try {
            $effectiveDate = $date ? Carbon::parse($date) : now();
            $company = $companyId ? Company::find($companyId) : null;
            
            if ($companyId && !$company) {
                $this->error("Company with ID {$companyId} not found.");
                return self::FAILURE;
            }
            
            if ($dryRun) {
                $this->warn('DRY RUN MODE - No changes will be made');
            }
            
            $this->info("Processing transfers effective on or before: {$effectiveDate->toDateString()}");
            
            if ($company) {
                $this->info("Filtering by company: {$company->name}");
            }
            
            $action = new ProcessStaffTransfersAction();
            $result = $action->execute($company, $effectiveDate, $dryRun);
            
            $this->displayResults($result);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to process transfers: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function displayResults(array $result): void
    {
        if ($result['dry_run']) {
            $this->info("DRY RUN: Would process {$result['processed']} transfers");
        } else {
            $this->info("Successfully processed {$result['processed']} transfers");
        }
        
        if ($result['failed'] > 0) {
            $this->warn("Failed to process {$result['failed']} transfers");
            
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->error('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->error("- Staff ID {$error['staff_id']} ({$error['staff_name']}): {$error['error']}");
                }
            }
        }
        
        if (empty($result['transfers']) && $result['processed'] === 0) {
            $this->info('No transfers found to process.');
        }
    }

    protected function isValidDate(string $date): bool
    {
        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
