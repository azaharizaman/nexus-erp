<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Actions;

use Nexus\Sequencing\Core\Contracts\VariableRegistryInterface;
use Nexus\Sequencing\Core\Contracts\CustomVariableInterface;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Register Custom Variable Action
 * 
 * Laravel action for registering custom pattern variables that can be used
 * in sequence patterns. Provides validation and error handling for the
 * registration process.
 * 
 * @package Nexus\Sequencing\Actions
 */
class RegisterCustomVariableAction
{
    use AsAction;

    public function __construct(
        private readonly VariableRegistryInterface $registry
    ) {}

    /**
     * Register a custom variable for use in patterns.
     * 
     * @param CustomVariableInterface $variable The custom variable to register
     * @return array{success: bool, message: string, variable_name?: string}
     */
    public function handle(CustomVariableInterface $variable): array
    {
        try {
            $this->registry->register($variable);
            
            return [
                'success' => true,
                'message' => "Variable '{$variable->getName()}' registered successfully",
                'variable_name' => $variable->getName(),
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => "Failed to register variable: {$e->getMessage()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Unexpected error: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Register multiple custom variables at once.
     * 
     * @param CustomVariableInterface[] $variables Array of variables to register
     * @return array{success: bool, message: string, registered: string[], failed: array<string, string>}
     */
    public function registerMultiple(array $variables): array
    {
        $registered = [];
        $failed = [];
        
        foreach ($variables as $variable) {
            if (!$variable instanceof CustomVariableInterface) {
                $failed['invalid_type'] = 'Variable must implement CustomVariableInterface';
                continue;
            }
            
            $result = $this->handle($variable);
            
            if ($result['success']) {
                $registered[] = $result['variable_name'];
            } else {
                $failed[$variable->getName()] = $result['message'];
            }
        }
        
        $successCount = count($registered);
        $failureCount = count($failed);
        $totalCount = $successCount + $failureCount;
        
        return [
            'success' => $failureCount === 0,
            'message' => "Registered {$successCount}/{$totalCount} variables successfully",
            'registered' => $registered,
            'failed' => $failed,
        ];
    }

    /**
     * List all currently registered custom variables.
     * 
     * @return array{variables: array<string, array{name: string, description: string, required_context: string[], optional_context: string[], supports_parameters: bool}>}
     */
    public function listRegistered(): array
    {
        $variables = [];
        
        foreach ($this->registry->all() as $variable) {
            $variables[$variable->getName()] = [
                'name' => $variable->getName(),
                'description' => $variable->getDescription(),
                'required_context' => $variable->getRequiredContextKeys(),
                'optional_context' => $variable->getOptionalContextKeys(),
                'supports_parameters' => $variable->supportsParameters(),
                'supported_parameters' => $variable->supportsParameters() ? $variable->getSupportedParameters() : [],
            ];
        }
        
        return ['variables' => $variables];
    }

    /**
     * Remove a custom variable from the registry.
     * 
     * @param string $variableName Name of the variable to remove
     * @return array{success: bool, message: string}
     */
    public function unregister(string $variableName): array
    {
        $removed = $this->registry->remove($variableName);
        
        if ($removed) {
            return [
                'success' => true,
                'message' => "Variable '{$variableName}' removed successfully",
            ];
        } else {
            return [
                'success' => false,
                'message' => "Variable '{$variableName}' not found in registry",
            ];
        }
    }
}