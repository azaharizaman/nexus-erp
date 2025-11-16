<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Submit Vendor Quote Request
 */
class SubmitVendorQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => 'required|exists:vendors,id',
            'total_quoted_price' => 'required|numeric|min:0',
            'delivery_days' => 'nullable|integer|min:1|max:365',
            'payment_terms' => 'nullable|string|max:255',
            'validity_days' => 'nullable|integer|min:1|max:365',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.rfq_item_id' => 'required|exists:rfq_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.delivery_days' => 'nullable|integer|min:1|max:365',
            'items.*.alternate_offer' => 'nullable|string|max:500',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.specifications_met' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'At least one item must be quoted.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price cannot be negative.',
        ];
    }
}