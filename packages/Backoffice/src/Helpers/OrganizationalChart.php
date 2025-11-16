<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Helpers;

use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Company;
use Illuminate\Database\Eloquent\Collection;

/**
 * Organizational Chart Helper
 * 
 * Provides utilities for generating and working with organizational charts.
 */
class OrganizationalChart
{
    /**
     * Generate a complete organizational chart for a company.
     */
    public static function forCompany(Company $company): array
    {
        $topLevelStaff = $company->getTopLevelStaff();
        
        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ],
            'chart' => $topLevelStaff->map(function (Staff $staff) {
                return $staff->getOrganizationalChart();
            })->toArray(),
            'metadata' => [
                'total_staff' => $company->getAllStaff()->count(),
                'total_managers' => $company->getAllStaff()->filter(fn($s) => $s->isManager())->count(),
                'max_depth' => self::getMaxDepth($topLevelStaff),
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Generate organizational chart starting from a specific staff member.
     */
    public static function fromStaff(Staff $staff): array
    {
        return [
            'root_staff' => [
                'id' => $staff->id,
                'name' => $staff->full_name,
                'position' => $staff->position?->name,
            ],
            'chart' => $staff->getOrganizationalChart(),
            'metadata' => [
                'team_size' => $staff->getTeamSize(),
                'span_of_control' => $staff->getSpanOfControl(),
                'reporting_level' => $staff->getReportingLevel(),
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Generate a flat organizational chart (all staff with their direct supervisor).
     */
    public static function flatChart(Company $company): array
    {
        $allStaff = $company->getAllStaff();
        
        return $allStaff->map(function (Staff $staff) {
            return [
                'id' => $staff->id,
                'employee_id' => $staff->employee_id,
                'name' => $staff->full_name,
                'position' => $staff->position?->name,
                'email' => $staff->email,
                'office' => $staff->office?->name,
                'department' => $staff->department?->name,
                'supervisor' => $staff->supervisor ? [
                    'id' => $staff->supervisor->id,
                    'name' => $staff->supervisor->full_name,
                    'position' => $staff->supervisor->position?->name,
                ] : null,
                'direct_subordinates_count' => $staff->getSpanOfControl(),
                'total_team_size' => $staff->getTeamSize(),
                'reporting_level' => $staff->getReportingLevel(),
            ];
        })->toArray();
    }

    /**
     * Generate reporting paths for all staff to top-level management.
     */
    public static function reportingPaths(Company $company): array
    {
        $allStaff = $company->getAllStaff();
        
        return $allStaff->map(function (Staff $staff) {
            $path = $staff->getReportingPath();
            
            return [
                'staff' => [
                    'id' => $staff->id,
                    'name' => $staff->full_name,
                    'position' => $staff->position?->name,
                ],
                'path' => $path->map(function (Staff $pathStaff) {
                    return [
                        'id' => $pathStaff->id,
                        'name' => $pathStaff->full_name,
                        'position' => $pathStaff->position?->name,
                    ];
                })->toArray(),
                'path_length' => $path->count() - 1, // Exclude self
            ];
        })->toArray();
    }

    /**
     * Generate organization statistics.
     */
    public static function statistics(Company $company): array
    {
        $allStaff = $company->getAllStaff();
        $managers = $allStaff->filter(fn($s) => $s->isManager());
        $topLevel = $allStaff->filter(fn($s) => $s->isTopLevel());
        
        $spanOfControlData = $managers->map(fn($m) => $m->getSpanOfControl());
        $teamSizeData = $managers->map(fn($m) => $m->getTeamSize());
        $reportingLevels = $allStaff->map(fn($s) => $s->getReportingLevel());
        
        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'totals' => [
                'total_staff' => $allStaff->count(),
                'total_managers' => $managers->count(),
                'top_level_executives' => $topLevel->count(),
                'individual_contributors' => $allStaff->count() - $managers->count(),
            ],
            'span_of_control' => [
                'average' => $spanOfControlData->avg(),
                'minimum' => $spanOfControlData->min(),
                'maximum' => $spanOfControlData->max(),
                'median' => $spanOfControlData->median(),
            ],
            'team_sizes' => [
                'average' => $teamSizeData->avg(),
                'minimum' => $teamSizeData->min(),
                'maximum' => $teamSizeData->max(),
                'median' => $teamSizeData->median(),
            ],
            'hierarchy_depth' => [
                'maximum_levels' => $reportingLevels->max(),
                'average_level' => $reportingLevels->avg(),
                'level_distribution' => $reportingLevels->countBy()->toArray(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Find optimal reorganization suggestions.
     */
    public static function reorganizationSuggestions(Company $company): array
    {
        $allStaff = $company->getAllStaff();
        $managers = $allStaff->filter(fn($s) => $s->isManager());
        
        $suggestions = [];
        
        // Check for managers with too many direct reports
        $overloadedManagers = $managers->filter(fn($m) => $m->getSpanOfControl() > 10);
        if ($overloadedManagers->count() > 0) {
            $suggestions[] = [
                'type' => 'span_of_control_too_high',
                'description' => 'Some managers have more than 10 direct reports',
                'affected_managers' => $overloadedManagers->map(function (Staff $manager) {
                    return [
                        'id' => $manager->id,
                        'name' => $manager->full_name,
                        'position' => $manager->position?->name,
                        'direct_reports' => $manager->getSpanOfControl(),
                    ];
                })->toArray(),
                'recommendation' => 'Consider adding middle management or redistributing staff',
            ];
        }
        
        // Check for very deep hierarchies
        $deepHierarchy = $allStaff->filter(fn($s) => $s->getReportingLevel() > 6);
        if ($deepHierarchy->count() > 0) {
            $suggestions[] = [
                'type' => 'hierarchy_too_deep',
                'description' => 'Some staff are more than 6 levels from top management',
                'affected_staff' => $deepHierarchy->map(function (Staff $staff) {
                    return [
                        'id' => $staff->id,
                        'name' => $staff->full_name,
                        'position' => $staff->position?->name,
                        'reporting_level' => $staff->getReportingLevel(),
                    ];
                })->toArray(),
                'recommendation' => 'Consider flattening the organizational structure',
            ];
        }
        
        // Check for managers with very few reports
        $underutilizedManagers = $managers->filter(fn($m) => $m->getSpanOfControl() < 3);
        if ($underutilizedManagers->count() > 0) {
            $suggestions[] = [
                'type' => 'span_of_control_too_low',
                'description' => 'Some managers have fewer than 3 direct reports',
                'affected_managers' => $underutilizedManagers->map(function (Staff $manager) {
                    return [
                        'id' => $manager->id,
                        'name' => $manager->full_name,
                        'position' => $manager->position?->name,
                        'direct_reports' => $manager->getSpanOfControl(),
                    ];
                })->toArray(),
                'recommendation' => 'Consider consolidating management roles or expanding responsibilities',
            ];
        }
        
        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'suggestions' => $suggestions,
            'analysis_date' => now()->toISOString(),
        ];
    }

    /**
     * Export organizational chart to different formats.
     */
    public static function export(Company $company, string $format = 'json'): array|string
    {
        $chart = self::forCompany($company);
        
        switch ($format) {
            case 'csv':
                return self::exportToCsv($company);
            case 'dot':
                return self::exportToDot($company);
            case 'json':
            default:
                return $chart;
        }
    }

    /**
     * Export to CSV format for spreadsheet applications.
     */
    private static function exportToCsv(Company $company): string
    {
        $flatChart = self::flatChart($company);
        
        $csv = "Employee ID,Name,Position,Email,Office,Department,Supervisor,Direct Reports,Team Size,Level\n";
        
        foreach ($flatChart as $staff) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%d,%d,%d\n",
                $staff['employee_id'],
                '"' . str_replace('"', '""', $staff['name']) . '"',
                '"' . str_replace('"', '""', $staff['position'] ?? '') . '"',
                $staff['email'] ?? '',
                '"' . str_replace('"', '""', $staff['office'] ?? '') . '"',
                '"' . str_replace('"', '""', $staff['department'] ?? '') . '"',
                $staff['supervisor'] ? '"' . str_replace('"', '""', $staff['supervisor']['name']) . '"' : '',
                $staff['direct_subordinates_count'],
                $staff['total_team_size'],
                $staff['reporting_level']
            );
        }
        
        return $csv;
    }

    /**
     * Export to DOT format for Graphviz visualization.
     */
    private static function exportToDot(Company $company): string
    {
        $allStaff = $company->getAllStaff();
        
        $dot = "digraph OrgChart {\n";
        $dot .= "  rankdir=TB;\n";
        $dot .= "  node [shape=box, style=filled, fillcolor=lightblue];\n\n";
        
        // Add nodes
        foreach ($allStaff as $staff) {
            $label = sprintf(
                "%s\\n%s\\n%s",
                $staff->full_name,
                $staff->position?->name ?? '',
                $staff->employee_id
            );
            $dot .= sprintf("  \"%d\" [label=\"%s\"];\n", $staff->id, $label);
        }
        
        $dot .= "\n";
        
        // Add edges (supervisor relationships)
        foreach ($allStaff as $staff) {
            if ($staff->supervisor) {
                $dot .= sprintf("  \"%d\" -> \"%d\";\n", $staff->supervisor->id, $staff->id);
            }
        }
        
        $dot .= "}\n";
        
        return $dot;
    }

    /**
     * Get maximum depth of organizational chart.
     */
    private static function getMaxDepth(Collection $topLevelStaff): int
    {
        $maxDepth = 0;
        
        foreach ($topLevelStaff as $staff) {
            $depth = self::calculateDepth($staff, 0);
            $maxDepth = max($maxDepth, $depth);
        }
        
        return $maxDepth;
    }

    /**
     * Calculate depth recursively.
     */
    private static function calculateDepth(Staff $staff, int $currentDepth): int
    {
        $maxSubDepth = $currentDepth;
        
        foreach ($staff->subordinates as $subordinate) {
            $subDepth = self::calculateDepth($subordinate, $currentDepth + 1);
            $maxSubDepth = max($maxSubDepth, $subDepth);
        }
        
        return $maxSubDepth;
    }
}