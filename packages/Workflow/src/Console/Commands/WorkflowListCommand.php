<?php

declare(strict_types=1);

namespace Nexus\Workflow\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Workflow\Models\WorkflowDefinition;

/**
 * List Workflow Definitions Command
 * 
 * Lists all workflow definitions in the system with their details.
 * 
 * Usage:
 * php artisan workflow:list
 * php artisan workflow:list --active
 * php artisan workflow:list --inactive
 * 
 * @package Nexus\Workflow\Console\Commands
 */
class WorkflowListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow:list
                            {--active : Show only active workflows}
                            {--inactive : Show only inactive workflows}
                            {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all workflow definitions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = WorkflowDefinition::query();

        // Apply filters
        if ($this->option('active')) {
            $query->where('is_active', true);
        } elseif ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        $workflows = $query->orderBy('code')
            ->orderBy('version', 'desc')
            ->get();

        if ($workflows->isEmpty()) {
            $this->info('No workflow definitions found.');
            return Command::SUCCESS;
        }

        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($workflows->map(fn($w) => [
                'id' => (string) $w->id,
                'code' => $w->code,
                'name' => $w->name,
                'version' => $w->version,
                'is_active' => $w->is_active,
                'created_at' => $w->created_at->toIso8601String(),
            ]), JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        // Table format
        $this->table(
            ['ID', 'Code', 'Name', 'Version', 'Active', 'Created'],
            $workflows->map(fn($w) => [
                substr((string) $w->id, 0, 8) . '...',
                $w->code,
                $w->name,
                'v' . $w->version,
                $w->is_active ? '✓' : '✗',
                $w->created_at->diffForHumans(),
            ])->toArray()
        );

        $this->newLine();
        $this->info("Total: {$workflows->count()} workflow(s)");

        return Command::SUCCESS;
    }
}
