<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SigningSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'field_values' => ['required', 'array', 'min:1'],
            'field_values.*.template_field_id' => ['required', 'integer', 'exists:template_fields,id'],
            'field_values.*.value' => ['nullable', 'string'],
        ];
    }
}
