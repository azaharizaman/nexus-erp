<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Sequence Interface
 *
 * Defines the data structure for a sequence configuration.
 * This interface represents what a Sequence IS, not how it's stored.
 */
interface SequenceInterface
{
    /**
     * Get the unique identifier for the sequence.
     *
     * @return int|string
     */
    public function getId();

    /**
     * Get the tenant identifier.
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Get the sequence name.
     *
     * @return string
     */
    public function getSequenceName(): string;

    /**
     * Get the pattern template.
     *
     * @return string
     */
    public function getPattern(): string;

    /**
     * Get the reset period (never, daily, monthly, yearly).
     *
     * @return string
     */
    public function getResetPeriod(): string;

    /**
     * Get the padding for the counter.
     *
     * @return int
     */
    public function getPadding(): int;

    /**
     * Get the step size for counter increment.
     *
     * @return int
     */
    public function getStepSize(): int;

    /**
     * Get the reset limit (count-based reset).
     *
     * @return int|null
     */
    public function getResetLimit(): ?int;

    /**
     * Get the current counter value.
     *
     * @return int
     */
    public function getCurrentValue(): int;

    /**
     * Get the last reset timestamp.
     *
     * @return \DateTimeInterface|null
     */
    public function getLastResetAt(): ?\DateTimeInterface;

    /**
     * Get additional metadata.
     *
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array;

    /**
     * Get the version for optimistic locking.
     *
     * @return int
     */
    public function getVersion(): int;

    /**
     * Get the creation timestamp.
     *
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get the last update timestamp.
     *
     * @return \DateTimeInterface
     */
    public function getUpdatedAt(): \DateTimeInterface;
}
