<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Requests\Api\Backoffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Staff Transfer Request
 * 
 * Validates data for creating a staff transfer.
 */
class StaffTransferRequest extends FormRequest
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
            'to_office_id' => 'nullable|exists:offices,id',
            'to_department_id' => 'nullable|exists:departments,id',
            'to_position_id' => 'nullable|exists:positions,id',
            'to_supervisor_id' => 'nullable|exists:staff,id',
            'effective_date' => 'required|date|after_or_equal:today',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
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
            'effective_date.required' => 'The effective date is required.',
            'effective_date.after_or_equal' => 'The effective date cannot be in the past.',
            'to_office_id.exists' => 'The selected office does not exist.',
            'to_department_id.exists' => 'The selected department does not exist.',
            'to_position_id.exists' => 'The selected position does not exist.',
            'to_supervisor_id.exists' => 'The selected supervisor does not exist.',
            'reason.max' => 'The reason may not be greater than 500 characters.',
            'notes.max' => 'The notes may not be greater than 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $staff = $this->route('staff');
            $toOfficeId = $this->input('to_office_id');
            $toDepartmentId = $this->input('to_department_id');
            $toSupervisorId = $this->input('to_supervisor_id');
            
            // At least one field must be changed
            if (!$toOfficeId && !$toDepartmentId && !$this->input('to_position_id') && !$toSupervisorId) {
                $validator->errors()->add('transfer', 'At least one field must be changed for the transfer.');
                return;
            }
            
            // If department is specified, validate it belongs to the office
            if ($toDepartmentId && $toOfficeId) {
                $department = \Nexus\Backoffice\Models\Department::find($toDepartmentId);
                if ($department && $department->office_id != $toOfficeId) {
                    $validator->errors()->add('to_department_id', 'The selected department does not belong to the specified office.');
                }
            }
            
            // Validate supervisor is not the staff member themselves
            if ($toSupervisorId && $staff && $toSupervisorId == $staff->id) {
                $validator->errors()->add('to_supervisor_id', 'A staff member cannot be their own supervisor.');
            }
            
            // Validate the transfer is actually changing something
            if ($staff) {
                $hasChanges = false;
                
                if ($toOfficeId && $toOfficeId != $staff->office_id) $hasChanges = true;
                if ($toDepartmentId && $toDepartmentId != $staff->department_id) $hasChanges = true;
                if ($this->input('to_position_id') && $this->input('to_position_id') != $staff->position_id) $hasChanges = true;
                if ($toSupervisorId && $toSupervisorId != $staff->supervisor_id) $hasChanges = true;
                
                if (!$hasChanges) {
                    $validator->errors()->add('transfer', 'The transfer must change at least one of the staff member\'s attributes.');
                }
            }
        });
    }
}