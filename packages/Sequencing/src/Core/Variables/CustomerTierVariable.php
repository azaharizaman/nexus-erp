<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Core\Variables;

use Nexus\Sequencing\Core\Contracts\CustomVariableInterface;
use Nexus\Sequencing\Core\Contracts\ValidationResult;
use Nexus\Sequencing\Core\ValueObjects\GenerationContext;
use DateTimeInterface;

/**
 * Customer Tier Custom Variable
 * 
 * Resolves customer tier/classification from context for use in sequence patterns.
 * Example: QUOTE-{CUSTOMER_TIER}-{YEAR}-{COUNTER:4} -> QUOTE-VIP-2025-0001
 * 
 * @package Nexus\Sequencing\Core\Variables
 */
class CustomerTierVariable implements CustomVariableInterface
{
    private const VALID_TIERS = ['VIP', 'PREMIUM', 'STANDARD', 'BASIC', 'BRONZE', 'SILVER', 'GOLD', 'PLATINUM'];

    public function getName(): string
    {
        return 'CUSTOMER_TIER';
    }

    public function getDescription(): string
    {
        return 'Customer tier classification for tier-based sequence generation (VIP, PREMIUM, STANDARD, etc.)';
    }

    public function resolve(GenerationContext $context, DateTimeInterface $timestamp): string
    {
        $tier = $context->get('customer_tier') ?? 
               $context->get('tier') ?? 
               $context->get('customer_classification') ?? '';
        
        if (empty($tier)) {
            return 'STANDARD'; // Default tier if not specified
        }

        $normalizedTier = strtoupper(trim((string) $tier));
        
        // Map common variations
        $tierMap = [
            'V' => 'VIP',
            'P' => 'PREMIUM',
            'S' => 'STANDARD',
            'B' => 'BASIC',
            'HIGH' => 'PREMIUM',
            'MEDIUM' => 'STANDARD',
            'LOW' => 'BASIC',
            'NORMAL' => 'STANDARD',
            'DEFAULT' => 'STANDARD',
        ];

        return $tierMap[$normalizedTier] ?? $normalizedTier;
    }

    public function validate(GenerationContext $context): ValidationResult
    {
        $tier = $context->get('customer_tier') ?? 
               $context->get('tier') ?? 
               $context->get('customer_classification');
        
        // Tier is optional - defaults to STANDARD if not provided
        if ($tier === null) {
            return ValidationResult::success(['Customer tier not specified, will default to STANDARD']);
        }

        $normalizedTier = strtoupper(trim((string) $tier));
        
        if (empty($normalizedTier)) {
            return ValidationResult::success(['Empty tier specified, will default to STANDARD']);
        }

        // Allow mapping of common variations
        $tierMap = [
            'V' => 'VIP',
            'P' => 'PREMIUM', 
            'S' => 'STANDARD',
            'B' => 'BASIC',
            'HIGH' => 'PREMIUM',
            'MEDIUM' => 'STANDARD',
            'LOW' => 'BASIC',
            'NORMAL' => 'STANDARD',
            'DEFAULT' => 'STANDARD',
        ];

        $resolvedTier = $tierMap[$normalizedTier] ?? $normalizedTier;

        if (!in_array($resolvedTier, self::VALID_TIERS, true)) {
            return ValidationResult::failed([
                "Invalid customer tier '{$tier}'. Valid tiers: " . implode(', ', self::VALID_TIERS)
            ]);
        }

        return ValidationResult::success();
    }

    public function getRequiredContextKeys(): array
    {
        return []; // Tier is optional with default fallback
    }

    public function getOptionalContextKeys(): array
    {
        return ['customer_tier', 'tier', 'customer_classification'];
    }

    public function supportsParameters(): bool
    {
        return true;
    }

    public function getSupportedParameters(): array
    {
        return ['ABBREV', 'NUMERIC'];
    }

    public function resolveWithParameter(
        GenerationContext $context, 
        DateTimeInterface $timestamp, 
        string $parameter
    ): string {
        $baseValue = $this->resolve($context, $timestamp);
        
        return match (strtoupper($parameter)) {
            'ABBREV' => $this->getAbbreviation($baseValue),
            'NUMERIC' => $this->getNumericValue($baseValue),
            default => throw new \InvalidArgumentException(
                "Unsupported parameter '{$parameter}' for CUSTOMER_TIER variable. Supported: " . 
                implode(', ', $this->getSupportedParameters())
            ),
        };
    }

    private function getAbbreviation(string $tier): string
    {
        return match ($tier) {
            'VIP' => 'V',
            'PREMIUM' => 'P',
            'STANDARD' => 'S',
            'BASIC' => 'B',
            'BRONZE' => 'BZ',
            'SILVER' => 'SV',
            'GOLD' => 'G',
            'PLATINUM' => 'PT',
            default => substr($tier, 0, 2),
        };
    }

    private function getNumericValue(string $tier): string
    {
        return match ($tier) {
            'BASIC' => '1',
            'BRONZE' => '2',
            'STANDARD' => '3',
            'SILVER' => '4',
            'PREMIUM' => '5',
            'GOLD' => '6',
            'VIP' => '7',
            'PLATINUM' => '8',
            default => '3', // Default to STANDARD level
        };
    }
}