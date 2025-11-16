<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Select Winner Request
 */
class SelectWinnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'winning_quote_id' => 'required|exists:vendor_quotes,id',
            'evaluation_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'winning_quote_id.required' => 'A winning quote must be selected.',
        ];
    }
}