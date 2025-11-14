<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Services;

use Nexus\Sequencing\Core\Contracts\ResetStrategyInterface;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\ResetPeriod;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * Default Reset Strategy
 * 
 * Implements time-based and count-based reset logic for sequence counters.
 * Handles daily, monthly, yearly, and count-based reset periods.
 * 
 * This is a pure PHP implementation with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\Services
 */
class DefaultResetStrategy implements ResetStrategyInterface
{
    public function shouldReset(
        CounterState $currentState,
        ResetPeriod $resetPeriod,
        ?int $resetLimit = null,
        ?DateTimeInterface $now = null
    ): bool {
        $now = $now ?? new DateTimeImmutable();

        // Check count-based reset first
        if ($resetLimit !== null && $currentState->counter >= $resetLimit) {
            return true;
        }

        // Check time-based reset
        if ($resetPeriod === ResetPeriod::NEVER) {
            return false;
        }

        // If counter has never been reset, check if it's time for first reset
        if (!$currentState->hasBeenReset()) {
            // For new sequences, reset if we're past the boundary of current period
            return !$this->isSameResetPeriod($currentState->timestamp, $now, $resetPeriod);
        }

        // Check if we've moved to a new reset period since last reset
        if ($currentState->lastResetAt !== null) {
            return !$this->isSameResetPeriod($currentState->lastResetAt, $now, $resetPeriod);
        }

        // If no last reset, compare with counter timestamp
        return !$this->isSameResetPeriod($currentState->timestamp, $now, $resetPeriod);
    }

    public function calculateNextResetTime(
        ResetPeriod $resetPeriod,
        DateTimeInterface $from
    ): ?DateTimeInterface {
        if ($resetPeriod === ResetPeriod::NEVER) {
            return null;
        }

        $fromMutable = new \DateTime($from->format(DateTimeInterface::ATOM));

        switch ($resetPeriod) {
            case ResetPeriod::DAILY:
                // Next day at 00:00:00
                $fromMutable->modify('+1 day')->setTime(0, 0, 0);
                break;

            case ResetPeriod::MONTHLY:
                // First day of next month at 00:00:00
                $fromMutable->modify('first day of next month')->setTime(0, 0, 0);
                break;

            case ResetPeriod::YEARLY:
                // January 1st of next year at 00:00:00
                $fromMutable->modify('first day of January next year')->setTime(0, 0, 0);
                break;

            default:
                return null;
        }

        return DateTimeImmutable::createFromMutable($fromMutable);
    }

    public function getResetBoundary(
        ResetPeriod $resetPeriod,
        DateTimeInterface $timestamp
    ): DateTimeInterface {
        $mutable = new \DateTime($timestamp->format(DateTimeInterface::ATOM));

        switch ($resetPeriod) {
            case ResetPeriod::DAILY:
                // Start of the day
                $mutable->setTime(0, 0, 0);
                break;

            case ResetPeriod::MONTHLY:
                // First day of the month
                $mutable->modify('first day of this month')->setTime(0, 0, 0);
                break;

            case ResetPeriod::YEARLY:
                // January 1st of the year
                $mutable->modify('first day of January this year')->setTime(0, 0, 0);
                break;

            case ResetPeriod::NEVER:
                // Return original timestamp
                return $timestamp;
        }

        return DateTimeImmutable::createFromMutable($mutable);
    }

    public function isSameResetPeriod(
        DateTimeInterface $timestamp1,
        DateTimeInterface $timestamp2,
        ResetPeriod $resetPeriod
    ): bool {
        if ($resetPeriod === ResetPeriod::NEVER) {
            return true;
        }

        $boundary1 = $this->getResetBoundary($resetPeriod, $timestamp1);
        $boundary2 = $this->getResetBoundary($resetPeriod, $timestamp2);

        return $boundary1->format('Y-m-d H:i:s') === $boundary2->format('Y-m-d H:i:s');
    }

    public function remainingUntilCountReset(
        CounterState $currentState,
        int $resetLimit
    ): int {
        return max(0, $resetLimit - $currentState->counter);
    }

    public function remainingUntilTimeReset(
        ResetPeriod $resetPeriod,
        DateTimeInterface $now
    ): int {
        $nextReset = $this->calculateNextResetTime($resetPeriod, $now);
        
        if ($nextReset === null) {
            return 0;
        }

        $diff = $nextReset->getTimestamp() - $now->getTimestamp();
        
        return max(0, $diff);
    }
}