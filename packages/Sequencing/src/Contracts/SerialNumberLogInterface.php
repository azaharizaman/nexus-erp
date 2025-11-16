<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Serial Number Log Interface
 *
 * Defines the data structure for a serial number generation log entry.
 * This interface represents what a log entry IS, not how it's stored.
 */
interface SerialNumberLogInterface
{
    /**
     * Get the unique identifier for the log entry.
     *
     * @return int|string
     */
    public function getId();

    /**
     * Get the sequence ID this log entry belongs to.
     *
     * @return int|string
     */
    public function getSequenceId();

    /**
     * Get the generated serial number.
     *
     * @return string
     */
    public function getGeneratedNumber(): string;

    /**
     * Get the counter value used.
     *
     * @return int
     */
    public function getCounterValue(): int;

    /**
     * Get the context data used during generation.
     *
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array;

    /**
     * Get the type of action (generated, overridden, reset).
     *
     * @return string
     */
    public function getActionType(): string;

    /**
     * Get the reason for manual override or reset.
     *
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * Get the user ID who caused this action.
     *
     * @return int|string|null
     */
    public function getCauserId();

    /**
     * Get the creation timestamp.
     *
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface;
}
