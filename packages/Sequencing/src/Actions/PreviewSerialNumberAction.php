<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Actions;

use Nexus\Sequencing\Contracts\PatternParserContract;
use Nexus\Sequencing\Contracts\SequenceRepositoryContract;
use Nexus\Sequencing\Core\Contracts\ResetStrategyInterface;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\SequenceConfig;
use Nexus\Sequencing\Core\ValueObjects\ResetPeriod;
use Nexus\Sequencing\Exceptions\SequenceNotFoundException;
use Nexus\Sequencing\Models\Sequence;
use Lorisleiva\Actions\Concerns\AsAction;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Enhanced Preview Serial Number Action
 *
 * Generates a comprehensive preview of the next serial number with
 * additional information about remaining count, reset predictions,
 * and sequence status.
 */
class PreviewSerialNumberAction
{
    use AsAction;

    /**
     * Create a new action instance.
     *
     * @param  SequenceRepositoryContract  $repository  The sequence repository
     * @param  PatternParserContract  $parser  The pattern parser
     * @param  ResetStrategyInterface  $resetStrategy  The reset strategy
     */
    public function __construct(
        private readonly SequenceRepositoryContract $repository,
        private readonly PatternParserContract $parser,
        private readonly ResetStrategyInterface $resetStrategy
    ) {}

    /**
     * Handle the action.
     *
     * @param  string  $tenantId  The tenant identifier
     * @param  string  $sequenceName  The sequence name
     * @param  array<string, mixed>  $context  Additional context for pattern variables
     * @param  bool  $detailed  Whether to include detailed reset information
     * @return array{
     *     preview: string,
     *     current_value: int,
     *     next_value: int,
     *     step_size: int,
     *     reset_info: array{
     *         type: string,
     *         period: string,
     *         limit: int|null,
     *         remaining_count: int|null,
     *         next_reset_date: string|null,
     *         will_reset_next: bool
     *     }
     * } The preview information
     *
     * @throws SequenceNotFoundException
     */
    public function handle(
        string $tenantId, 
        string $sequenceName, 
        array $context = [],
        bool $detailed = true
    ): array {
        // Find sequence configuration
        $sequence = $this->repository->find($tenantId, $sequenceName);

        if ($sequence === null) {
            throw SequenceNotFoundException::create($tenantId, $sequenceName);
        }

        // Calculate next counter value based on step size
        $nextCounter = $sequence->current_value + $sequence->step_size;

        // Build context for pattern evaluation
        $patternContext = array_merge([
            'counter' => $nextCounter,
            'padding' => $sequence->padding,
            'tenant_code' => $context['tenant_code'] ?? '',
            'prefix' => $context['prefix'] ?? '',
            'department_code' => $context['department_code'] ?? '',
        ], $context);

        // Generate preview serial number
        $previewNumber = $this->parser->preview($sequence->pattern, $patternContext);

        // Base response
        $response = [
            'preview' => $previewNumber,
            'current_value' => $sequence->current_value,
            'next_value' => $nextCounter,
            'step_size' => $sequence->step_size,
        ];

        // Add detailed reset information if requested
        if ($detailed) {
            $response['reset_info'] = $this->buildResetInfo($sequence);
        }

        return $response;
    }

    /**
     * Build comprehensive reset information for the sequence.
     *
     * @param  Sequence  $sequence  The sequence model
     * @return array{
     *     type: string,
     *     period: string,
     *     limit: int|null,
     *     remaining_count: int|null,
     *     next_reset_date: string|null,
     *     will_reset_next: bool
     * } Reset information
     */
    private function buildResetInfo(Sequence $sequence): array
    {
        $now = new DateTimeImmutable();
        
        // Convert Laravel enum to Core enum
        $coreResetPeriod = ResetPeriod::from($sequence->reset_period->value);
        
        // Create current counter state
        $counterState = new CounterState(
            counter: $sequence->current_value,
            timestamp: $sequence->updated_at ?? $now,
            lastResetAt: $sequence->last_reset_at
        );

        // Create sequence config for reset strategy
        $config = new SequenceConfig(
            scopeIdentifier: $sequence->tenant_id,
            sequenceName: $sequence->name,
            pattern: $sequence->pattern,
            resetPeriod: $coreResetPeriod,
            padding: $sequence->padding,
            resetLimit: $sequence->reset_limit
        );

        // Calculate remaining count until reset
        $remainingCount = null;
        if ($sequence->reset_limit !== null) {
            $remainingCount = max(0, $sequence->reset_limit - $sequence->current_value);
        }

        // Calculate next reset date for time-based resets
        $nextResetDate = null;
        if ($sequence->reset_period !== \Nexus\Sequencing\Enums\ResetPeriod::NEVER) {
            $resetDateTime = $this->resetStrategy->calculateNextResetTime(
                $coreResetPeriod,
                $sequence->updated_at ?? $now
            );
            $nextResetDate = $resetDateTime?->format(DateTimeInterface::ATOM);
        }

        // Check if next generation will trigger a reset
        $nextCounterState = new CounterState(
            counter: $sequence->current_value + $sequence->step_size,
            timestamp: $now,
            lastResetAt: $sequence->last_reset_at
        );
        
        $willResetNext = $this->resetStrategy->shouldReset(
            $nextCounterState,
            $coreResetPeriod,
            $sequence->reset_limit,
            $now
        );

        // Determine reset type
        $resetType = 'none';
        if ($sequence->reset_limit !== null && $sequence->reset_period !== \Nexus\Sequencing\Enums\ResetPeriod::NEVER) {
            $resetType = 'both';
        } elseif ($sequence->reset_limit !== null) {
            $resetType = 'count';
        } elseif ($sequence->reset_period !== \Nexus\Sequencing\Enums\ResetPeriod::NEVER) {
            $resetType = 'time';
        }

        return [
            'type' => $resetType,
            'period' => $sequence->reset_period->value,
            'limit' => $sequence->reset_limit,
            'remaining_count' => $remainingCount,
            'next_reset_date' => $nextResetDate,
            'will_reset_next' => $willResetNext,
        ];
    }
}
