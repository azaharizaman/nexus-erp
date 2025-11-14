<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Adapters\Laravel;

use Nexus\Sequencing\Core\Contracts\CounterRepositoryInterface;
use Nexus\Sequencing\Core\ValueObjects\SequenceConfig;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\GeneratedNumber;
use Nexus\Sequencing\Models\Sequence;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;

/**
 * Eloquent Counter Repository
 * 
 * Laravel/Eloquent implementation of the CounterRepositoryInterface.
 * Provides atomic counter operations using SELECT FOR UPDATE.
 * 
 * This adapter bridges the pure Core logic with Laravel's database layer.
 * 
 * @package Nexus\Sequencing\Adapters\Laravel
 */
class EloquentCounterRepository implements CounterRepositoryInterface
{
    public function find(SequenceConfig $config): ?CounterState
    {
        $sequence = $this->findSequenceModel($config);
        
        if ($sequence === null) {
            return null;
        }

        return new CounterState(
            counter: $sequence->current_value,
            timestamp: new DateTimeImmutable($sequence->updated_at->toDateTimeString()),
            lastResetAt: $sequence->last_reset_at 
                ? new DateTimeImmutable($sequence->last_reset_at->toDateTimeString())
                : null
        );
    }

    public function lockAndIncrement(SequenceConfig $config): GeneratedNumber
    {
        return DB::transaction(function () use ($config) {
            // Find and lock sequence for atomic update
            $sequence = Sequence::where('tenant_id', $config->scopeIdentifier)
                ->where('sequence_name', $config->sequenceName)
                ->lockForUpdate()
                ->firstOrFail();

            // Increment counter
            $newCounter = $sequence->current_value + $config->stepSize;
            $now = new DateTimeImmutable();

            // Update sequence
            $sequence->current_value = $newCounter;
            $sequence->save();

            // Evaluate pattern to generate the actual number
            // Note: In a real implementation, we'd inject the PatternEvaluatorInterface
            // For now, we'll do basic pattern replacement
            $generatedValue = $this->evaluatePattern(
                $config->pattern,
                $newCounter,
                $config,
                $now
            );

            return GeneratedNumber::createAt(
                value: $generatedValue,
                counter: $newCounter,
                generatedAt: $now,
                metadata: [
                    'sequence_id' => $sequence->id,
                    'tenant_id' => $config->scopeIdentifier,
                    'sequence_name' => $config->sequenceName,
                ]
            );
        });
    }

    public function reset(SequenceConfig $config, CounterState $newState): CounterState
    {
        return DB::transaction(function () use ($config, $newState) {
            $sequence = $this->findSequenceModel($config);
            
            if ($sequence === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Cannot reset sequence that does not exist: %s',
                        $config->getUniqueKey()
                    )
                );
            }

            // Update sequence with new state
            $sequence->current_value = $newState->counter;
            $sequence->last_reset_at = now();
            $sequence->save();

            return new CounterState(
                counter: $sequence->current_value,
                timestamp: new DateTimeImmutable($sequence->updated_at->toDateTimeString()),
                lastResetAt: new DateTimeImmutable($sequence->last_reset_at->toDateTimeString())
            );
        });
    }

    public function getCurrentState(SequenceConfig $config): CounterState
    {
        $sequence = $this->findSequenceModel($config);
        
        if ($sequence === null) {
            return CounterState::initial();
        }

        return new CounterState(
            counter: $sequence->current_value,
            timestamp: new DateTimeImmutable($sequence->updated_at->toDateTimeString()),
            lastResetAt: $sequence->last_reset_at 
                ? new DateTimeImmutable($sequence->last_reset_at->toDateTimeString())
                : null
        );
    }

    public function saveSequence(SequenceConfig $config): bool
    {
        try {
            Sequence::updateOrCreate(
                [
                    'tenant_id' => $config->scopeIdentifier,
                    'sequence_name' => $config->sequenceName,
                ],
                [
                    'pattern' => $config->pattern,
                    'reset_period' => $config->resetPeriod->value,
                    'current_value' => 0,
                    'padding' => $config->padding,
                    'last_reset_at' => null,
                    'metadata' => [
                        'step_size' => $config->stepSize,
                        'reset_limit' => $config->resetLimit,
                        'evaluator_type' => $config->evaluatorType,
                    ],
                ]
            );

            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Failed to save sequence: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    public function deleteSequence(SequenceConfig $config): bool
    {
        $deleted = Sequence::where('tenant_id', $config->scopeIdentifier)
            ->where('sequence_name', $config->sequenceName)
            ->delete();

        return $deleted > 0;
    }

    public function exists(SequenceConfig $config): bool
    {
        return Sequence::where('tenant_id', $config->scopeIdentifier)
            ->where('sequence_name', $config->sequenceName)
            ->exists();
    }

    public function findByScope(string $scopeIdentifier): array
    {
        $sequences = Sequence::where('tenant_id', $scopeIdentifier)->get();
        
        $configs = [];
        foreach ($sequences as $sequence) {
            $metadata = $sequence->metadata ?? [];
            
            $configs[] = new SequenceConfig(
                scopeIdentifier: $sequence->tenant_id,
                sequenceName: $sequence->sequence_name,
                pattern: $sequence->pattern,
                resetPeriod: \Nexus\Sequencing\Core\ValueObjects\ResetPeriod::from($sequence->reset_period),
                padding: $sequence->padding,
                stepSize: $metadata['step_size'] ?? 1,
                resetLimit: $metadata['reset_limit'] ?? null,
                evaluatorType: $metadata['evaluator_type'] ?? 'regex'
            );
        }

        return $configs;
    }

    /**
     * Find sequence model by config
     */
    private function findSequenceModel(SequenceConfig $config): ?Sequence
    {
        return Sequence::where('tenant_id', $config->scopeIdentifier)
            ->where('sequence_name', $config->sequenceName)
            ->first();
    }

    /**
     * Basic pattern evaluation for the adapter
     * 
     * Note: This is a simplified version. In the full implementation,
     * we would inject PatternEvaluatorInterface and use it here.
     */
    private function evaluatePattern(
        string $pattern,
        int $counter,
        SequenceConfig $config,
        DateTimeImmutable $timestamp
    ): string {
        // Basic variable replacement
        $variables = [
            '{YEAR}' => $timestamp->format('Y'),
            '{MONTH}' => $timestamp->format('m'),
            '{DAY}' => $timestamp->format('d'),
            '{COUNTER}' => str_pad((string) $counter, $config->padding, '0', STR_PAD_LEFT),
        ];

        // Handle parameterized counter
        $pattern = preg_replace_callback(
            '/\{COUNTER:(\d+)\}/',
            function ($matches) use ($counter) {
                $padding = (int) $matches[1];
                return str_pad((string) $counter, $padding, '0', STR_PAD_LEFT);
            },
            $pattern
        );

        return strtr($pattern, $variables);
    }
}