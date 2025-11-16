<?php

declare(strict_types=1);

namespace Nexus\Atomy\Actions\Backoffice;

use Nexus\Atomy\Actions\Action;
use Nexus\Backoffice\Models\Staff;
use Nexus\Backoffice\Models\Office;
use Nexus\Backoffice\Models\Department;
use Nexus\Backoffice\Models\Position;
use Nexus\Backoffice\Models\Company;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Create Staff Action
 * 
 * Orchestrates staff creation with office, department, and position assignment.
 */
class CreateStaffAction extends Action
{
    /**
     * Create a new staff member with proper assignments.
     * 
     * @param array $staffData
     * @return Staff
     * @throws ValidationException
     */
    public function handle(...$parameters): Staff
    {
        $staffData = $parameters[0] ?? [];
        
        if (!is_array($staffData)) {
            throw new \InvalidArgumentException('Staff data must be an array');
        }
        
        // Validate input data
        $this->validateStaffData($staffData);
        
        // Validate organizational assignments
        $this->validateOrganizationalAssignments($staffData);
        
        // Create the staff member
        $staff = Staff::create([
            'name' => $staffData['name'],
            'email' => $staffData['email'],
            'phone' => $staffData['phone'] ?? null,
            'employee_id' => $staffData['employee_id'] ?? $this->generateEmployeeId(),
            'company_id' => $staffData['company_id'],
            'office_id' => $staffData['office_id'],
            'department_id' => $staffData['department_id'] ?? null,
            'position_id' => $staffData['position_id'],
            'supervisor_id' => $staffData['supervisor_id'] ?? null,
            'hire_date' => $staffData['hire_date'],
            'status' => $staffData['status'] ?? 'active',
            'salary' => $staffData['salary'] ?? null,
        ]);
        
        return $staff->fresh();
    }

    /**
     * Validate staff creation data.
     * 
     * @param array $data
     * @throws ValidationException
     */
    protected function validateStaffData(array $data): void
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff,email',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:staff,employee_id',
            'company_id' => 'required|exists:companies,id',
            'office_id' => 'required|exists:offices,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'supervisor_id' => 'nullable|exists:staff,id',
            'hire_date' => 'required|date|before_or_equal:today',
            'status' => 'in:active,inactive,on_leave,terminated,resigned,retired',
            'salary' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate organizational assignments (office belongs to company, etc.)
     * 
     * @param array $data
     * @throws \InvalidArgumentException
     */
    protected function validateOrganizationalAssignments(array $data): void
    {
        $company = Company::find($data['company_id']);
        $office = Office::find($data['office_id']);
        
        // Validate office belongs to company
        if ($office->company_id !== $company->id) {
            throw new \InvalidArgumentException('Office does not belong to the specified company');
        }
        
        // Validate department belongs to company (if specified)
        if (isset($data['department_id'])) {
            $department = Department::find($data['department_id']);
            if ($department->company_id !== $company->id) {
                throw new \InvalidArgumentException('Department does not belong to the specified company');
            }
        }
        
        // Validate position exists and is active
        $position = Position::find($data['position_id']);
        if (!$position->is_active) {
            throw new \InvalidArgumentException('Cannot assign staff to inactive position');
        }
        
        // Validate supervisor belongs to same company (if specified)
        if (isset($data['supervisor_id'])) {
            $supervisor = Staff::find($data['supervisor_id']);
            if ($supervisor->company_id !== $company->id) {
                throw new \InvalidArgumentException('Supervisor must belong to the same company');
            }
        }
    }

    /**
     * Generate a unique employee ID.
     * 
     * @return string
     */
    protected function generateEmployeeId(): string
    {
        do {
            $employeeId = 'EMP' . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Staff::where('employee_id', $employeeId)->exists());
        
        return $employeeId;
    }
}