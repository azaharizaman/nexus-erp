<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Requests\Api\Backoffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Staff Request
 * 
 * Validates data for creating a new staff member.
 */
class StoreStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:staff,email',
            'phone' => 'nullable|string|max:20',
            'hire_date' => 'required|date|before_or_equal:today',
            'resignation_date' => 'nullable|date|after:hire_date',
            'company_id' => 'required|exists:companies,id',
            'office_id' => 'nullable|exists:offices,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'supervisor_id' => 'nullable|exists:staff,id',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The staff name is required.',
            'email.required' => 'The email address is required.',
            'email.unique' => 'This email address is already registered.',
            'hire_date.required' => 'The hire date is required.',
            'hire_date.before_or_equal' => 'The hire date cannot be in the future.',
            'resignation_date.after' => 'The resignation date must be after the hire date.',
            'company_id.required' => 'The company selection is required.',
            'company_id.exists' => 'The selected company does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $officeId = $this->input('office_id');
            $departmentId = $this->input('department_id');
            $companyId = $this->input('company_id');
            
            // If office is specified, validate it belongs to the company
            if ($officeId && $companyId) {
                $office = \Nexus\Backoffice\Models\Office::find($officeId);
                if ($office && $office->company_id != $companyId) {
                    $validator->errors()->add('office_id', 'The selected office does not belong to the specified company.');
                }
            }
            
            // If department is specified, validate it belongs to the office
            if ($departmentId && $officeId) {
                $department = \Nexus\Backoffice\Models\Department::find($departmentId);
                if ($department && $department->office_id != $officeId) {
                    $validator->errors()->add('department_id', 'The selected department does not belong to the specified office.');
                }
            }
        });
    }
}