<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Examples;

use Nexus\Sequencing\Actions\PreviewSerialNumberAction;
use Nexus\Sequencing\Models\Sequence;
use Nexus\Sequencing\Enums\ResetPeriod;

/**
 * Enhanced Preview Example
 * 
 * Demonstrates the new enhanced preview functionality with
 * remaining count calculations and reset information.
 */
class EnhancedPreviewExample
{
    /**
     * Example of using the enhanced preview action
     * 
     * @return array Example output showing preview structure
     */
    public static function demonstrateEnhancedPreview(): array
    {
        // Example output structure - what you would get from the enhanced preview
        return [
            'preview' => 'INV-2024-0012',
            'current_value' => 11,
            'next_value' => 12,
            'step_size' => 1,
            'reset_info' => [
                'type' => 'both',           // 'none', 'count', 'time', or 'both'
                'period' => 'monthly',      // 'never', 'daily', 'monthly', 'yearly'
                'limit' => 100,             // null if no count limit
                'remaining_count' => 89,    // null if no count limit
                'next_reset_date' => '2024-12-01T00:00:00+00:00', // null if no time reset
                'will_reset_next' => false, // true if next generation will trigger reset
            ]
        ];
    }

    /**
     * Example showing count-based reset warning
     * 
     * @return array Example when approaching count limit
     */
    public static function demonstrateCountLimitWarning(): array
    {
        return [
            'preview' => 'PO-2024-0099',
            'current_value' => 98,
            'next_value' => 99,
            'step_size' => 1,
            'reset_info' => [
                'type' => 'count',
                'period' => 'never',
                'limit' => 100,
                'remaining_count' => 2,      // Only 2 numbers left!
                'next_reset_date' => null,
                'will_reset_next' => false,
            ]
        ];
    }

    /**
     * Example showing imminent reset (next generation will reset)
     * 
     * @return array Example when next generation will trigger reset
     */
    public static function demonstrateImminentReset(): array
    {
        return [
            'preview' => 'SO-2024-0100',
            'current_value' => 99,
            'next_value' => 100,
            'step_size' => 1,
            'reset_info' => [
                'type' => 'count',
                'period' => 'never',
                'limit' => 100,
                'remaining_count' => 1,
                'next_reset_date' => null,
                'will_reset_next' => true,   // Next generation will reset to 1!
            ]
        ];
    }

    /**
     * Example with time-based reset only
     * 
     * @return array Example with monthly reset
     */
    public static function demonstrateTimeBasedReset(): array
    {
        return [
            'preview' => 'QT-2024-0045',
            'current_value' => 44,
            'next_value' => 45,
            'step_size' => 1,
            'reset_info' => [
                'type' => 'time',
                'period' => 'monthly',
                'limit' => null,
                'remaining_count' => null,   // No count limit
                'next_reset_date' => '2024-12-01T00:00:00+00:00',
                'will_reset_next' => false,
            ]
        ];
    }

    /**
     * Usage examples showing different preview options
     * 
     * @return array Different ways to use the action
     */
    public static function usageExamples(): array
    {
        return [
            'basic' => [
                'description' => 'Basic preview (backward compatible)',
                'call' => 'PreviewSerialNumberAction::run("tenant-123", "invoices")',
                'returns' => 'Array with preview and reset_info'
            ],
            
            'simple' => [
                'description' => 'Simple preview without reset info',
                'call' => 'PreviewSerialNumberAction::run("tenant-123", "invoices", [], false)',
                'returns' => 'Array with preview, current_value, next_value, step_size only'
            ],
            
            'with_context' => [
                'description' => 'Preview with custom context variables',
                'call' => 'PreviewSerialNumberAction::run("tenant-123", "po", ["department_code" => "IT"])',
                'returns' => 'Array with preview using department code in pattern'
            ],
        ];
    }
}