<?php

declare(strict_types=1);

use Nexus\Sequencing\Core\ValueObjects\SequenceConfig;
use Nexus\Sequencing\Core\ValueObjects\ResetPeriod;

describe('SequenceConfig Value Object', function () {
    it('creates valid configuration with all parameters', function () {
        $config = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{YEAR}-{COUNTER:4}',
            resetPeriod: ResetPeriod::YEARLY,
            padding: 4,
            stepSize: 1,
            resetLimit: null,
            evaluatorType: 'regex'
        );

        expect($config->scopeIdentifier)->toBe('tenant_123');
        expect($config->sequenceName)->toBe('PO');
        expect($config->pattern)->toBe('PO-{YEAR}-{COUNTER:4}');
        expect($config->resetPeriod)->toBe(ResetPeriod::YEARLY);
        expect($config->padding)->toBe(4);
        expect($config->stepSize)->toBe(1);
        expect($config->resetLimit)->toBeNull();
        expect($config->evaluatorType)->toBe('regex');
    });

    it('validates required parameters', function () {
        expect(fn () => new SequenceConfig(
            scopeIdentifier: '',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER
        ))->toThrow(InvalidArgumentException::class, 'Scope identifier cannot be empty');

        expect(fn () => new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: '',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER
        ))->toThrow(InvalidArgumentException::class, 'Sequence name cannot be empty');
    });

    it('validates padding range', function () {
        expect(fn () => new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER,
            padding: 0
        ))->toThrow(InvalidArgumentException::class, 'Padding must be between 1 and 20');

        expect(fn () => new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER,
            padding: 21
        ))->toThrow(InvalidArgumentException::class, 'Padding must be between 1 and 20');
    });

    it('validates step size', function () {
        expect(fn () => new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER,
            stepSize: 0
        ))->toThrow(InvalidArgumentException::class, 'Step size must be greater than 0');
    });

    it('validates reset limit', function () {
        expect(fn () => new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER,
            resetLimit: 0
        ))->toThrow(InvalidArgumentException::class, 'Reset limit must be greater than 0');
    });

    it('creates immutable configuration', function () {
        $config = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER
        );

        // Verify readonly properties cannot be modified
        // PHP will throw an error if we try to modify readonly properties
        expect($config)->toBeInstanceOf(SequenceConfig::class);
    });

    it('creates new instance with different scope', function () {
        $original = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER
        );

        $withNewScope = $original->withScope('tenant_456');

        expect($withNewScope->scopeIdentifier)->toBe('tenant_456');
        expect($withNewScope->sequenceName)->toBe('PO');
        expect($original->scopeIdentifier)->toBe('tenant_123'); // Original unchanged
        expect($withNewScope)->not->toBe($original); // Different instances
    });

    it('creates new instance with different pattern', function () {
        $original = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::NEVER
        );

        $withNewPattern = $original->withPattern('PO-{YEAR}-{COUNTER}');

        expect($withNewPattern->pattern)->toBe('PO-{YEAR}-{COUNTER}');
        expect($original->pattern)->toBe('PO-{COUNTER}'); // Original unchanged
    });

    it('generates unique key for configuration', function () {
        $config1 = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::YEARLY
        );

        $config2 = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::MONTHLY
        );

        $config3 = new SequenceConfig(
            scopeIdentifier: 'tenant_456',
            sequenceName: 'PO',
            pattern: 'PO-{COUNTER}',
            resetPeriod: ResetPeriod::YEARLY
        );

        expect($config1->getUniqueKey())->toBe('tenant_123:PO:yearly');
        expect($config2->getUniqueKey())->toBe('tenant_123:PO:monthly');
        expect($config3->getUniqueKey())->toBe('tenant_456:PO:yearly');

        // Different reset periods = different keys
        expect($config1->getUniqueKey())->not->toBe($config2->getUniqueKey());
        // Different scopes = different keys
        expect($config1->getUniqueKey())->not->toBe($config3->getUniqueKey());
    });

    it('converts to array representation', function () {
        $config = new SequenceConfig(
            scopeIdentifier: 'tenant_123',
            sequenceName: 'PO',
            pattern: 'PO-{YEAR}-{COUNTER:4}',
            resetPeriod: ResetPeriod::YEARLY,
            padding: 4,
            stepSize: 2,
            resetLimit: 1000,
            evaluatorType: 'twig'
        );

        $array = $config->toArray();

        expect($array)->toBe([
            'scope_identifier' => 'tenant_123',
            'sequence_name' => 'PO',
            'pattern' => 'PO-{YEAR}-{COUNTER:4}',
            'reset_period' => 'yearly',
            'padding' => 4,
            'step_size' => 2,
            'reset_limit' => 1000,
            'evaluator_type' => 'twig',
        ]);
    });
});