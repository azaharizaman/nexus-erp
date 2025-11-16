<?php

namespace Nexus\Procurement\Rules;

use Illuminate\Support\Facades\Config;

class ToleranceRules
{
    /**
     * Default tolerance rules
     */
    const DEFAULT_RULES = [
        'price_variance_percent' => 5.0,
        'quantity_variance_percent' => 2.0,
        'total_value_variance_amount' => 100.0,
        'auto_approve_threshold_percent' => 2.0,
        'escalation_threshold_percent' => 10.0,
        'auto_reject_threshold_percent' => 15.0,
    ];

    /**
     * Get tolerance rules for a tenant
     *
     * @param string $tenantId
     * @return array
     */
    public static function getRules(string $tenantId): array
    {
        // Try to get tenant-specific rules from settings
        $tenantRules = Config::get("procurement.tenants.{$tenantId}.tolerance_rules", []);

        // Merge with default rules
        return array_merge(self::DEFAULT_RULES, $tenantRules);
    }

    /**
     * Check if variance is within tolerance
     *
     * @param float $actualValue
     * @param float $expectedValue
     * @param string $ruleType ('price', 'quantity', 'total')
     * @param string $tenantId
     * @return array
     */
    public static function checkVariance(float $actualValue, float $expectedValue, string $ruleType, string $tenantId): array
    {
        $rules = self::getRules($tenantId);

        $variance = abs($actualValue - $expectedValue);
        $variancePercent = $expectedValue > 0 ? ($variance / $expectedValue) * 100 : 0;

        $ruleKey = $ruleType . '_variance_percent';
        $threshold = $rules[$ruleKey] ?? $rules['price_variance_percent'];

        $result = [
            'variance_amount' => $variance,
            'variance_percent' => $variancePercent,
            'threshold_percent' => $threshold,
            'within_tolerance' => $variancePercent <= $threshold,
            'action' => 'approve', // default
        ];

        // Determine action based on variance
        if ($variancePercent <= $rules['auto_approve_threshold_percent']) {
            $result['action'] = 'auto_approve';
        } elseif ($variancePercent <= $threshold) {
            $result['action'] = 'approve';
        } elseif ($variancePercent <= $rules['escalation_threshold_percent']) {
            $result['action'] = 'escalate';
        } elseif ($variancePercent <= $rules['auto_reject_threshold_percent']) {
            $result['action'] = 'reject';
        } else {
            $result['action'] = 'cfo_approval';
        }

        return $result;
    }

    /**
     * Check price variance tolerance
     *
     * @param float $invoicePrice
     * @param float $poPrice
     * @param string $tenantId
     * @return array
     */
    public static function checkPriceVariance(float $invoicePrice, float $poPrice, string $tenantId): array
    {
        return self::checkVariance($invoicePrice, $poPrice, 'price', $tenantId);
    }

    /**
     * Check quantity variance tolerance
     *
     * @param float $receivedQuantity
     * @param float $orderedQuantity
     * @param string $tenantId
     * @return array
     */
    public static function checkQuantityVariance(float $receivedQuantity, float $orderedQuantity, string $tenantId): array
    {
        return self::checkVariance($receivedQuantity, $orderedQuantity, 'quantity', $tenantId);
    }

    /**
     * Check total value variance tolerance
     *
     * @param float $invoiceTotal
     * @param float $poTotal
     * @param string $tenantId
     * @return array
     */
    public static function checkTotalVariance(float $invoiceTotal, float $poTotal, string $tenantId): array
    {
        $rules = self::getRules($tenantId);
        $variance = abs($invoiceTotal - $poTotal);

        return [
            'variance_amount' => $variance,
            'threshold_amount' => $rules['total_value_variance_amount'],
            'within_tolerance' => $variance <= $rules['total_value_variance_amount'],
            'action' => $variance <= $rules['total_value_variance_amount'] ? 'approve' : 'escalate',
        ];
    }

    /**
     * Validate tolerance rule configuration
     *
     * @param array $rules
     * @return array Validation errors
     */
    public static function validateRules(array $rules): array
    {
        $errors = [];

        $requiredFields = [
            'price_variance_percent',
            'quantity_variance_percent',
            'total_value_variance_amount',
            'auto_approve_threshold_percent',
            'escalation_threshold_percent',
            'auto_reject_threshold_percent',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($rules[$field])) {
                $errors[] = "Missing required field: {$field}";
            } elseif (!is_numeric($rules[$field])) {
                $errors[] = "Field {$field} must be numeric";
            } elseif ($rules[$field] < 0) {
                $errors[] = "Field {$field} must be non-negative";
            }
        }

        // Validate logical consistency
        if (isset($rules['auto_approve_threshold_percent'], $rules['escalation_threshold_percent'])) {
            if ($rules['auto_approve_threshold_percent'] > $rules['escalation_threshold_percent']) {
                $errors[] = "Auto-approve threshold cannot be greater than escalation threshold";
            }
        }

        if (isset($rules['escalation_threshold_percent'], $rules['auto_reject_threshold_percent'])) {
            if ($rules['escalation_threshold_percent'] > $rules['auto_reject_threshold_percent']) {
                $errors[] = "Escalation threshold cannot be greater than auto-reject threshold";
            }
        }

        return $errors;
    }

    /**
     * Get tolerance rule recommendations based on industry and company size
     *
     * @param string $industry
     * @param string $companySize ('small', 'medium', 'large')
     * @return array
     */
    public static function getRecommendedRules(string $industry, string $companySize): array
    {
        $recommendations = [
            'small' => [
                'price_variance_percent' => 3.0,
                'quantity_variance_percent' => 1.0,
                'auto_approve_threshold_percent' => 1.0,
                'escalation_threshold_percent' => 5.0,
                'auto_reject_threshold_percent' => 10.0,
            ],
            'medium' => [
                'price_variance_percent' => 5.0,
                'quantity_variance_percent' => 2.0,
                'auto_approve_threshold_percent' => 2.0,
                'escalation_threshold_percent' => 8.0,
                'auto_reject_threshold_percent' => 12.0,
            ],
            'large' => [
                'price_variance_percent' => 7.0,
                'quantity_variance_percent' => 3.0,
                'auto_approve_threshold_percent' => 3.0,
                'escalation_threshold_percent' => 10.0,
                'auto_reject_threshold_percent' => 15.0,
            ],
        ];

        $baseRules = $recommendations[$companySize] ?? $recommendations['medium'];

        // Industry-specific adjustments
        $industryAdjustments = [
            'manufacturing' => ['price_variance_percent' => -1.0], // Stricter price control
            'retail' => ['quantity_variance_percent' => 1.0], // More flexible quantity
            'construction' => ['price_variance_percent' => 2.0, 'quantity_variance_percent' => 2.0], // More flexible
        ];

        if (isset($industryAdjustments[$industry])) {
            foreach ($industryAdjustments[$industry] as $field => $adjustment) {
                if (isset($baseRules[$field])) {
                    $baseRules[$field] = max(0, $baseRules[$field] + $adjustment);
                }
            }
        }

        return array_merge(self::DEFAULT_RULES, $baseRules);
    }
}