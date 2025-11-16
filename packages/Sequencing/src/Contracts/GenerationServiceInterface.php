<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Generation Service Interface
 *
 * Defines the core business logic for serial number generation.
 * This service orchestrates the generation process and is framework-agnostic.
 */
interface GenerationServiceInterface
{
    /**
     * Generate a new serial number atomically.
     *
     * This method performs the complete generation workflow:
     * 1. Lock the sequence counter
     * 2. Increment the counter
     * 3. Evaluate the pattern with the new counter
     * 4. Return the generated number
     *
     * @param  SequenceInterface  $sequence  The sequence configuration
     * @param  array<string, mixed>  $context  Additional context for pattern variables
     * @return string The generated serial number
     *
     * @throws \Nexus\Sequencing\Exceptions\GenerationException
     */
    public function generate(SequenceInterface $sequence, array $context = []): string;

    /**
     * Preview the next serial number without consuming the counter.
     *
     * Shows what the next generated number would be without actually
     * incrementing the counter.
     *
     * @param  SequenceInterface  $sequence  The sequence configuration
     * @param  array<string, mixed>  $context  Additional context for pattern variables
     * @return string The preview of the next serial number
     */
    public function preview(SequenceInterface $sequence, array $context = []): string;

    /**
     * Validate that a given string matches the sequence pattern.
     *
     * @param  SequenceInterface  $sequence  The sequence configuration
     * @param  string  $serialNumber  The serial number to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(SequenceInterface $sequence, string $serialNumber): bool;

    /**
     * Check if sequence needs reset based on reset period or limit.
     *
     * @param  SequenceInterface  $sequence  The sequence configuration
     * @return bool True if reset is needed, false otherwise
     */
    public function needsReset(SequenceInterface $sequence): bool;
}
