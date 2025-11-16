<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create RFQ Request
 */
class CreateRFQRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    public function rules(): array
    {
        return [
            'requisition_id' => 'required|exists:purchase_requisitions,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'quote_deadline' => 'required|date|after:today',
            'evaluation_criteria' => 'nullable|array',
            'evaluation_criteria.*.weight' => 'required_with:evaluation_criteria|integer|min:0|max:100',
            'evaluation_criteria.*.description' => 'required_with:evaluation_criteria|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'quote_deadline.after' => 'Quote deadline must be in the future.',
        ];
    }
}