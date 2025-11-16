<?php

declare(strict_types=1);

namespace Nexus\Atomy\Console\Commands\Backoffice;

use Illuminate\Console\Command;
use Nexus\Backoffice\Models\OfficeType;

/**
 * Create Office Types Command
 * 
 * Creates default office types for organizational management.
 * 
 * This orchestration command manages the creation of default office types
 * based on configuration settings, providing a foundation for office categorization.
 */
class CreateOfficeTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nexus:backoffice:create-office-types {--force : Force creation even if office types already exist}';

    /**
     * The console command description.
     */
    protected $description = 'Create default office types for organizational structure';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating default office types...');

        $defaultOfficeTypes = config('backoffice.default_office_types', []);

        if (empty($defaultOfficeTypes)) {
            $this->warn('No default office types configured.');
            $this->comment('Add office types to config/backoffice.php under "default_office_types"');
            return self::FAILURE;
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
                $this->createOfficeType($officeTypeData);
                $this->line("Replaced existing '{$officeTypeData['name']}'");
            } else {
                $this->createOfficeType($officeTypeData);
                $this->line("Created '{$officeTypeData['name']}'");
            }

            $created++;
        }

        $this->info("Office types creation completed!");
        $this->line("Created: {$created}");
        $this->line("Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Create an office type with the given data.
     *
     * @param array $officeTypeData
     * @return OfficeType
     */
    private function createOfficeType(array $officeTypeData): OfficeType
    {
        return OfficeType::create([
            'name' => $officeTypeData['name'],
            'code' => $officeTypeData['code'],
            'description' => $officeTypeData['description'] ?? null,
            'is_active' => true,
        ]);
    }
}