<?php

namespace Nexus\Uom\Console\Commands;

use Illuminate\Console\Command;

class UomSeedCommand extends Command
{
    protected $signature = 'uom:seed
        {--database= : The database connection to use}
        {--class= : Override the seeder class}
        {--force : Force the operation to run when in production}';

    protected $description = 'Seed the database with the package\'s baseline UOM dataset.';

    public function handle(): int
    {
        $classOption = $this->option('class');
        $configured = config('uom.seeders.class');
        $fallback = app()->bound('uom.database.seeder') ? app('uom.database.seeder') : null;

        $seederClass = $classOption ?: ($configured ?: $fallback);

        if (! is_string($seederClass) || $seederClass === '') {
            $this->error('Unable to resolve a seeder class.');

            return self::FAILURE;
        }

        if (! class_exists($seederClass)) {
            $this->error("Seeder class '{$seederClass}' does not exist.");

            return self::FAILURE;
        }

        $arguments = [
            '--class' => $seederClass,
        ];

        if ($database = $this->option('database')) {
            $arguments['--database'] = $database;
        }

        if ($this->option('force')) {
            $arguments['--force'] = true;
        }

        $this->info(sprintf('Seeding database using %s.', class_basename($seederClass)));

        $this->call('db:seed', $arguments);

        $this->info('Seeding complete.');

        return self::SUCCESS;
    }
}
