<?php

declare(strict_types=1);

use Nexus\Sequencing\Core\ValueObjects\PatternTemplate;

describe('PatternTemplate Value Object', function () {
    it('creates valid pattern template', function () {
        $template = new PatternTemplate('PO-{YEAR}-{COUNTER:4}');
        
        expect($template->pattern)->toBe('PO-{YEAR}-{COUNTER:4}');
    });

    it('validates pattern is not empty', function () {
        expect(fn () => new PatternTemplate(''))
            ->toThrow(InvalidArgumentException::class, 'Pattern cannot be empty');
    });

    it('validates pattern length', function () {
        $longPattern = str_repeat('a', 256);
        expect(fn () => new PatternTemplate($longPattern))
            ->toThrow(InvalidArgumentException::class, 'Pattern cannot exceed 255 characters');
    });

    it('extracts variables from pattern', function () {
        $template = new PatternTemplate('PO-{YEAR}-{MONTH}-{COUNTER:4}-{DEPARTMENT}');
        
        $variables = $template->extractVariables();
        
        expect($variables)->toContain('YEAR');
        expect($variables)->toContain('MONTH');
        expect($variables)->toContain('COUNTER');
        expect($variables)->toContain('DEPARTMENT');
        expect(count($variables))->toBe(4);
    });

    it('handles variables with parameters', function () {
        $template = new PatternTemplate('{COUNTER:6}');
        
        $variables = $template->extractVariables();
        
        expect($variables)->toContain('COUNTER');
        expect(count($variables))->toBe(1);
    });

    it('checks if pattern has specific variable', function () {
        $template = new PatternTemplate('PO-{YEAR}-{COUNTER}');
        
        expect($template->hasVariable('YEAR'))->toBeTrue();
        expect($template->hasVariable('COUNTER'))->toBeTrue();
        expect($template->hasVariable('MONTH'))->toBeFalse();
    });

    it('checks if pattern has counter', function () {
        $withCounter = new PatternTemplate('PO-{COUNTER}');
        $withoutCounter = new PatternTemplate('STATIC');
        
        expect($withCounter->hasCounter())->toBeTrue();
        expect($withoutCounter->hasCounter())->toBeFalse();
    });

    it('identifies static patterns', function () {
        $static = new PatternTemplate('STATIC-STRING');
        $dynamic = new PatternTemplate('PO-{COUNTER}');
        
        expect($static->isStatic())->toBeTrue();
        expect($dynamic->isStatic())->toBeFalse();
    });

    it('calculates complexity score', function () {
        $simple = new PatternTemplate('PO');
        $complex = new PatternTemplate('PO-{YEAR}-{MONTH}-{DAY}-{COUNTER}-{DEPARTMENT}-{PREFIX}');
        
        $simpleScore = $simple->getComplexity();
        $complexScore = $complex->getComplexity();
        
        expect($complexScore)->toBeGreaterThan($simpleScore);
        expect($simpleScore)->toBeGreaterThanOrEqual(0);
        expect($complexScore)->toBeLessThanOrEqual(100);
    });

    it('converts to array representation', function () {
        $template = new PatternTemplate('PO-{YEAR}-{COUNTER}');
        
        $array = $template->toArray();
        
        expect($array)->toHaveKey('pattern');
        expect($array)->toHaveKey('variables');
        expect($array)->toHaveKey('has_counter');
        expect($array)->toHaveKey('is_static');
        expect($array)->toHaveKey('complexity');
        
        expect($array['pattern'])->toBe('PO-{YEAR}-{COUNTER}');
        expect($array['has_counter'])->toBeTrue();
        expect($array['is_static'])->toBeFalse();
    });
});