<?php

declare(strict_types=1);

use Nexus\Sequencing\Core\ValueObjects\CounterState;

describe('CounterState Value Object', function () {
    it('creates valid counter state', function () {
        $timestamp = new DateTimeImmutable('2024-11-14 10:00:00');
        $state = new CounterState(
            counter: 42,
            timestamp: $timestamp
        );

        expect($state->counter)->toBe(42);
        expect($state->timestamp)->toBe($timestamp);
        expect($state->lastResetAt)->toBeNull();
    });

    it('validates counter is not negative', function () {
        expect(fn () => new CounterState(
            counter: -1,
            timestamp: new DateTimeImmutable()
        ))->toThrow(InvalidArgumentException::class, 'Counter cannot be negative');
    });

    it('creates initial state with counter at zero', function () {
        $initial = CounterState::initial();
        
        expect($initial->counter)->toBe(0);
        expect($initial->lastResetAt)->toBeNull();
        expect($initial->timestamp)->toBeInstanceOf(DateTimeInterface::class);
    });

    it('creates incremented state', function () {
        $original = new CounterState(
            counter: 10,
            timestamp: new DateTimeImmutable('2024-11-14 10:00:00')
        );

        $incremented = $original->increment(5);

        expect($incremented->counter)->toBe(15);
        expect($original->counter)->toBe(10); // Original unchanged
        expect($incremented)->not->toBe($original); // Different instances
    });

    it('creates reset state', function () {
        $original = new CounterState(
            counter: 100,
            timestamp: new DateTimeImmutable('2024-11-14 10:00:00')
        );

        $reset = $original->reset(1);

        expect($reset->counter)->toBe(1);
        expect($reset->hasBeenReset())->toBeTrue();
        expect($reset->lastResetAt)->not->toBeNull();
    });

    it('calculates next value without modifying state', function () {
        $state = new CounterState(
            counter: 10,
            timestamp: new DateTimeImmutable()
        );

        expect($state->getNextValue(3))->toBe(13);
        expect($state->counter)->toBe(10); // State unchanged
    });

    it('converts to array representation', function () {
        $timestamp = new DateTimeImmutable('2024-11-14 10:00:00');
        $state = new CounterState(
            counter: 42,
            timestamp: $timestamp,
            lastResetAt: $timestamp
        );

        $array = $state->toArray();

        expect($array)->toHaveKey('counter');
        expect($array)->toHaveKey('timestamp');
        expect($array)->toHaveKey('last_reset_at');
        expect($array['counter'])->toBe(42);
    });
});