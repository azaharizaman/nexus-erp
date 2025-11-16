<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Templates;

use Nexus\Sequencing\Core\Contracts\PatternTemplateInterface;
use Nexus\Sequencing\Core\Contracts\ValidationResult;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use Nexus\Sequencing\Core\ValueObjects\PatternTemplate;

/**
 * Abstract Pattern Template Base
 * 
 * Base class for pattern templates providing common functionality
 * for customization, validation, and preview generation.
 * 
 * @package Nexus\Sequencing\Core\Templates
 */
abstract class AbstractPatternTemplate implements PatternTemplateInterface
{
    /**
     * Generate a pattern customized for specific context.
     */
    public function customize(array $customizations = []): string
    {
        $pattern = $this->getBasePattern();

        // Apply customizations
        foreach ($customizations as $key => $value) {
            $pattern = $this->applySingleCustomization($pattern, $key, $value);
        }

        return $pattern;
    }

    /**
     * Preview the pattern with example data.
     */
    public function preview(array $context = [], int $counterValue = 1): string
    {
        // Use example context if none provided
        if (empty($context)) {
            $context = $this->getExampleContext();
        }

        // Create a basic preview by replacing common variables
        $pattern = $this->getBasePattern();
        $generationContext = new GenerationContext($context);
        
        // Simple preview implementation - replace basic variables
        $preview = $pattern;
        
        // Replace counter
        $preview = str_replace('{COUNTER}', str_pad((string) $counterValue, 4, '0', STR_PAD_LEFT), $preview);
        $preview = preg_replace_callback('/\{COUNTER:(\d+)\}/', function($matches) use ($counterValue) {
            return str_pad((string) $counterValue, (int) $matches[1], '0', STR_PAD_LEFT);
        }, $preview);

        // Replace date variables
        $now = new \DateTimeImmutable();
        $preview = str_replace('{YEAR}', $now->format('Y'), $preview);
        $preview = str_replace('{MONTH}', $now->format('m'), $preview);
        $preview = str_replace('{DAY}', $now->format('d'), $preview);
        $preview = str_replace('{QUARTER}', $this->getQuarter($now), $preview);
        $preview = str_replace('{WEEK}', $now->format('W'), $preview);

        // Replace context variables
        foreach ($context as $key => $value) {
            $preview = str_replace('{' . strtoupper($key) . '}', (string) $value, $preview);
        }

        // Handle simple conditionals (basic implementation)
        $preview = $this->processSimpleConditionals($preview, $context);

        return $preview;
    }

    /**
     * Validate template configuration.
     */
    public function validate(): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validate base pattern syntax
        try {
            $patternTemplate = PatternTemplate::from($this->getBasePattern());
            $variables = $patternTemplate->extractVariables();
            
            // Check for required variables
            if (!in_array('COUNTER', $variables)) {
                $warnings[] = 'Template does not include COUNTER variable, which may cause sequence conflicts';
            }

            // Validate required context availability
            $requiredContext = $this->getRequiredContext();
            foreach ($requiredContext as $required) {
                $found = false;
                foreach ($variables as $variable) {
                    if (strtoupper($required) === $variable || strtoupper($required) === explode(':', $variable)[0]) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $warnings[] = "Required context '{$required}' not found in pattern variables";
                }
            }

        } catch (\Exception $e) {
            $errors[] = "Invalid pattern syntax: {$e->getMessage()}";
        }

        // Validate example context
        $example = $this->getExampleContext();
        foreach ($this->getRequiredContext() as $required) {
            if (!array_key_exists($required, $example)) {
                $errors[] = "Example context missing required key: {$required}";
            }
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings
        );
    }

    /**
     * Get the template category.
     */
    public function getCategory(): string
    {
        return 'General';
    }

    /**
     * Get template tags for discovery.
     */
    public function getTags(): array
    {
        return [];
    }

    /**
     * Check if this template extends another template.
     */
    public function getParentTemplateId(): ?string
    {
        return null;
    }

    /**
     * Apply a single customization to the pattern.
     */
    protected function applySingleCustomization(string $pattern, string $key, mixed $value): string
    {
        return match ($key) {
            'prefix' => $value . '-' . $pattern,
            'suffix' => $pattern . '-' . $value,
            'separator' => str_replace('-', (string) $value, $pattern),
            'counter_padding' => preg_replace('/\{COUNTER(?::(\d+))?\}/', '{COUNTER:' . $value . '}', $pattern),
            default => $pattern,
        };
    }

    /**
     * Get quarter from date.
     */
    protected function getQuarter(\DateTimeInterface $date): string
    {
        $month = (int) $date->format('n');
        
        return match (true) {
            $month >= 1 && $month <= 3 => '1',
            $month >= 4 && $month <= 6 => '2', 
            $month >= 7 && $month <= 9 => '3',
            $month >= 10 && $month <= 12 => '4',
            default => '1',
        };
    }

    /**
     * Process simple conditionals for preview.
     */
    protected function processSimpleConditionals(string $pattern, array $context): string
    {
        // Simple conditional processing for preview: {?key=value?true:false}
        return preg_replace_callback(
            '/\{\?([^=]+)=([^?]+)\?([^:}]+)(?::([^}]+))?\}/',
            function ($matches) use ($context) {
                $key = trim($matches[1]);
                $expectedValue = trim($matches[2]);
                $trueValue = trim($matches[3]);
                $falseValue = isset($matches[4]) ? trim($matches[4]) : '';

                $contextValue = $context[$key] ?? $context[strtolower($key)] ?? '';
                $condition = strtoupper((string) $contextValue) === strtoupper($expectedValue);

                return $condition ? $trueValue : $falseValue;
            },
            $pattern
        );
    }
}