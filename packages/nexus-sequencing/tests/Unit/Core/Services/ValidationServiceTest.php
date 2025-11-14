<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Tests\Unit\Core\Services;

use Nexus\Sequencing\Core\Services\ValidationService;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use PHPUnit\Framework\TestCase;

/**
 * ValidationService Tests
 * 
 * Tests for the Phase 2.2 Core ValidationService ensuring
 * comprehensive pattern validation functionality.
 */
class ValidationServiceTest extends TestCase
{
    private ValidationService $service;

    protected function setUp(): void
    {
        $this->service = new ValidationService();
    }

    public function test_valid_pattern_passes_validation(): void
    {
        $pattern = 'INV-{YEAR}-{COUNTER:4}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->getErrors());
    }

    public function test_empty_pattern_fails_validation(): void
    {
        $pattern = '';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertFalse($result->isValid);
        $this->assertContains('Pattern cannot be empty', $result->getErrors());
    }

    public function test_pattern_with_invalid_variable_name_fails(): void
    {
        $pattern = 'INV-{invalid_lowercase}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Invalid variable name', implode(' ', $result->getErrors()));
    }

    public function test_pattern_with_duplicate_variables_fails(): void
    {
        $pattern = '{COUNTER:4}-{COUNTER:6}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertFalse($result->isValid);
        $this->assertContains('Duplicate variable: COUNTER', $result->getErrors());
    }

    public function test_pattern_with_invalid_counter_padding_fails(): void
    {
        $pattern = 'INV-{COUNTER:25}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('COUNTER parameter must be between 1 and 20', implode(' ', $result->getErrors()));
    }

    public function test_pattern_without_counter_shows_warning(): void
    {
        $pattern = 'STATIC-TEXT-ONLY';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertTrue($result->isValid); // Valid but with warning
        $this->assertStringContainsString('does not contain {COUNTER} variable', implode(' ', $result->getWarnings()));
    }

    public function test_unbalanced_brackets_fail(): void
    {
        $pattern = 'INV-{YEAR-{COUNTER:4}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertFalse($result->isValid);
        $this->assertContains('Unbalanced brackets in pattern', $result->getErrors());
    }

    public function test_context_validation_with_missing_variables(): void
    {
        $pattern = 'PO-{DEPARTMENT}-{COUNTER:3}';
        $context = new GenerationContext([
            'COUNTER' => 123,
            // Missing 'DEPARTMENT'
        ]);
        
        $result = $this->service->validateContext($context, $pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('DEPARTMENT', implode(' ', $result->getErrors()));
    }

    public function test_context_validation_passes_with_complete_context(): void
    {
        $pattern = 'SO-{REGION}-{COUNTER:4}';
        $context = new GenerationContext([
            'REGION' => 'US-WEST',
            'COUNTER' => 42,
        ]);
        
        $result = $this->service->validateContext($context, $pattern);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->getErrors());
    }

    public function test_context_with_unused_variables_shows_warning(): void
    {
        $pattern = 'QT-{COUNTER:4}';
        $context = new GenerationContext([
            'COUNTER' => 1,
            'UNUSED_VAR' => 'value',
        ]);
        
        $result = $this->service->validateContext($context, $pattern);

        $this->assertTrue($result->isValid);
        $this->assertStringContainsString('Unused context variables', implode(' ', $result->getWarnings()));
    }

    public function test_context_with_invalid_value_types(): void
    {
        $pattern = 'INV-{COUNTER:4}';
        $context = new GenerationContext([
            'COUNTER' => 1,
            'INVALID' => ['array', 'not', 'allowed'],
        ]);
        
        $result = $this->service->validateContext($context, $pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('must be scalar', implode(' ', $result->getErrors()));
    }

    public function test_regex_pattern_generation(): void
    {
        $pattern = 'QT-{YEAR}-{COUNTER:4}';
        
        $regexPattern = $this->service->generateRegexPattern($pattern);

        $this->assertStringContainsString('QT-', $regexPattern);
        $this->assertStringContainsString('(\d{4})', $regexPattern); // Year pattern
        $this->assertStringContainsString('(\d+)', $regexPattern);   // Counter pattern
    }

    public function test_pattern_with_special_characters(): void
    {
        $pattern = 'INV/2024.{COUNTER:5}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertTrue($result->isValid);
        
        $regexPattern = $this->service->generateRegexPattern($pattern);
        // Ensure special characters are properly escaped in regex
        $this->assertStringContainsString('INV\/2024\.', $regexPattern);
    }

    public function test_complex_pattern_validation(): void
    {
        $pattern = '{PREFIX}-{YEAR}{MONTH}-{DEPARTMENT}-{COUNTER:6}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertTrue($result->isValid);
    }

    public function test_sequence_configuration_validation(): void
    {
        $pattern = 'TEST-{COUNTER:4}';
        $context = new GenerationContext(['COUNTER' => 1]);
        
        $result = $this->service->validateSequenceConfiguration($pattern, $context);

        $this->assertTrue($result->isValid);
    }

    public function test_sequence_configuration_with_options(): void
    {
        $pattern = 'VERY-LONG-PATTERN-WITH-LOTS-OF-TEXT-{COUNTER:4}';
        $context = new GenerationContext(['COUNTER' => 1]);
        $options = ['max_length' => 20];
        
        $result = $this->service->validateSequenceConfiguration($pattern, $context, $options);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('exceeds maximum length', implode(' ', $result->getErrors()));
    }

    public function test_forbidden_variables_validation(): void
    {
        $pattern = 'TEST-{FORBIDDEN_VAR}-{COUNTER:4}';
        $context = new GenerationContext(['FORBIDDEN_VAR' => 'test', 'COUNTER' => 1]);
        $options = ['forbidden_variables' => ['FORBIDDEN_VAR']];
        
        $result = $this->service->validateSequenceConfiguration($pattern, $context, $options);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Forbidden variables detected', implode(' ', $result->getErrors()));
    }

    public function test_performance_with_complex_patterns(): void
    {
        $pattern = '{PREFIX}-{YEAR}{MONTH}{DAY}-{DEPARTMENT}-{REGION}-{COUNTER:10}-{SUFFIX}';
        
        $start = microtime(true);
        
        $result = $this->service->validatePattern($pattern);
        
        $duration = microtime(true) - $start;
        
        $this->assertTrue($result->isValid);
        $this->assertLessThan(0.1, $duration, 'Validation should complete within 100ms');
    }

    public function test_built_in_variables_do_not_require_context(): void
    {
        $pattern = 'RPT-{YEAR}-{MONTH}-{DAY}-{COUNTER:4}';
        $context = new GenerationContext(['COUNTER' => 1]);
        
        $result = $this->service->validateContext($context, $pattern);

        $this->assertTrue($result->isValid);
    }

    public function test_counter_with_invalid_parameter_type(): void
    {
        $pattern = 'INV-{COUNTER:abc}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('COUNTER parameter must be a number', implode(' ', $result->getErrors()));
    }

    public function test_date_variables_with_invalid_parameters(): void
    {
        $pattern = 'INV-{YEAR:4}-{COUNTER:3}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Date/time variables do not accept parameters', implode(' ', $result->getErrors()));
    }

    public function test_pattern_with_unicode_characters(): void
    {
        $pattern = '单据-{YEAR}-{COUNTER:4}';
        
        $result = $this->service->validatePattern($pattern);

        $this->assertTrue($result->isValid);
    }

    public function test_context_with_long_string_values(): void
    {
        $pattern = 'TEST-{LONG_VAR}-{COUNTER:4}';
        $longValue = str_repeat('A', 101); // Exceeds 100 char limit
        $context = new GenerationContext([
            'LONG_VAR' => $longValue,
            'COUNTER' => 1,
        ]);
        
        $result = $this->service->validateContext($context, $pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('too long', implode(' ', $result->getErrors()));
    }

    public function test_context_with_curly_braces_in_values(): void
    {
        $pattern = 'TEST-{VAR}-{COUNTER:4}';
        $context = new GenerationContext([
            'VAR' => 'value{with}braces',
            'COUNTER' => 1,
        ]);
        
        $result = $this->service->validateContext($context, $pattern);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('cannot contain curly braces', implode(' ', $result->getErrors()));
    }
}