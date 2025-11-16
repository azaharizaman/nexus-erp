<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Invite Vendors Request
 */
class InviteVendorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'required|exists:vendors,id',
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_ids.min' => 'At least one vendor must be invited.',
        ];
    }
}