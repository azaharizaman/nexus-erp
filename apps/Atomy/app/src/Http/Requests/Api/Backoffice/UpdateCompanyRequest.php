<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Requests\Api\Backoffice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Company Request
 * 
 * Validates data for updating an existing company.
 */
class UpdateCompanyRequest extends FormRequest
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
        $companyId = $this->route('company')?->id;
        
        return [
            'name' => 'sometimes|string|max:255',
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('companies', 'code')->ignore($companyId),
            ],
            'description' => 'nullable|string',
            'parent_company_id' => 'nullable|exists:companies,id',
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
            'name.string' => 'The company name must be a string.',
            'name.max' => 'The company name may not be greater than 255 characters.',
            'code.unique' => 'The company code has already been taken.',
            'code.max' => 'The company code may not be greater than 50 characters.',
            'parent_company_id.exists' => 'The selected parent company does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'parent_company_id' => 'parent company',
            'is_active' => 'active status',
        ];
    }
    
    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $companyId = $this->route('company')?->id;
            $parentId = $this->input('parent_company_id');
            
            // Prevent setting self as parent
            if ($parentId && $parentId == $companyId) {
                $validator->errors()->add('parent_company_id', 'A company cannot be its own parent.');
            }
        });
    }
}