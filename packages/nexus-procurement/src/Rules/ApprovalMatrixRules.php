<?php

namespace Nexus\Procurement\Rules;

use Illuminate\Support\Facades\Config;

class ApprovalMatrixRules
{
    /**
     * Default approval matrix rules
     */
    const DEFAULT_MATRIX = [
        [
            'name' => 'Standard Approval Matrix',
            'active' => true,
            'rules' => [
                [
                    'condition' => 'total_amount <= 5000',
                    'approvers' => ['department_manager'],
                    'escalation_days' => 3,
                ],
                [
                    'condition' => 'total_amount > 5000 AND total_amount <= 50000',
                    'approvers' => ['department_manager', 'division_director'],
                    'escalation_days' => 5,
                ],
                [
                    'condition' => 'total_amount > 50000',
                    'approvers' => ['department_manager', 'division_director', 'cfo'],
                    'escalation_days' => 7,
                ],
            ],
        ],
    ];

    /**
     * Get approval matrix for a tenant
     *
     * @param string $tenantId
     * @return array
     */
    public static function getMatrix(string $tenantId): array
    {
        // Try to get tenant-specific matrix from settings
        $tenantMatrix = Config::get("procurement.tenants.{$tenantId}.approval_matrix", []);

        if (!empty($tenantMatrix)) {
            return $tenantMatrix;
        }

        // Fall back to default matrix
        return self::DEFAULT_MATRIX;
    }

    /**
     * Evaluate approval requirements for a requisition
     *
     * @param array $requisitionData
     * @param string $tenantId
     * @return array
     */
    public static function evaluateApprovalRequirements(array $requisitionData, string $tenantId): array
    {
        $matrix = self::getMatrix($tenantId);
        $totalAmount = $requisitionData['total_amount'] ?? 0;
        $department = $requisitionData['department'] ?? '';
        $glAccount = $requisitionData['gl_account'] ?? '';
        $category = $requisitionData['category'] ?? '';

        $requirements = [
            'required_approvers' => [],
            'escalation_days' => 3,
            'parallel_approval' => false,
            'conditional_approvers' => [],
        ];

        foreach ($matrix as $matrixItem) {
            if (!$matrixItem['active']) {
                continue;
            }

            foreach ($matrixItem['rules'] as $rule) {
                if (self::evaluateCondition($rule['condition'], [
                    'total_amount' => $totalAmount,
                    'department' => $department,
                    'gl_account' => $glAccount,
                    'category' => $category,
                ])) {
                    $requirements['required_approvers'] = array_merge(
                        $requirements['required_approvers'],
                        $rule['approvers']
                    );
                    $requirements['escalation_days'] = max(
                        $requirements['escalation_days'],
                        $rule['escalation_days'] ?? 3
                    );

                    // Check for parallel approval indicators
                    if (in_array('AND', $rule['approvers']) || count($rule['approvers']) > 1) {
                        $requirements['parallel_approval'] = true;
                    }
                }
            }
        }

        // Remove duplicates and invalid approvers
        $requirements['required_approvers'] = array_unique(
            array_filter($requirements['required_approvers'], function ($approver) {
                return $approver !== 'AND' && !empty($approver);
            })
        );

        return $requirements;
    }

    /**
     * Evaluate a condition expression
     *
     * @param string $condition
     * @param array $variables
     * @return bool
     */
    protected static function evaluateCondition(string $condition, array $variables): bool
    {
        // Safe expression evaluation without eval()
        // Supports: <=, >=, <, >, ==, !=, AND, OR
        
        try {
            // Replace variable names with their values
            $expression = $condition;
            
            // Sort keys by length (longest first) to avoid partial replacements
            $sortedKeys = array_keys($variables);
            usort($sortedKeys, fn($a, $b) => strlen($b) - strlen($a));
            
            foreach ($sortedKeys as $key) {
                $value = $variables[$key];
                // Use word boundary to avoid partial replacements
                if (is_string($value)) {
                    // Escape single quotes to prevent injection
                    $escapedValue = str_replace("'", "\\'", $value);
                    $expression = preg_replace('/\b' . preg_quote($key, '/') . '\b/', "'{$escapedValue}'", $expression);
                } else {
                    $expression = preg_replace('/\b' . preg_quote($key, '/') . '\b/', (string)$value, $expression);
                }
            }
            
            // Respect operator precedence: AND before OR
            $expression = trim($expression);
            
            // Split by OR first, then evaluate each part as a group of ANDs
            $orParts = preg_split('/\s+OR\s+/i', $expression);
            foreach ($orParts as $orPart) {
                $andParts = preg_split('/\s+AND\s+/i', $orPart);
                $allAndTrue = true;
                foreach ($andParts as $andPart) {
                    if (!self::evaluateSingleCondition(trim($andPart))) {
                        $allAndTrue = false;
                        break;
                    }
                }
                if ($allAndTrue) {
                    return true;
                }
            }
            return false;
            
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * Evaluate a single comparison condition
     *
     * @param string $condition
     * @return bool
     */
    protected static function evaluateSingleCondition(string $condition): bool
    {
        // Parse comparison operators: <=, >=, ==, !=, <, >
        
        // Try <= operator
        if (preg_match('/^(.+)\s*<=\s*(.+)$/', $condition, $matches)) {
            return self::compareValues(trim($matches[1]), trim($matches[2]), '<=');
        }
        
        // Try >= operator
        if (preg_match('/^(.+)\s*>=\s*(.+)$/', $condition, $matches)) {
            return self::compareValues(trim($matches[1]), trim($matches[2]), '>=');
        }
        
        // Try == operator
        if (preg_match('/^(.+)\s*==\s*(.+)$/', $condition, $matches)) {
            return self::compareValues(trim($matches[1]), trim($matches[2]), '==');
        }
        
        // Try != operator
        if (preg_match('/^(.+)\s*!=\s*(.+)$/', $condition, $matches)) {
            return self::compareValues(trim($matches[1]), trim($matches[2]), '!=');
        }
        
        // Try < operator (must come after <= to avoid matching < first)
        if (preg_match('/^(.+)\s*<\s*(.+)$/', $condition, $matches)) {
            return self::compareValues(trim($matches[1]), trim($matches[2]), '<');
        }
        
        // Try > operator (must come after >= to avoid matching > first)
        if (preg_match('/^(.+)\s*>\s*(.+)$/', $condition, $matches)) {
            return self::compareValues(trim($matches[1]), trim($matches[2]), '>');
        }
        
        return false;
    }
    
    /**
     * Compare two values using the specified operator
     *
     * @param string $left
     * @param string $right
     * @param string $operator
     * @return bool
     */
    protected static function compareValues(string $left, string $right, string $operator): bool
    {
        // Remove quotes from string values
        $left = trim($left, "'\"");
        $right = trim($right, "'\"");
        
        // Try numeric comparison first
        if (is_numeric($left) && is_numeric($right)) {
            $left = (float)$left;
            $right = (float)$right;
            
            return match($operator) {
                '<=' => $left <= $right,
                '>=' => $left >= $right,
                '<' => $left < $right,
                '>' => $left > $right,
                '==' => $left == $right,
                '!=' => $left != $right,
                default => false,
            };
        }
        
        // String comparison
        return match($operator) {
            '<=' => $left <= $right,
            '>=' => $left >= $right,
            '<' => $left < $right,
            '>' => $left > $right,
            '==' => $left == $right,
            '!=' => $left != $right,
            default => false,
        };
    }

    /**
     * Check if a user can approve a requisition
     *
     * @param string $userId
     * @param array $requisitionData
     * @param string $tenantId
     * @return array
     */
    public static function canApprove(string $userId, array $requisitionData, string $tenantId): array
    {
        $requirements = self::evaluateApprovalRequirements($requisitionData, $tenantId);

        $userRoles = self::getUserRoles($userId, $tenantId);
        $canApprove = false;
        $reason = 'User does not have required approval role';

        foreach ($requirements['required_approvers'] as $requiredApprover) {
            if (in_array($requiredApprover, $userRoles)) {
                $canApprove = true;
                $reason = 'User has required approval role';
                break;
            }
        }

        return [
            'can_approve' => $canApprove,
            'reason' => $reason,
            'required_roles' => $requirements['required_approvers'],
            'user_roles' => $userRoles,
        ];
    }

    /**
     * Get user roles (mock implementation - integrate with actual role system)
     *
     * @param string $userId
     * @param string $tenantId
     * @return array
     */
    protected static function getUserRoles(string $userId, string $tenantId): array
    {
        // This should integrate with the actual role/permission system
        // For now, return mock roles based on user ID patterns
        $roles = [];

        if (str_contains($userId, 'manager')) {
            $roles[] = 'department_manager';
        }

        if (str_contains($userId, 'director')) {
            $roles[] = 'division_director';
        }

        if (str_contains($userId, 'cfo') || str_contains($userId, 'finance')) {
            $roles[] = 'cfo';
        }

        // Default role for all users
        $roles[] = 'employee';

        return array_unique($roles);
    }

    /**
     * Validate approval matrix configuration
     *
     * @param array $matrix
     * @return array Validation errors
     */
    public static function validateMatrix(array $matrix): array
    {
        $errors = [];

        if (!is_array($matrix)) {
            $errors[] = 'Approval matrix must be an array';
            return $errors;
        }

        foreach ($matrix as $index => $matrixItem) {
            if (!isset($matrixItem['name'])) {
                $errors[] = "Matrix item {$index} missing 'name' field";
            }

            if (!isset($matrixItem['rules']) || !is_array($matrixItem['rules'])) {
                $errors[] = "Matrix item {$index} missing 'rules' array";
                continue;
            }

            foreach ($matrixItem['rules'] as $ruleIndex => $rule) {
                if (!isset($rule['condition'])) {
                    $errors[] = "Rule {$ruleIndex} in matrix item {$index} missing 'condition'";
                }

                if (!isset($rule['approvers']) || !is_array($rule['approvers'])) {
                    $errors[] = "Rule {$ruleIndex} in matrix item {$index} missing 'approvers' array";
                }
            }
        }

        return $errors;
    }

    /**
     * Get approval matrix templates for different industries
     *
     * @param string $industry
     * @return array
     */
    public static function getIndustryTemplate(string $industry): array
    {
        $templates = [
            'technology' => [
                [
                    'name' => 'Tech Industry Approval Matrix',
                    'active' => true,
                    'rules' => [
                        [
                            'condition' => 'total_amount <= 10000',
                            'approvers' => ['department_manager'],
                            'escalation_days' => 2,
                        ],
                        [
                            'condition' => 'total_amount > 10000 AND total_amount <= 100000',
                            'approvers' => ['department_manager', 'division_director'],
                            'escalation_days' => 3,
                        ],
                        [
                            'condition' => 'total_amount > 100000',
                            'approvers' => ['department_manager', 'division_director', 'cfo'],
                            'escalation_days' => 5,
                        ],
                    ],
                ],
            ],
            'manufacturing' => [
                [
                    'name' => 'Manufacturing Approval Matrix',
                    'active' => true,
                    'rules' => [
                        [
                            'condition' => 'total_amount <= 25000',
                            'approvers' => ['department_manager'],
                            'escalation_days' => 5,
                        ],
                        [
                            'condition' => 'total_amount > 25000 AND total_amount <= 250000',
                            'approvers' => ['department_manager', 'division_director'],
                            'escalation_days' => 7,
                        ],
                        [
                            'condition' => 'total_amount > 250000',
                            'approvers' => ['department_manager', 'division_director', 'cfo'],
                            'escalation_days' => 10,
                        ],
                    ],
                ],
            ],
            'healthcare' => [
                [
                    'name' => 'Healthcare Approval Matrix',
                    'active' => true,
                    'rules' => [
                        [
                            'condition' => 'total_amount <= 5000',
                            'approvers' => ['department_manager'],
                            'escalation_days' => 3,
                        ],
                        [
                            'condition' => 'total_amount > 5000 AND total_amount <= 25000',
                            'approvers' => ['department_manager', 'division_director'],
                            'escalation_days' => 5,
                        ],
                        [
                            'condition' => 'total_amount > 25000',
                            'approvers' => ['department_manager', 'division_director', 'cfo', 'compliance_officer'],
                            'escalation_days' => 7,
                        ],
                    ],
                ],
            ],
        ];

        return $templates[$industry] ?? self::DEFAULT_MATRIX;
    }

    /**
     * Check for separation of duties violations
     *
     * @param string $requesterId
     * @param array $requisitionData
     * @param string $tenantId
     * @return array
     */
    public static function checkSeparationOfDuties(string $requesterId, array $requisitionData, string $tenantId): array
    {
        $violations = [];

        $requirements = self::evaluateApprovalRequirements($requisitionData, $tenantId);

        // Check if requester is in the approval chain
        if (in_array($requesterId, $requirements['required_approvers'])) {
            $violations[] = 'Requester cannot approve their own requisition';
        }

        // Additional SoD checks can be added here
        // e.g., check if the same person created and approved previous requisitions

        return [
            'violations' => $violations,
            'compliant' => empty($violations),
        ];
    }
}