<?php

declare(strict_types=1);

namespace Nexus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Workflow\Models\WorkflowDefinition;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class WorkflowExportCommand extends Command
{
    protected $signature = 'workflow:export {code} {--output=} {--version=}';
    protected $description = 'Export workflow definition to JSON';

    public function handle(): int
    {
        $code = $this->argument('code');
        $version = $this->option('version');

        $query = WorkflowDefinition::where('code', $code);
        
        if ($version) {
            $query->where('version', $version);
        } else {
            $query->orderBy('version', 'desc');
        }

        $workflow = $query->first();

        if (!$workflow) {
            error('Workflow not found');
            return self::FAILURE;
        }

        $data = [
            'code' => $workflow->code,
            'name' => $workflow->name,
            'version' => $workflow->version,
            'definition' => $workflow->definition,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);

        if ($output = $this->option('output')) {
            file_put_contents($output, $json);
            info("Workflow exported to: {$output}");
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
