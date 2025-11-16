<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Blanket PO Release Request
 */
class CreateBlanketPOReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'required_delivery_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.blanket_po_item_id' => 'required|exists:blanket_purchase_order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.delivery_date' => 'nullable|date|after:today',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'At least one item must be included in the release.',
            'required_delivery_date.after' => 'Delivery date must be in the future.',
            'items.*.delivery_date.after' => 'Item delivery date must be in the future.',
        ];
    }
}