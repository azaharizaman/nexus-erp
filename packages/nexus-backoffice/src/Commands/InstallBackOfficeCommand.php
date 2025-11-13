<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Install BackOffice Command
 * 
 * Handles the initial installation of the BackOffice package.
 */
class InstallBackOfficeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backoffice:install {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     */
    protected $description = 'Install the BackOffice package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing BackOffice package...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--provider' => 'AzahariZaman\\BackOffice\\BackOfficeServiceProvider',
            '--tag' => 'backoffice-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--provider' => 'AzahariZaman\\BackOffice\\BackOfficeServiceProvider',
            '--tag' => 'backoffice-migrations',
            '--force' => $this->option('force'),
        ]);

        // Ask if user wants to run migrations
        if ($this->confirm('Do you want to run the migrations now?', true)) {
            $this->call('migrate');
        }

        // Ask if user wants to create default office types
        if ($this->confirm('Do you want to create default office types?', true)) {
            $this->call('backoffice:create-office-types');
        }

        $this->info('BackOffice package installed successfully!');
        
        $this->comment('Next steps:');
        $this->line('1. Review the configuration file at config/backoffice.php');
        $this->line('2. Create your first company using the Company model');
        $this->line('3. Set up offices, departments, and staff as needed');

        return self::SUCCESS;
    }
}