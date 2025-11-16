<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Contracts;

use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\ResetPeriod;
use DateTimeInterface;

/**
 * Reset Strategy Interface
 * 
 * Contract for determining when sequence counters should be reset.
 * Handles both time-based resets (daily/monthly/yearly) and count-based resets.
 * 
 * This interface is framework-agnostic and uses pure PHP DateTime objects.
 * 
 * @package Nexus\Sequencing\Core\Contracts
 */
interface ResetStrategyInterface
{
    /**
     * Check if counter should be reset based on current state
     * 
     * @param CounterState $currentState Current counter state
     * @param ResetPeriod $resetPeriod The reset period configuration
     * @param ?int $resetLimit Optional count-based reset limit
     * @param DateTimeInterface $now Current timestamp for time-based resets
     * @return bool True if counter should be reset
     */
    public function shouldReset(
        CounterState $currentState,
        ResetPeriod $resetPeriod,
        ?int $resetLimit = null,
        ?DateTimeInterface $now = null
    ): bool;

    /**
     * Calculate when the next reset should occur
     * 
     * @param ResetPeriod $resetPeriod The reset period configuration
     * @param DateTimeInterface $from Base timestamp for calculation
     * @return DateTimeInterface|null Next reset timestamp, or null if no reset scheduled
     */
    public function calculateNextResetTime(
        ResetPeriod $resetPeriod,
        DateTimeInterface $from
    ): ?DateTimeInterface;

    /**
     * Get reset boundary for a specific period
     * 
     * For example, for MONTHLY reset on 2024-03-15, should return 2024-03-01 00:00:00
     * 
     * @param ResetPeriod $resetPeriod The reset period
     * @param DateTimeInterface $timestamp Reference timestamp
     * @return DateTimeInterface Start of the reset period containing the timestamp
     */
    public function getResetBoundary(
        ResetPeriod $resetPeriod,
        DateTimeInterface $timestamp
    ): DateTimeInterface;

    /**
     * Check if two timestamps are in the same reset period
     * 
     * @param DateTimeInterface $timestamp1 First timestamp
     * @param DateTimeInterface $timestamp2 Second timestamp
     * @param ResetPeriod $resetPeriod The reset period to check
     * @return bool True if both timestamps are in same reset period
     */
    public function isSameResetPeriod(
        DateTimeInterface $timestamp1,
        DateTimeInterface $timestamp2,
        ResetPeriod $resetPeriod
    ): bool;

    /**
     * Calculate remaining count until reset limit
     * 
     * @param CounterState $currentState Current counter state
     * @param int $resetLimit The reset limit
     * @return int Remaining count before reset (0 if limit reached)
     */
    public function remainingUntilCountReset(
        CounterState $currentState,
        int $resetLimit
    ): int;

    /**
     * Calculate remaining time until next time-based reset
     * 
     * @param ResetPeriod $resetPeriod The reset period
     * @param DateTimeInterface $now Current timestamp
     * @return int Remaining seconds until reset (0 if reset time has passed)
     */
    public function remainingUntilTimeReset(
        ResetPeriod $resetPeriod,
        DateTimeInterface $now
    ): int;
}