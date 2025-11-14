<?php

declare(strict_types=1);

namespace Nexus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Workflow\Models\WorkflowDefinition;
use Nexus\Workflow\Engines\DatabaseWorkflowEngine;
use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class WorkflowActivateCommand extends Command
{
    protected $signature = 'workflow:activate {codeOrId}';
    protected $description = 'Activate a workflow definition';

    public function handle(): int
    {
        $codeOrId = $this->argument('codeOrId');

        // Try by ID first
        $workflow = WorkflowDefinition::find($codeOrId);

        // If not found, try by code
        if (!$workflow) {
            $workflow = WorkflowDefinition::where('code', $codeOrId)
                ->orderBy('version', 'desc')
                ->first();
        }

        if (!$workflow) {
            error('Workflow not found');
            return self::FAILURE;
        }

        $workflow->update(['is_active' => true]);

        // Clear cache
        $engine = app(DatabaseWorkflowEngine::class);
        $engine->clearCache($workflow->id);

        info("Workflow activated: {$workflow->name} (v{$workflow->version})");
        return self::SUCCESS;
    }
}
