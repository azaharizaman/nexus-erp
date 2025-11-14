<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core\DTOs;

/**
 * Transition Result DTO - framework-agnostic
 * 
 * Represents the result of a transition attempt.
 * Contains success/failure status, messages, and resulting state.
 */
final readonly class TransitionResult
{
    /**
     * @param bool $success Whether the transition succeeded
     * @param string $fromState State before transition
     * @param string $toState State after transition
     * @param string $transitionName Name of the transition applied
     * @param string|null $message Optional result message
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public bool $success,
        public string $fromState,
        public string $toState,
        public string $transitionName,
        public ?string $message = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Create a successful transition result
     *
     * @param string $fromState
     * @param string $toState
     * @param string $transitionName
     * @param string|null $message
     * @param array<string, mixed> $metadata
     * @return self
     */
    public static function success(
        string $fromState,
        string $toState,
        string $transitionName,
        ?string $message = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            fromState: $fromState,
            toState: $toState,
            transitionName: $transitionName,
            message: $message ?? "Transition '{$transitionName}' from '{$fromState}' to '{$toState}' succeeded",
            metadata: $metadata,
        );
    }

    /**
     * Create a failed transition result
     *
     * @param string $fromState
     * @param string $toState
     * @param string $transitionName
     * @param string $message
     * @param array<string, mixed> $metadata
     * @return self
     */
    public static function failure(
        string $fromState,
        string $toState,
        string $transitionName,
        string $message,
        array $metadata = []
    ): self {
        return new self(
            success: false,
            fromState: $fromState,
            toState: $toState,
            transitionName: $transitionName,
            message: $message,
            metadata: $metadata,
        );
    }

    /**
     * Check if the transition was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the transition failed
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'from' => $this->fromState,
            'to' => $this->toState,
            'transition' => $this->transitionName,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
