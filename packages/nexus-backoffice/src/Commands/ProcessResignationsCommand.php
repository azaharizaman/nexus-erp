<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Commands;

use Nexus\Backoffice\Models\Staff;
use Illuminate\Console\Command;

/**
 * Process Scheduled Resignations Command
 * 
 * This command processes staff resignations that are scheduled for today or earlier.
 * It should be run daily via cron to automatically update staff status.
 */
class ProcessResignationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backoffice:process-resignations
                           {--dry-run : Run without making changes}
                           {--force : Process without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled staff resignations that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing scheduled resignations...');

        // Get staff with pending resignations that are due
        $dueResignations = Staff::pendingResignation()
            ->whereDate('resignation_date', '<=', now()->toDateString())
            ->get();

        if ($dueResignations->isEmpty()) {
            $this->info('No resignations to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$dueResignations->count()} resignation(s) to process:");

        // Display resignations to be processed
        $headers = ['Employee ID', 'Name', 'Department', 'Resignation Date', 'Reason'];
        $rows = $dueResignations->map(function (Staff $staff) {
            return [
                $staff->employee_id,
                $staff->full_name,
                $staff->department?->name ?? 'N/A',
                $staff->resignation_date?->format('Y-m-d') ?? 'N/A',
                $staff->resignation_reason ? substr($staff->resignation_reason, 0, 50) . '...' : 'N/A'
            ];
        });

        $this->table($headers, $rows);

        // Check for dry run
        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - no changes will be made.');
            return Command::SUCCESS;
        }

        // Confirm processing unless forced
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to process these resignations?')) {
                $this->info('Processing cancelled.');
                return Command::SUCCESS;
            }
        }

        $processed = 0;
        $errors = 0;

        // Process each resignation
        foreach ($dueResignations as $staff) {
            try {
                $this->processStaffResignation($staff);
                $processed++;
                $this->info("✓ Processed resignation for {$staff->full_name} ({$staff->employee_id})");
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to process {$staff->full_name} ({$staff->employee_id}): {$e->getMessage()}");
            }
        }

        // Summary
        $this->info("\nProcessing complete:");
        $this->info("- Processed: {$processed}");
        if ($errors > 0) {
            $this->warn("- Errors: {$errors}");
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process individual staff resignation.
     */
    private function processStaffResignation(Staff $staff): void
    {
        $staff->processResignation();

        // Log the resignation processing
        $this->info("Resignation processed for {$staff->full_name} on {$staff->resignation_date->format('Y-m-d')}");
    }
}