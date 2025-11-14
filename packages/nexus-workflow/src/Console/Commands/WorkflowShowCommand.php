<?php

namespace Nexus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Workflow\Models\WorkflowDefinition;

/**
 * Show Workflow Definition Command
 * 
 * Displays detailed information about a specific workflow definition.
 * 
 * Usage:
 * php artisan workflow:show {code}
 * php artisan workflow:show purchase-order-approval
 * php artisan workflow:show --id=uuid
 * 
 * @package Nexus\Workflow\Console\Commands
 */
class WorkflowShowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow:show
                            {code? : Workflow code to show}
                            {--id= : Workflow ID (UUID)}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show detailed information about a workflow definition';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $code = $this->argument('code');
        $id = $this->option('id');

        if (!$code && !$id) {
            $this->error('Please provide either a workflow code or --id option');
            return Command::FAILURE;
        }

        // Find workflow
        if ($id) {
            $workflow = WorkflowDefinition::find($id);
        } else {
            $workflow = WorkflowDefinition::where('code', $code)
                ->where('is_active', true)
                ->orderBy('version', 'desc')
                ->first();
        }

        if (!$workflow) {
            $this->error('Workflow definition not found');
            return Command::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'id' => (string) $workflow->id,
                'code' => $workflow->code,
                'name' => $workflow->name,
                'version' => $workflow->version,
                'is_active' => $workflow->is_active,
                'definition' => $workflow->definition,
                'created_at' => $workflow->created_at->toIso8601String(),
                'updated_at' => $workflow->updated_at->toIso8601String(),
            ], JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        // Display workflow information
        $this->info('Workflow Definition Details');
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['ID', (string) $workflow->id],
                ['Code', $workflow->code],
                ['Name', $workflow->name],
                ['Version', 'v' . $workflow->version],
                ['Active', $workflow->is_active ? 'Yes' : 'No'],
                ['Created', $workflow->created_at->format('Y-m-d H:i:s')],
                ['Updated', $workflow->updated_at->format('Y-m-d H:i:s')],
            ]
        );

        $this->newLine();
        $this->info('Workflow Structure:');
        $this->newLine();

        $definition = $workflow->definition;

        // Show states
        if (isset($definition['states'])) {
            $this->line('<fg=yellow>States:</>');
            foreach ($definition['states'] as $state) {
                $type = $state['type'] ?? 'regular';
                $marker = match($type) {
                    'initial' => '▶',
                    'final' => '■',
                    default => '●',
                };
                $this->line("  {$marker} {$state['name']} - {$state['label']}");
            }
            $this->newLine();
        }

        // Show transitions
        if (isset($definition['transitions'])) {
            $this->line('<fg=yellow>Transitions:</>');
            foreach ($definition['transitions'] as $transition) {
                $from = $transition['from'];
                $to = $transition['to'];
                $name = $transition['name'];
                $this->line("  {$from} --[{$name}]--> {$to}");
            }
            $this->newLine();
        }

        // Show instance count
        $instanceCount = $workflow->instances()->count();
        $activeCount = $workflow->instances()->whereNotIn('current_state', 
            collect($definition['states'] ?? [])->where('type', 'final')->pluck('name')
        )->count();

        $this->info("Instances: {$instanceCount} total, {$activeCount} active");

        return Command::SUCCESS;
    }
}
