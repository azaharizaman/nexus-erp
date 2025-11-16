<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\Office;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Create Department Action
 * 
 * Orchestrates department creation with proper validation and hierarchy management.
 */
class CreateDepartmentAction extends Action
{
    /**
     * Create a new department with validation.
     * 
     * @param array $departmentData
     * @return Department
     * @throws ValidationException
     */
    public function handle(...$parameters): Department
    {
        $departmentData = $parameters[0] ?? [];
        
        if (!is_array($departmentData)) {
            throw new \InvalidArgumentException('Department data must be an array');
        }
        
        // Validate input data
        $this->validateDepartmentData($departmentData);
        
        // Verify office exists and is active
        $office = Office::find($departmentData['office_id']);
        if (!$office || !$office->is_active) {
            $validator = Validator::make([], []);
            $validator->errors()->add('office_id', 'The selected office is not active or does not exist.');
            throw new ValidationException($validator);
        }
        
        // Validate parent department hierarchy
        if (isset($departmentData['parent_department_id'])) {
            $this->validateParentDepartment($departmentData['parent_department_id'], $departmentData['office_id']);
        }
        
        // Create the department
        $department = Department::create([
            'name' => $departmentData['name'],
            'code' => $departmentData['code'],
            'description' => $departmentData['description'] ?? null,
            'office_id' => $departmentData['office_id'],
            'parent_department_id' => $departmentData['parent_department_id'] ?? null,
            'is_active' => $departmentData['is_active'] ?? true,
        ]);
        
        return $department;
    }
    
    /**
     * Validate department data.
     * 
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validateDepartmentData(array $data): void
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code',
            'description' => 'nullable|string',
            'office_id' => 'required|exists:offices,id',
            'parent_department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
    
    /**
     * Validate parent department relationship.
     * 
     * @param int $parentDepartmentId
     * @param int $officeId
     * @return void
     * @throws ValidationException
     */
    protected function validateParentDepartment(int $parentDepartmentId, int $officeId): void
    {
        $parentDepartment = Department::find($parentDepartmentId);
        
        if (!$parentDepartment) {
            $validator = Validator::make([], []);
            $validator->errors()->add('parent_department_id', 'The selected parent department does not exist.');
            throw new ValidationException($validator);
        }
        
        // Ensure parent department belongs to the same office
        if ($parentDepartment->office_id !== $officeId) {
            $validator = Validator::make([], []);
            $validator->errors()->add('parent_department_id', 'The parent department must belong to the same office.');
            throw new ValidationException($validator);
        }
        
        // Ensure parent department is active
        if (!$parentDepartment->is_active) {
            $validator = Validator::make([], []);
            $validator->errors()->add('parent_department_id', 'The parent department must be active.');
            throw new ValidationException($validator);
        }
    }
    
    /**
     * Execute the action with structured return format.
     * 
     * @param mixed ...$parameters
     * @return array
     */
    public function execute(...$parameters): array
    {
        try {
            $departmentData = $parameters[0] ?? [];
            if (!is_array($departmentData)) {
                throw new \InvalidArgumentException('Department data must be an array');
            }
            
            $department = $this->handle($departmentData);
            
            // Load relationships for response
            $department->load(['office.company', 'parentDepartment']);
            
            return [
                'success' => true,
                'message' => 'Department created successfully',
                'data' => $department,
            ];
            
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ];
        }
    }
}