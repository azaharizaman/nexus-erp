<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core\DTOs;

/**
 * Workflow Definition DTO - framework-agnostic
 * 
 * Represents the static workflow schema (states, transitions, rules).
 * This is immutable once created and defines the "blueprint" for workflow instances.
 */
final readonly class WorkflowDefinition
{
    /**
     * @param string $id Unique workflow identifier
     * @param string $initialState Starting state for new instances
     * @param array<string, array<string, mixed>> $states State definitions
     * @param array<string, array<string, mixed>> $transitions Transition definitions
     * @param string|null $label Human-readable label
     * @param string|null $version Version identifier
     */
    public function __construct(
        public string $id,
        public string $initialState,
        public array $states,
        public array $transitions,
        public ?string $label = null,
        public ?string $version = null,
    ) {
    }

    /**
     * Create from array definition (e.g., from workflow() method)
     *
     * @param array<string, mixed> $definition
     * @return self
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            id: $definition['id'] ?? 'default',
            initialState: $definition['initialState'] ?? throw new \InvalidArgumentException('initialState is required'),
            states: $definition['states'] ?? throw new \InvalidArgumentException('states is required'),
            transitions: $definition['transitions'] ?? throw new \InvalidArgumentException('transitions is required'),
            label: $definition['label'] ?? null,
            version: $definition['version'] ?? null,
        );
    }

    /**
     * Get a specific state definition
     *
     * @param string $stateName
     * @return array<string, mixed>|null
     */
    public function getState(string $stateName): ?array
    {
        return $this->states[$stateName] ?? null;
    }

    /**
     * Get a specific transition definition
     *
     * @param string $transitionName
     * @return array<string, mixed>|null
     */
    public function getTransition(string $transitionName): ?array
    {
        return $this->transitions[$transitionName] ?? null;
    }

    /**
     * Get all transitions that originate from a specific state
     *
     * @param string $stateName
     * @return array<string, array<string, mixed>>
     */
    public function getTransitionsFromState(string $stateName): array
    {
        $result = [];
        
        foreach ($this->transitions as $name => $transition) {
            $from = $transition['from'] ?? [];
            
            // Handle both single state and array of states
            $fromStates = is_array($from) ? $from : [$from];
            
            if (in_array($stateName, $fromStates, true)) {
                $result[$name] = $transition;
            }
        }
        
        return $result;
    }

    /**
     * Check if a state exists in the definition
     *
     * @param string $stateName
     * @return bool
     */
    public function hasState(string $stateName): bool
    {
        return isset($this->states[$stateName]);
    }

    /**
     * Check if a transition exists in the definition
     *
     * @param string $transitionName
     * @return bool
     */
    public function hasTransition(string $transitionName): bool
    {
        return isset($this->transitions[$transitionName]);
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'initialState' => $this->initialState,
            'states' => $this->states,
            'transitions' => $this->transitions,
            'label' => $this->label,
            'version' => $this->version,
        ];
    }
}
