<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Helpers\OrganizationalChart;

/**
 * Generate Organizational Chart Action
 * 
 * Orchestrates organizational chart generation with various options.
 */
class GenerateOrganizationalChartAction extends Action
{
    /**
     * Generate organizational chart for a company.
     * 
     * @param Company $company
     * @param array $options
     * @return array
     */
    public function handle(...$parameters): array
    {
        $company = $parameters[0] ?? null;
        $options = $parameters[1] ?? [];
        
        if (!$company instanceof Company) {
            throw new \InvalidArgumentException('First parameter must be a Company instance');
        }
        
        if (!is_array($options)) {
            $options = [];
        }
        
        // Set default options
        $defaultOptions = [
            'include_inactive' => false,
            'max_depth' => null,
            'include_staff_count' => true,
            'include_statistics' => false,
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Generate the chart using the helper
        $chart = OrganizationalChart::forCompany($company);
        
        // Add statistics if requested
        if ($options['include_statistics']) {
            $chart['statistics'] = $this->generateStatistics($company);
        }
        
        // Filter by depth if specified
        if ($options['max_depth'] !== null && is_int($options['max_depth'])) {
            $chart = $this->limitChartDepth($chart, $options['max_depth']);
        }
        
        return $chart;
    }

    /**
     * This action doesn't need database transactions.
     */
    protected function useTransactions(): bool
    {
        return false;
    }

    /**
     * Generate statistics for the company.
     * 
     * @param Company $company
     * @return array
     */
    protected function generateStatistics(Company $company): array
    {
        return OrganizationalChart::statistics($company);
    }

    /**
     * Limit chart depth to specified level.
     * 
     * @param array $chart
     * @param int $maxDepth
     * @return array
     */
    protected function limitChartDepth(array $chart, int $maxDepth): array
    {
        if ($maxDepth <= 0 || !isset($chart['children'])) {
            return $chart;
        }
        
        // Recursively limit depth
        $limitedChart = $chart;
        if ($maxDepth === 1) {
            unset($limitedChart['children']);
        } else {
            foreach ($limitedChart['children'] as $key => $child) {
                $limitedChart['children'][$key] = $this->limitChartDepth($child, $maxDepth - 1);
            }
        }
        
        return $limitedChart;
    }
}