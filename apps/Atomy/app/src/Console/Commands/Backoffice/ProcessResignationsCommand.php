<?php

declare(strict_types=1);

namespace Nexus\Atomy\Console\Commands\Backoffice;

use Illuminate\Console\Command;
use Nexus\Atomy\Actions\Backoffice\ProcessResignationsAction;
use Nexus\Backoffice\Models\Company;
use Carbon\Carbon;

/**
 * Process Scheduled Resignations Command
 * 
 * Uses ProcessResignationsAction to handle business logic.
 */
class ProcessResignationsCommand extends Command
{
    protected $signature = 'nexus:backoffice:process-resignations
                           {--dry-run : Run without making changes}
                           {--date= : Process resignations for specific date (YYYY-MM-DD)}
                           {--company= : Process resignations for specific company ID only}
                           {--force : Process without confirmation}';

    protected $description = 'Process scheduled staff resignations that are due';

    public function handle(): int
    {
        $this->info('Processing scheduled resignations...');

        $dryRun = $this->option('dry-run');
        $date = $this->option('date');
        $companyId = $this->option('company');
        $force = $this->option('force');
        
        if ($date && !$this->isValidDate($date)) {
            $this->error('Invalid date format. Please use YYYY-MM-DD format.');
            return self::FAILURE;
        }
        
        try {
            $asOfDate = $date ? Carbon::parse($date) : now();
            $company = $companyId ? Company::find($companyId) : null;
            
            if ($companyId && !$company) {
                $this->error("Company with ID {$companyId} not found.");
                return self::FAILURE;
            }
            
            if ($dryRun) {
                $this->warn('DRY RUN MODE - No changes will be made');
            }
            
            $this->info("Processing resignations as of: {$asOfDate->toDateString()}");
            
            if ($company) {
                $this->info("Filtering by company: {$company->name}");
            }
            
            $action = new ProcessResignationsAction();
            $result = $action->execute($company, $asOfDate, $dryRun);
            
            if (empty($result['resignations']) && $result['processed'] === 0) {
                $this->info('No resignations to process.');
                return self::SUCCESS;
            }
            
            if (!$dryRun && !$force && !$this->confirmProcessing($result)) {
                $this->info('Processing cancelled by user.');
                return self::SUCCESS;
            }
            
            $this->displayResults($result);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to process resignations: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function confirmProcessing(array $result): bool
    {
        $this->info("Found {$result['processed']} resignation(s) to process:");
        
        foreach ($result['resignations'] as $resignation) {
            $this->line("- {$resignation['name']} (Resignation date: {$resignation['resignation_date']})");
        }
        
        return $this->confirm('Do you want to proceed with processing these resignations?');
    }

    protected function displayResults(array $result): void
    {
        if ($result['dry_run']) {
            $this->info("DRY RUN: Would process {$result['processed']} resignations");
        } else {
            $this->info("Successfully processed {$result['processed']} resignations");
        }
        
        if ($result['failed'] > 0) {
            $this->warn("Failed to process {$result['failed']} resignations");
            
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->error('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->error("- Staff ID {$error['staff_id']} ({$error['staff_name']}): {$error['error']}");
                }
            }
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
