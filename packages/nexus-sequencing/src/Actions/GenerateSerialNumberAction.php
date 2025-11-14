<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Actions;

use Nexus\Sequencing\Core\Services\GenerationService;
use Nexus\Sequencing\Core\ValueObjects\SequenceConfig;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use Nexus\Sequencing\Core\ValueObjects\ResetPeriod as CoreResetPeriod;
use Nexus\Sequencing\Events\SequenceGeneratedEvent;
use Nexus\Sequencing\Models\SerialNumberLog;
use Nexus\Sequencing\Models\Sequence;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Generate Serial Number Action
 *
 * Generates a new serial number using Core services with atomic counter increment
 * and transaction-safe pattern evaluation. Acts as a Laravel adapter for the
 * framework-agnostic Core business logic.
 */
class GenerateSerialNumberAction
{
    use AsAction;

    /**
     * Create a new action instance.
     *
     * @param  GenerationService  $generationService  The core generation service
     */
    public function __construct(
        private readonly GenerationService $generationService
    ) {}

    /**
     * Handle the action.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  array<string, mixed>  $context  Additional context for pattern variables
     * @return string The generated serial number
     *
     * @throws \InvalidArgumentException When sequence configuration is invalid
     * @throws \RuntimeException When sequence generation fails
     */
    public function handle(string $tenantId, string $sequenceName, array $context = []): string
    {
        // Find sequence configuration from database
        $sequenceModel = Sequence::where('tenant_id', $tenantId)
            ->where('sequence_name', $sequenceName)
            ->first();

        if ($sequenceModel === null) {
            throw new \InvalidArgumentException(
                "Sequence '{$sequenceName}' not found for tenant '{$tenantId}'"
            );
        }

        // Create Core SequenceConfig from Laravel model
        $config = new SequenceConfig(
            scopeIdentifier: $tenantId,
            sequenceName: $sequenceName,
            pattern: $sequenceModel->pattern,
            resetPeriod: CoreResetPeriod::from($sequenceModel->reset_period->value),
            padding: $sequenceModel->padding,
            stepSize: $sequenceModel->step_size,
            resetLimit: $sequenceModel->reset_limit,
            evaluatorType: 'regex'
        );

        // Create Core GenerationContext
        $generationContext = new GenerationContext(array_merge([
            'tenant_code' => $context['tenant_code'] ?? '',
            'prefix' => $context['prefix'] ?? '',
            'department_code' => $context['department_code'] ?? '',
        ], $context));

        // Delegate to Core service for generation
        $result = $this->generationService->generate($config, $generationContext);

        // Log generation (Laravel-specific concern)
        if (config('serial-numbering.log_generations', true)) {
            $this->logGeneration($tenantId, $sequenceName, $result->value, $context);
        }

        // Dispatch Laravel event
        event(new SequenceGeneratedEvent(
            $tenantId,
            $sequenceName,
            $result->value,
            $result->counter
        ));

        return $result->value;
    }

    /**
     * Log the serial number generation.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  string  $generatedNumber  The generated number
     * @param  array<string, mixed>  $context  Additional context
     * @return void
     */
    private function logGeneration(
        string $tenantId,
        string $sequenceName,
        string $generatedNumber,
        array $context
    ): void {
        $causerType = null;
        $causerId = null;

        if (auth()->check()) {
            $causer = auth()->user();
            $causerType = get_class($causer);
            $causerId = $causer->id;
        }

        SerialNumberLog::create([
            'tenant_id' => $tenantId,
            'sequence_name' => $sequenceName,
            'generated_number' => $generatedNumber,
            'causer_type' => $causerType,
            'causer_id' => $causerId,
            'metadata' => $context,
            'created_at' => now(),
        ]);
    }
}
