<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Commands;

use Illuminate\Console\Command;
use Nexus\Backoffice\Models\OfficeType;

/**
 * Create Office Types Command
 * 
 * Creates default office types for the system.
 */
class CreateOfficeTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backoffice:create-office-types {--force : Force creation even if office types already exist}';

    /**
     * The console command description.
     */
    protected $description = 'Create default office types';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating default office types...');

        $defaultOfficeTypes = config('backoffice.default_office_types', []);

        if (empty($defaultOfficeTypes)) {
            $this->warn('No default office types configured.');
            return 0;
        }

        $created = 0;
        $skipped = 0;

        foreach ($defaultOfficeTypes as $officeTypeData) {
            $exists = OfficeType::where('code', $officeTypeData['code'])->exists();

            if ($exists && !$this->option('force')) {
                $this->line("Skipping '{$officeTypeData['name']}' - already exists");
                $skipped++;
                continue;
            }

            if ($exists && $this->option('force')) {
                OfficeType::where('code', $officeTypeData['code'])->delete();
                $this->line("Replaced existing '{$officeTypeData['name']}'");
            } else {
                $this->line("Created '{$officeTypeData['name']}'");
            }

            OfficeType::create([
                'name' => $officeTypeData['name'],
                'code' => $officeTypeData['code'],
                'description' => $officeTypeData['description'] ?? null,
                'is_active' => true,
            ]);

            $created++;
        }

        $this->info("Office types creation completed!");
        $this->line("Created: {$created}");
        $this->line("Skipped: {$skipped}");

        return 0;
    }
}