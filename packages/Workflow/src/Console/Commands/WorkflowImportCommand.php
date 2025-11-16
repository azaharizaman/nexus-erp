<?php

declare(strict_types=1);

namespace Nexus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Workflow\Services\WorkflowDefinitionService;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class WorkflowImportCommand extends Command
{
    protected $signature = 'workflow:import {file} {--activate}';
    protected $description = 'Import workflow definition from JSON file';

    public function handle(): int
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            error("File not found: {$file}");
            return self::FAILURE;
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        if (!$data) {
            error('Invalid JSON format');
            return self::FAILURE;
        }

        try {
            $service = app(WorkflowDefinitionService::class);
            $workflow = $service->create($data);

            if ($this->option('activate')) {
                $workflow->update(['is_active' => true]);
            }

            info("Workflow imported successfully: {$workflow->name}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            error("Import failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
