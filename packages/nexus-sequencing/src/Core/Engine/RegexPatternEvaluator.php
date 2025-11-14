<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Engine;

use Nexus\Sequencing\Core\Contracts\PatternEvaluatorInterface;
use Nexus\Sequencing\Core\Contracts\ValidationResult;
use Nexus\Sequencing\Core\Contracts\VariableRegistryInterface;
use Nexus\Sequencing\Core\Contracts\ConditionalProcessorInterface;
use Nexus\Sequencing\Core\ValueObjects\PatternTemplate;
use Nexus\Sequencing\Core\ValueObjects\CounterState;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use DateTimeInterface;

/**
 * Regex Pattern Evaluator
 * 
 * Default pattern evaluator using simple {VARIABLE} syntax.
 * Supports built-in variables like {YEAR}, {MONTH}, {COUNTER} and custom context variables.
 * 
 * This is a pure PHP implementation with zero external dependencies.
 * 
 * @package Nexus\Sequencing\Core\Engine
 */
class RegexPatternEvaluator implements PatternEvaluatorInterface
{
    /**
     * Built-in variables supported by this evaluator
     */
    private const BUILTIN_VARIABLES = [
        'YEAR',
        'MONTH', 
        'DAY',
        'COUNTER',
        'HOUR',
        'MINUTE',
        'SECOND',
        'WEEK',      // ISO week number (1-53)
        'QUARTER',   // Quarter number (1-4)
        'WEEK_YEAR', // ISO week-numbering year
        'DAY_OF_WEEK', // Day of week (1=Monday, 7=Sunday)
        'DAY_OF_YEAR', // Day of year (1-366)
    ];

    public function __construct(
        private readonly ?VariableRegistryInterface $customVariables = null,
        private readonly ?ConditionalProcessorInterface $conditionalProcessor = null
    ) {}

    public function getType(): string
    {
        return 'regex';
    }

    public function getDescription(): string
    {
        return 'Simple regex-based evaluator supporting {VARIABLE} syntax with built-in date/counter variables and custom context variables';
    }

    public function getSupportedVariables(): array
    {
        $variables = self::BUILTIN_VARIABLES;
        
        if ($this->customVariables !== null) {
            $variables = array_merge($variables, $this->customVariables->getNames());
        }
        
        return $variables;
    }

    public function supportsVariable(string $variable): bool
    {
        if (in_array($variable, self::BUILTIN_VARIABLES, true)) {
            return true;
        }
        
        if ($this->customVariables !== null && $this->customVariables->has($variable)) {
            return true;
        }
        
        return false;
    }

    public function evaluate(
        PatternTemplate $template,
        CounterState $counterState,
        GenerationContext $context
    ): string {
        $pattern = $template->pattern;

        // First, process conditional segments (Phase 2.3)
        if ($this->conditionalProcessor !== null && $this->conditionalProcessor->hasConditionals($pattern)) {
            $pattern = $this->conditionalProcessor->processConditionals($pattern, $context, $counterState->timestamp);
        }

        // Build variable map
        $variables = $this->buildVariableMap($counterState, $context);

        // Replace all variables in pattern
        return $this->replaceVariables($pattern, $variables);
    }

    public function preview(
        PatternTemplate $template,
        GenerationContext $context,
        int $previewCounter = 1
    ): string {
        // Create mock counter state for preview
        $mockState = new CounterState(
            counter: $previewCounter,
            timestamp: new \DateTimeImmutable()
        );

        return $this->evaluate($template, $mockState, $context);
    }

    public function validateSyntax(PatternTemplate $template): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validate conditional syntax (Phase 2.3)
        if ($this->conditionalProcessor !== null && $this->conditionalProcessor->hasConditionals($template->pattern)) {
            $conditionalResult = $this->conditionalProcessor->validateConditionalSyntax($template->pattern);
            $errors = array_merge($errors, $conditionalResult->getErrors());
            $warnings = array_merge($warnings, $conditionalResult->getWarnings());
        }

        // Extract all variables from pattern (after processing conditionals for accurate variable detection)
        $patternForVariableExtraction = $template->pattern;
        if ($this->conditionalProcessor !== null) {
            // Temporarily process conditionals to get the actual variables that will be used
            $patternForVariableExtraction = $this->conditionalProcessor->processConditionals(
                $template->pattern,
                new GenerationContext([]), // Empty context for validation
                new \DateTimeImmutable()
            );
        }

        $variables = PatternTemplate::from($patternForVariableExtraction)->extractVariables();

        // Check each variable
        foreach ($variables as $variable) {
            // Remove parameters (e.g., COUNTER:4 becomes COUNTER)
            $baseVariable = explode(':', $variable)[0];

            // Check if variable is supported (built-in or custom)
            if (!$this->supportsVariable($baseVariable)) {
                $warnings[] = sprintf(
                    'Variable "%s" is not a built-in or registered custom variable. It will be resolved from context.',
                    $baseVariable
                );
            }
        }

        // Check for malformed variable syntax
        if (preg_match('/\{[^}]*$/', $template->pattern)) {
            $errors[] = 'Pattern contains unclosed variable bracket';
        }

        if (preg_match('/^[^{]*\}/', $template->pattern)) {
            $errors[] = 'Pattern contains closing bracket without opening';
        }

        // Check for empty variables
        if (preg_match('/\{\}/', $template->pattern)) {
            $errors[] = 'Pattern contains empty variable placeholder';
        }

        // Check for nested variables
        if (preg_match('/\{[^}]*\{/', $template->pattern)) {
            $errors[] = 'Pattern contains nested variables which are not supported';
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings
        );
    }

    /**
     * Build map of all available variables
     * 
     * @param CounterState $counterState Current counter state
     * @param GenerationContext $context User-provided context
     * @return array<string, string> Variable name => value mapping
     */
    private function buildVariableMap(CounterState $counterState, GenerationContext $context): array
    {
        $timestamp = $counterState->timestamp;

        // Built-in variables
        $variables = [
            'YEAR' => $timestamp->format('Y'),
            'MONTH' => $timestamp->format('m'),
            'DAY' => $timestamp->format('d'),
            'HOUR' => $timestamp->format('H'),
            'MINUTE' => $timestamp->format('i'),
            'SECOND' => $timestamp->format('s'),
            'COUNTER' => (string) $counterState->counter,
            
            // Advanced date variables (Phase 2.3)
            'WEEK' => $timestamp->format('W'),           // ISO week number (01-53)
            'QUARTER' => $this->getQuarter($timestamp),  // Quarter number (1-4)
            'WEEK_YEAR' => $timestamp->format('o'),      // ISO week-numbering year
            'DAY_OF_WEEK' => $timestamp->format('N'),    // ISO day of week (1=Mon, 7=Sun)
            'DAY_OF_YEAR' => $timestamp->format('z'),    // Day of year (0-365)
        ];

        // Add context variables (custom variables from context)
        foreach ($context->all() as $key => $value) {
            $variables[strtoupper($key)] = (string) $value;
        }

        // Add registered custom variables
        if ($this->customVariables !== null) {
            foreach ($this->customVariables->all() as $customVariable) {
                $variableName = $customVariable->getName();
                
                try {
                    // Validate first
                    $validationResult = $customVariable->validate($context);
                    if (!$validationResult->isValid) {
                        continue; // Skip invalid variables
                    }
                    
                    // Resolve variable value
                    $variables[$variableName] = $customVariable->resolve($context, $timestamp);
                } catch (\Exception $e) {
                    // Skip variables that cannot be resolved
                    continue;
                }
            }
        }

        return $variables;
    }

    /**
     * Replace variables in pattern string
     * 
     * Supports both simple {VAR} and parameterized {VAR:param} syntax.
     * Also handles custom variables with parameters.
     * 
     * @param string $pattern The pattern string
     * @param array<string, string> $variables Variable map
     * @return string Pattern with variables replaced
     */
    private function replaceVariables(string $pattern, array $variables): string
    {
        $result = preg_replace_callback(
            '/\{([^}]+)\}/',
            function ($matches) use ($variables) {
                $variableSpec = $matches[1];
                
                // Handle parameterized variables (e.g., COUNTER:4, DEPARTMENT:UPPER)
                if (strpos($variableSpec, ':') !== false) {
                    [$variableName, $parameter] = explode(':', $variableSpec, 2);
                    
                    // Check if it's a built-in variable with parameter
                    if (isset($variables[$variableName])) {
                        return $this->formatVariable($variables[$variableName], $parameter);
                    }
                    
                    // Check if it's a custom variable with parameter
                    if ($this->customVariables !== null && $this->customVariables->has($variableName)) {
                        $customVariable = $this->customVariables->get($variableName);
                        
                        if ($customVariable && $customVariable->supportsParameters()) {
                            try {
                                $timestamp = new \DateTimeImmutable(); // Use current time for parameterized resolution
                                $context = new GenerationContext($variables); // Create context from current variables
                                
                                return $customVariable->resolveWithParameter($context, $timestamp, $parameter);
                            } catch (\Exception $e) {
                                // Fallback to base variable if parameter resolution fails
                                return $variables[$variableName] ?? $matches[0];
                            }
                        }
                    }
                } else {
                    // Simple variable
                    if (isset($variables[$variableSpec])) {
                        return $variables[$variableSpec];
                    }
                }

                // Variable not found - return as-is (this allows for optional variables)
                return $matches[0];
            },
            $pattern
        );

        // preg_replace_callback returns string|null, but our pattern should always match
        return $result ?? $pattern;
    }

    /**
     * Format variable with parameter
     * 
     * Supports numeric padding and advanced date formatting.
     * 
     * @param string $value The variable value
     * @param string $parameter The format parameter
     * @return string Formatted value
     */
    private function formatVariable(string $value, string $parameter): string
    {
        // For numeric parameters, treat as padding
        if (is_numeric($parameter)) {
            $padding = (int) $parameter;
            return str_pad($value, $padding, '0', STR_PAD_LEFT);
        }

        // Advanced date formatting parameters
        return match (strtoupper($parameter)) {
            // Quarter formatting
            'Q1', 'Q2', 'Q3', 'Q4' => 'Q' . $value,          // e.g., Q1, Q2, Q3, Q4
            'QTR' => 'QTR' . $value,                          // e.g., QTR1, QTR2
            'QUARTER' => 'Quarter ' . $value,                 // e.g., Quarter 1
            
            // Week formatting  
            'W' => 'W' . str_pad($value, 2, '0', STR_PAD_LEFT), // e.g., W01, W52
            'WEEK' => 'Week ' . $value,                       // e.g., Week 1, Week 52
            'WK' => 'WK' . str_pad($value, 2, '0', STR_PAD_LEFT), // e.g., WK01, WK52
            
            // Day formatting
            'ST', 'ND', 'RD', 'TH' => $this->addOrdinalSuffix($value), // e.g., 1st, 2nd, 3rd, 21st
            
            // Default: use as numeric padding if it's a number, otherwise return as-is
            default => is_numeric($parameter) ? str_pad($value, (int) $parameter, '0', STR_PAD_LEFT) : $value,
        };
    }

    /**
     * Get quarter number from date
     * 
     * @param DateTimeInterface $date The date to analyze
     * @return string Quarter number (1-4)
     */
    private function getQuarter(DateTimeInterface $date): string
    {
        $month = (int) $date->format('n'); // 1-12
        
        return match (true) {
            $month >= 1 && $month <= 3 => '1',
            $month >= 4 && $month <= 6 => '2',
            $month >= 7 && $month <= 9 => '3',
            $month >= 10 && $month <= 12 => '4',
            default => '1', // Fallback
        };
    }

    /**
     * Add ordinal suffix to number
     * 
     * @param string $number The number to add suffix to
     * @return string Number with ordinal suffix (1st, 2nd, 3rd, etc.)
     */
    private function addOrdinalSuffix(string $number): string
    {
        $num = (int) $number;
        
        // Special cases for 11th, 12th, 13th
        if ($num % 100 >= 11 && $num % 100 <= 13) {
            return $number . 'th';
        }
        
        return match ($num % 10) {
            1 => $number . 'st',
            2 => $number . 'nd', 
            3 => $number . 'rd',
            default => $number . 'th',
        };
    }
}