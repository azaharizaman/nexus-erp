<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Blanket PO Request
 */
class CreateBlanketPORequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => 'required|exists:vendors,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'total_committed_value' => 'required|numeric|min:0.01',
            'currency_code' => 'nullable|string|size:3',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'payment_terms' => 'nullable|string|max:255',
            'auto_approval_limit' => 'nullable|numeric|min:0',
            'utilization_alert_threshold' => 'nullable|numeric|min:0|max:1',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.item_description' => 'required|string|max:255',
            'items.*.specifications' => 'nullable|string|max:1000',
            'items.*.unit_of_measure' => 'nullable|string|max:50',
            'items.*.max_quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.category_code' => 'nullable|string|max:50',
            'items.*.gl_account_code' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'valid_until.after' => 'Valid until date must be after valid from date.',
            'items.min' => 'At least one item must be added to the blanket PO.',
        ];
    }
}