<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Company;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\Position;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Enums\StaffStatus;

/**
 * Export Organizational Data Action
 * 
 * Orchestrates export of organizational data in various formats.
 */
class ExportOrganizationalDataAction extends Action
{
    /**
     * Export organizational data.
     * 
     * @param string $type Export type ('companies', 'offices', 'departments', 'positions', 'staff', 'full')
     * @param array $options Export options (format, filters, etc.)
     * @return array Export results with data and metadata
     */
    public function handle(...$parameters): array
    {
        $type = $parameters[0] ?? 'full';
        $options = $parameters[1] ?? [];
        
        // Set default options
        $options = array_merge([
            'format' => 'array', // array, json, csv
            'include_inactive' => false,
            'company_id' => null,
            'office_id' => null,
            'department_id' => null,
            'include_relationships' => true,
        ], $options);
        
        $data = match ($type) {
            'companies' => $this->exportCompanies($options),
            'offices' => $this->exportOffices($options),
            'departments' => $this->exportDepartments($options),
            'positions' => $this->exportPositions($options),
            'staff' => $this->exportStaff($options),
            'full' => $this->exportFullOrganization($options),
            default => throw new \InvalidArgumentException("Unknown export type: {$type}")
        };
        
        return [
            'type' => $type,
            'format' => $options['format'],
            'exported_at' => now()->toISOString(),
            'record_count' => $this->getRecordCount($data),
            'options' => $options,
            'data' => $this->formatOutput($data, $options['format']),
        ];
    }

    /**
     * This action doesn't modify data, so no transactions needed.
     */
    protected function useTransactions(): bool
    {
        return false;
    }

    /**
     * Export companies data.
     * 
     * @param array $options
     * @return array
     */
    protected function exportCompanies(array $options): array
    {
        $query = Company::query();
        
        if (!$options['include_inactive']) {
            $query->where('is_active', true);
        }
        
        if ($options['company_id']) {
            $query->where('id', $options['company_id']);
        }
        
        if ($options['include_relationships']) {
            $query->with(['parent', 'children']);
        }
        
        return $query->get()->map(function ($company) use ($options) {
            $data = [
                'id' => $company->id,
                'name' => $company->name,
                'registration_number' => $company->registration_number,
                'description' => $company->description,
                'is_active' => $company->is_active,
                'created_at' => $company->created_at?->toISOString(),
                'updated_at' => $company->updated_at?->toISOString(),
            ];
            
            if ($options['include_relationships']) {
                $data['parent_company'] = $company->parent ? [
                    'id' => $company->parent->id,
                    'name' => $company->parent->name,
                ] : null;
                
                $data['child_companies'] = $company->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                    ];
                })->toArray();
            }
            
            return $data;
        })->toArray();
    }

    /**
     * Export offices data.
     * 
     * @param array $options
     * @return array
     */
    protected function exportOffices(array $options): array
    {
        $query = Office::query();
        
        if (!$options['include_inactive']) {
            $query->where('is_active', true);
        }
        
        if ($options['company_id']) {
            $query->where('company_id', $options['company_id']);
        }
        
        if ($options['office_id']) {
            $query->where('id', $options['office_id']);
        }
        
        if ($options['include_relationships']) {
            $query->with(['company', 'departments']);
        }
        
        return $query->get()->map(function ($office) use ($options) {
            $data = [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
                'address' => $office->address,
                'phone' => $office->phone,
                'email' => $office->email,
                'is_active' => $office->is_active,
                'company_id' => $office->company_id,
                'created_at' => $office->created_at?->toISOString(),
                'updated_at' => $office->updated_at?->toISOString(),
            ];
            
            if ($options['include_relationships']) {
                $data['company'] = $office->company ? [
                    'id' => $office->company->id,
                    'name' => $office->company->name,
                ] : null;
                
                $data['departments'] = $office->departments->map(function ($department) {
                    return [
                        'id' => $department->id,
                        'name' => $department->name,
                    ];
                })->toArray();
            }
            
            return $data;
        })->toArray();
    }

    /**
     * Export departments data.
     * 
     * @param array $options
     * @return array
     */
    protected function exportDepartments(array $options): array
    {
        $query = Department::query();
        
        if (!$options['include_inactive']) {
            $query->where('is_active', true);
        }
        
        if ($options['company_id']) {
            $query->whereHas('office', function ($q) use ($options) {
                $q->where('company_id', $options['company_id']);
            });
        }
        
        if ($options['office_id']) {
            $query->where('office_id', $options['office_id']);
        }
        
        if ($options['department_id']) {
            $query->where('id', $options['department_id']);
        }
        
        if ($options['include_relationships']) {
            $query->with(['office.company', 'positions']);
        }
        
        return $query->get()->map(function ($department) use ($options) {
            $data = [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'description' => $department->description,
                'is_active' => $department->is_active,
                'office_id' => $department->office_id,
                'created_at' => $department->created_at?->toISOString(),
                'updated_at' => $department->updated_at?->toISOString(),
            ];
            
            if ($options['include_relationships'] && $department->office) {
                $data['office'] = [
                    'id' => $department->office->id,
                    'name' => $department->office->name,
                ];
                
                if ($department->office->company) {
                    $data['company'] = [
                        'id' => $department->office->company->id,
                        'name' => $department->office->company->name,
                    ];
                }
                
                $data['positions'] = $department->positions->map(function ($position) {
                    return [
                        'id' => $position->id,
                        'title' => $position->title,
                    ];
                })->toArray();
            }
            
            return $data;
        })->toArray();
    }

    /**
     * Export positions data.
     * 
     * @param array $options
     * @return array
     */
    protected function exportPositions(array $options): array
    {
        $query = Position::query();
        
        if (!$options['include_inactive']) {
            $query->where('is_active', true);
        }
        
        if ($options['department_id']) {
            $query->where('department_id', $options['department_id']);
        }
        
        if ($options['include_relationships']) {
            $query->with(['department.office.company', 'staff']);
        }
        
        return $query->get()->map(function ($position) use ($options) {
            $data = [
                'id' => $position->id,
                'title' => $position->title,
                'code' => $position->code,
                'description' => $position->description,
                'is_active' => $position->is_active,
                'department_id' => $position->department_id,
                'created_at' => $position->created_at?->toISOString(),
                'updated_at' => $position->updated_at?->toISOString(),
            ];
            
            if ($options['include_relationships'] && $position->department) {
                $data['department'] = [
                    'id' => $position->department->id,
                    'name' => $position->department->name,
                ];
                
                if ($position->department->office) {
                    $data['office'] = [
                        'id' => $position->department->office->id,
                        'name' => $position->department->office->name,
                    ];
                    
                    if ($position->department->office->company) {
                        $data['company'] = [
                            'id' => $position->department->office->company->id,
                            'name' => $position->department->office->company->name,
                        ];
                    }
                }
                
                $data['staff'] = $position->staff->map(function ($staff) {
                    return [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'status' => $staff->status->value,
                    ];
                })->toArray();
            }
            
            return $data;
        })->toArray();
    }

    /**
     * Export staff data.
     * 
     * @param array $options
     * @return array
     */
    protected function exportStaff(array $options): array
    {
        $query = Staff::query();
        
        if (!$options['include_inactive']) {
            $query->where('status', StaffStatus::ACTIVE);
        }
        
        if ($options['company_id']) {
            $query->where('company_id', $options['company_id']);
        }
        
        if ($options['office_id']) {
            $query->where('office_id', $options['office_id']);
        }
        
        if ($options['department_id']) {
            $query->where('department_id', $options['department_id']);
        }
        
        if ($options['include_relationships']) {
            $query->with(['company', 'office', 'department', 'position', 'supervisor']);
        }
        
        return $query->get()->map(function ($staff) use ($options) {
            $data = [
                'id' => $staff->id,
                'employee_id' => $staff->employee_id,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'hire_date' => $staff->hire_date?->format('Y-m-d'),
                'resignation_date' => $staff->resignation_date?->format('Y-m-d'),
                'termination_date' => $staff->termination_date?->format('Y-m-d'),
                'status' => $staff->status->value,
                'is_active' => $staff->is_active,
                'company_id' => $staff->company_id,
                'office_id' => $staff->office_id,
                'department_id' => $staff->department_id,
                'position_id' => $staff->position_id,
                'supervisor_id' => $staff->supervisor_id,
                'created_at' => $staff->created_at?->toISOString(),
                'updated_at' => $staff->updated_at?->toISOString(),
            ];
            
            if ($options['include_relationships']) {
                $data['company'] = $staff->company ? [
                    'id' => $staff->company->id,
                    'name' => $staff->company->name,
                ] : null;
                
                $data['office'] = $staff->office ? [
                    'id' => $staff->office->id,
                    'name' => $staff->office->name,
                ] : null;
                
                $data['department'] = $staff->department ? [
                    'id' => $staff->department->id,
                    'name' => $staff->department->name,
                ] : null;
                
                $data['position'] = $staff->position ? [
                    'id' => $staff->position->id,
                    'title' => $staff->position->title,
                ] : null;
                
                $data['supervisor'] = $staff->supervisor ? [
                    'id' => $staff->supervisor->id,
                    'name' => $staff->supervisor->name,
                ] : null;
            }
            
            return $data;
        })->toArray();
    }

    /**
     * Export full organizational data.
     * 
     * @param array $options
     * @return array
     */
    protected function exportFullOrganization(array $options): array
    {
        return [
            'companies' => $this->exportCompanies($options),
            'offices' => $this->exportOffices($options),
            'departments' => $this->exportDepartments($options),
            'positions' => $this->exportPositions($options),
            'staff' => $this->exportStaff($options),
        ];
    }

    /**
     * Get record count from exported data.
     * 
     * @param array $data
     * @return int
     */
    protected function getRecordCount(array $data): int
    {
        if (isset($data['companies'])) {
            // Full export
            return array_sum([
                count($data['companies']),
                count($data['offices']),
                count($data['departments']),
                count($data['positions']),
                count($data['staff']),
            ]);
        }
        
        return count($data);
    }

    /**
     * Format output based on requested format.
     * 
     * @param array $data
     * @param string $format
     * @return mixed
     */
    protected function formatOutput(array $data, string $format)
    {
        return match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'csv' => $this->convertToCSV($data),
            'array' => $data,
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    /**
     * Convert data to CSV format.
     * 
     * @param array $data
     * @return string
     */
    protected function convertToCSV(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        // If this is a full export, convert each section separately
        if (isset($data['companies'])) {
            $csv = '';
            foreach ($data as $section => $records) {
                $csv .= "# {$section}\n";
                $csv .= $this->arrayToCSV($records) . "\n\n";
            }
            return trim($csv);
        }
        
        return $this->arrayToCSV($data);
    }

    /**
     * Convert array to CSV string.
     * 
     * @param array $data
     * @return string
     */
    protected function arrayToCSV(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $csvRow[] = $value;
            }
            fputcsv($output, $csvRow);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}