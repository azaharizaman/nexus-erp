<?php

declare(strict_types=1);

use Nexus\Sequencing\Core\Services\GenerationService;
use Nexus\Sequencing\Core\Services\DefaultResetStrategy;
use Nexus\Sequencing\Core\Engine\RegexPatternEvaluator;
use Nexus\Sequencing\Core\ValueObjects\SequenceConfig;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\GeneratedNumber;
use Nexus\Sequencing\Core\ValueObjects\ResetPeriod;
use Nexus\Sequencing\Core\Contracts\CounterRepositoryInterface;

describe('GenerationService Core Logic', function () {
    it('validates pattern before generation', function () {
        // Create mock repository for testing
        $mockRepository = mock(CounterRepositoryInterface::class);
        $patternEvaluator = new RegexPatternEvaluator();
        $resetStrategy = new DefaultResetStrategy();
        
        $service = new GenerationService(
            $mockRepository,
            $patternEvaluator,
            $resetStrategy
        );

        $config = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'test',
            pattern: '{INVALID_BRACKET',  // Invalid pattern
            resetPeriod: ResetPeriod::NEVER
        );

        $context = GenerationContext::empty();

        expect(fn() => $service->generate($config, $context))
            ->toThrow(InvalidArgumentException::class, 'Pattern validation failed');
    });

    it('creates sequence if it does not exist', function () {
        // Create mock repository for testing
        $mockRepository = mock(CounterRepositoryInterface::class);
        $patternEvaluator = new RegexPatternEvaluator();
        $resetStrategy = new DefaultResetStrategy();
        
        $service = new GenerationService(
            $mockRepository,
            $patternEvaluator,
            $resetStrategy
        );

        $config = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER:4}',
            resetPeriod: ResetPeriod::NEVER
        );

        $context = GenerationContext::empty();
        $now = new DateTimeImmutable('2024-11-14 10:00:00');

        // Mock expectations
        $mockRepository->shouldReceive('exists')->with($config)->once()->andReturn(false);
        $mockRepository->shouldReceive('saveSequence')->with($config)->once()->andReturn(true);
        $mockRepository->shouldReceive('getCurrentState')->with($config)->once()->andReturn(CounterState::initial());
        $mockRepository->shouldReceive('lockAndIncrement')->with($config)->once()->andReturn(
            GeneratedNumber::createAt('PO-0001', 1, $now)
        );

        $result = $service->generate($config, $context, $now);

        expect($result->value)->toBe('PO-0001');
        expect($result->counter)->toBe(1);
    });

    it('previews next number without consuming counter', function () {
        // Create fresh mocks for this test
        $mockRepository = mock(CounterRepositoryInterface::class);
        $patternEvaluator = new RegexPatternEvaluator();
        $resetStrategy = new DefaultResetStrategy();
        
        $service = new GenerationService(
            $mockRepository,
            $patternEvaluator,
            $resetStrategy
        );

        $config = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{YEAR}-{COUNTER:4}',
            resetPeriod: ResetPeriod::NEVER
        );

        $context = GenerationContext::empty();
        $now = new DateTimeImmutable('2024-11-14 10:00:00');

        // Mock current state (counter at 42)
        $currentState = new CounterState(
            counter: 42,
            timestamp: $now
        );

        $mockRepository->shouldReceive('exists')->with($config)->once()->andReturn(true);
        $mockRepository->shouldReceive('getCurrentState')->with($config)->once()->andReturn($currentState);

        $preview = $service->preview($config, $context, $now);

        expect($preview->value)->toBe('PO-2024-0043'); // Next counter would be 43
        expect($preview->counter)->toBe(43);
        expect($preview->getMetadata('is_preview'))->toBeTrue();
    });
});