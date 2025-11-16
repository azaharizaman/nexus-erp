<?php

declare(strict_types=1);

namespace Nexus\Workflow\Core\Services;

use Nexus\Workflow\Core\Contracts\WorkflowEngineContract;
use Nexus\Workflow\Core\DTOs\TransitionResult;
use Nexus\Workflow\Core\DTOs\WorkflowDefinition;
use Nexus\Workflow\Core\DTOs\WorkflowInstance;

/**
 * State Transition Service - framework-agnostic
 * 
 * Core workflow engine implementing state machine logic.
 * No framework dependencies - pure PHP logic.
 */
final class StateTransitionService implements WorkflowEngineContract
{
    public function canTransition(
        WorkflowInstance $instance,
        string $transitionName,
        array $context = []
    ): bool {
        $definition = $instance->getDefinition();
        
        // Check if transition exists
        if (!$definition->hasTransition($transitionName)) {
            return false;
        }
        
        $transition = $definition->getTransition($transitionName);
        $currentState = $instance->getCurrentState();
        
        // Check if current state is in the 'from' array
        $fromStates = $this->normalizeFromStates($transition['from'] ?? []);
        if (!in_array($currentState, $fromStates, true)) {
            return false;
        }
        
        // Evaluate guard condition if present
        if (isset($transition['guard']) && is_callable($transition['guard'])) {
            $guardContext = array_merge($context, [
                'instance' => $instance,
                'subject' => $instance->getSubject(),
                'data' => $instance->getData(),
            ]);
            
            return (bool) call_user_func($transition['guard'], $instance->getSubject(), $guardContext);
        }
        
        return true;
    }

    public function applyTransition(
        WorkflowInstance $instance,
        string $transitionName,
        array $context = []
    ): TransitionResult {
        $definition = $instance->getDefinition();
        
        // Validate transition exists
        if (!$definition->hasTransition($transitionName)) {
            throw new \InvalidArgumentException("Transition '{$transitionName}' does not exist in workflow definition");
        }
        
        // Check if transition is allowed
        if (!$this->canTransition($instance, $transitionName, $context)) {
            $currentState = $instance->getCurrentState();
            return TransitionResult::failure(
                fromState: $currentState,
                toState: $currentState,
                transitionName: $transitionName,
                message: "Transition '{$transitionName}' is not allowed from state '{$currentState}'"
            );
        }
        
        $transition = $definition->getTransition($transitionName);
        $fromState = $instance->getCurrentState();
        $toState = $transition['to'] ?? throw new \InvalidArgumentException("Transition '{$transitionName}' has no 'to' state");
        
        // Validate target state exists
        if (!$definition->hasState($toState)) {
            throw new \InvalidArgumentException("Target state '{$toState}' does not exist in workflow definition");
        }
        
        // Execute 'before' hook if present
        if (isset($transition['before']) && is_callable($transition['before'])) {
            $hookContext = array_merge($context, [
                'instance' => $instance,
                'subject' => $instance->getSubject(),
                'from' => $fromState,
                'to' => $toState,
            ]);
            
            call_user_func($transition['before'], $instance->getSubject(), $hookContext);
        }
        
        // Apply the transition
        $instance->setCurrentState($toState);
        
        // Add to history
        $instance->addToHistory(
            transitionName: $transitionName,
            fromState: $fromState,
            toState: $toState,
            metadata: $context
        );
        
        // Execute 'after' hook if present
        if (isset($transition['after']) && is_callable($transition['after'])) {
            $hookContext = array_merge($context, [
                'instance' => $instance,
                'subject' => $instance->getSubject(),
                'from' => $fromState,
                'to' => $toState,
            ]);
            
            call_user_func($transition['after'], $instance->getSubject(), $hookContext);
        }
        
        return TransitionResult::success(
            fromState: $fromState,
            toState: $toState,
            transitionName: $transitionName,
            metadata: $context
        );
    }

    public function getAvailableTransitions(
        WorkflowInstance $instance,
        array $context = []
    ): array {
        $definition = $instance->getDefinition();
        $currentState = $instance->getCurrentState();
        $availableTransitions = [];
        
        foreach ($definition->transitions as $transitionName => $transition) {
            if ($this->canTransition($instance, $transitionName, $context)) {
                $availableTransitions[] = $transitionName;
            }
        }
        
        return $availableTransitions;
    }

    public function validateDefinition(array $definition): bool
    {
        // Check required fields
        if (!isset($definition['initialState'])) {
            throw new \InvalidArgumentException('Workflow definition must have an initialState');
        }
        
        if (!isset($definition['states']) || !is_array($definition['states'])) {
            throw new \InvalidArgumentException('Workflow definition must have a states array');
        }
        
        if (!isset($definition['transitions']) || !is_array($definition['transitions'])) {
            throw new \InvalidArgumentException('Workflow definition must have a transitions array');
        }
        
        // Validate initialState exists in states
        if (!isset($definition['states'][$definition['initialState']])) {
            throw new \InvalidArgumentException("Initial state '{$definition['initialState']}' does not exist in states");
        }
        
        // Validate each transition
        foreach ($definition['transitions'] as $transitionName => $transition) {
            if (!isset($transition['from'])) {
                throw new \InvalidArgumentException("Transition '{$transitionName}' must have a 'from' field");
            }
            
            if (!isset($transition['to'])) {
                throw new \InvalidArgumentException("Transition '{$transitionName}' must have a 'to' field");
            }
            
            // Validate 'from' states exist
            $fromStates = $this->normalizeFromStates($transition['from']);
            foreach ($fromStates as $fromState) {
                if (!isset($definition['states'][$fromState])) {
                    throw new \InvalidArgumentException("Transition '{$transitionName}' references non-existent 'from' state: '{$fromState}'");
                }
            }
            
            // Validate 'to' state exists
            if (!isset($definition['states'][$transition['to']])) {
                throw new \InvalidArgumentException("Transition '{$transitionName}' references non-existent 'to' state: '{$transition['to']}'");
            }
        }
        
        return true;
    }

    public function getCurrentState(WorkflowInstance $instance): string
    {
        return $instance->getCurrentState();
    }

    public function stateExists(WorkflowInstance $instance, string $stateName): bool
    {
        return $instance->getDefinition()->hasState($stateName);
    }

    /**
     * Normalize 'from' states to always be an array
     *
     * @param mixed $from
     * @return array<string>
     */
    private function normalizeFromStates(mixed $from): array
    {
        if (is_string($from)) {
            return [$from];
        }
        
        if (is_array($from)) {
            return $from;
        }
        
        return [];
    }
}
