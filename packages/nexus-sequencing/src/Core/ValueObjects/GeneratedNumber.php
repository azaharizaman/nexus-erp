<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\ValueObjects;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Generated Number Value Object
 * 
 * Immutable representation of a successfully generated serial number,
 * including the final formatted value, counter used, and generation metadata.
 * 
 * This is a pure PHP Value Object with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\ValueObjects
 */
readonly class GeneratedNumber
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $value,
        public int $counter,
        public DateTimeInterface $generatedAt,
        public array $metadata = []
    ) {
        $this->validate();
    }

    /**
     * Validate generated number data
     * 
     * @throws \InvalidArgumentException If any parameter is invalid
     */
    private function validate(): void
    {
        if (empty($this->value)) {
            throw new \InvalidArgumentException('Generated value cannot be empty');
        }

        if ($this->counter < 1) {
            throw new \InvalidArgumentException('Counter must be greater than 0');
        }

        if (strlen($this->value) > 255) {
            throw new \InvalidArgumentException('Generated value cannot exceed 255 characters');
        }
    }

    /**
     * Create generated number with current timestamp
     * 
     * @param array<string, mixed> $metadata
     */
    public static function create(
        string $value,
        int $counter,
        array $metadata = []
    ): self {
        return new self(
            $value,
            $counter,
            new DateTimeImmutable(),
            $metadata
        );
    }

    /**
     * Create with specific timestamp
     * 
     * @param array<string, mixed> $metadata
     */
    public static function createAt(
        string $value,
        int $counter,
        DateTimeInterface $generatedAt,
        array $metadata = []
    ): self {
        return new self($value, $counter, $generatedAt, $metadata);
    }

    /**
     * Add metadata and return new instance
     */
    public function withMetadata(string $key, mixed $value): self
    {
        $metadata = $this->metadata;
        $metadata[$key] = $value;
        
        return new self(
            $this->value,
            $this->counter,
            $this->generatedAt,
            $metadata
        );
    }

    /**
     * Get metadata value
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if has specific metadata
     */
    public function hasMetadata(string $key): bool
    {
        return array_key_exists($key, $this->metadata);
    }

    /**
     * Get generation timestamp as formatted string
     */
    public function getFormattedTimestamp(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->generatedAt->format($format);
    }

    /**
     * Check if number was generated within specified seconds ago
     */
    public function isGeneratedWithin(int $seconds): bool
    {
        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->generatedAt->getTimestamp();
        
        return $diff <= $seconds;
    }

    /**
     * Get age of generation in seconds
     */
    public function getAgeInSeconds(): int
    {
        $now = new DateTimeImmutable();
        return $now->getTimestamp() - $this->generatedAt->getTimestamp();
    }

    /**
     * Convert to string (returns the generated value)
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Convert to array representation
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'counter' => $this->counter,
            'generated_at' => $this->generatedAt->format(DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to array with formatted timestamp
     * 
     * @return array<string, mixed>
     */
    public function toArrayWithFormattedTime(string $format = 'Y-m-d H:i:s'): array
    {
        $data = $this->toArray();
        $data['formatted_timestamp'] = $this->getFormattedTimestamp($format);
        
        return $data;
    }
}