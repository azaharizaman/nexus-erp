<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Actions;

use Nexus\Sequencing\Core\Services\ValidationService;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Validate Pattern Action
 *
 * Provides Laravel integration for the Core ValidationService.
 * Validates sequence patterns for syntax, variables, and context compatibility.
 *
 * Usage:
 * ```php
 * $result = ValidatePatternAction::run([
 *     'pattern' => 'PO-{YEAR}-{COUNTER:4}',
 *     'context' => ['department' => 'SALES'],
 * ]);
 * ```
 */
class ValidatePatternAction
{
    use AsAction;

    /**
     * Create a new action instance.
     *
     * @param ValidationService $validationService The core validation service
     */
    public function __construct(
        private readonly ValidationService $validationService
    ) {}

    /**
     * Handle the action.
     *
     * @param array<string, mixed> $data Validation request data
     * @return array<string, mixed> Validation result
     *
     * Expected data format:
     * [
     *     'pattern' => string,              // Required: Pattern to validate
     *     'context' => array,               // Optional: Context variables
     *     'options' => array,               // Optional: Validation options
     * ]
     */
    public function handle(array $data): array
    {
        $pattern = $data['pattern'] ?? '';
        $contextData = $data['context'] ?? [];
        $options = $data['options'] ?? [];

        // Validate pattern syntax
        $patternResult = $this->validationService->validatePattern($pattern);
        
        $result = [
            'valid' => $patternResult->isValid,
            'pattern' => $pattern,
            'errors' => $patternResult->getErrors(),
            'warnings' => $patternResult->getWarnings(),
            'analysis' => $this->analyzePattern($pattern),
        ];

        // Validate context if provided
        if (!empty($contextData)) {
            try {
                $context = new GenerationContext($contextData);
                $contextResult = $this->validationService->validateContext($context, $pattern);
                
                $result['context_valid'] = $contextResult->isValid;
                $result['context_errors'] = $contextResult->getErrors();
                $result['context_warnings'] = $contextResult->getWarnings();
                
                // Overall validity includes both pattern and context
                $result['valid'] = $patternResult->isValid && $contextResult->isValid;
                $result['errors'] = array_merge($result['errors'], $contextResult->getErrors());
                $result['warnings'] = array_merge($result['warnings'], $contextResult->getWarnings());
                
            } catch (\Exception $e) {
                $result['context_valid'] = false;
                $result['context_errors'] = ['Invalid context data: ' . $e->getMessage()];
                $result['valid'] = false;
            }
        }

        // Additional options validation
        if (!empty($options)) {
            $configResult = $this->validationService->validateSequenceConfiguration(
                $pattern,
                isset($contextData) ? new GenerationContext($contextData) : null,
                $options
            );
            
            if (!$configResult->isValid) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $configResult->getErrors());
                $result['warnings'] = array_merge($result['warnings'], $configResult->getWarnings());
            }
        }

        // Generate regex pattern for the client
        if ($patternResult->isValid) {
            try {
                $result['regex'] = $this->validationService->generateRegexPattern($pattern);
            } catch (\Exception $e) {
                $result['warnings'][] = 'Could not generate regex pattern: ' . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Analyze pattern structure and provide insights.
     *
     * @param string $pattern The pattern to analyze
     * @return array<string, mixed> Pattern analysis
     */
    private function analyzePattern(string $pattern): array
    {
        try {
            $template = \Nexus\Sequencing\Core\ValueObjects\PatternTemplate::from($pattern);
            
            return [
                'length' => strlen($pattern),
                'complexity' => $template->getComplexity(),
                'variables' => $template->extractVariables(),
                'has_counter' => $template->hasCounter(),
                'is_static' => $template->isStatic(),
                'variable_count' => count($template->extractVariables()),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Pattern analysis failed: ' . $e->getMessage(),
                'length' => strlen($pattern),
            ];
        }
    }
}