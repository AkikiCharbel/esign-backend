<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncTemplateFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fields' => ['required', 'array'],
            'fields.*.id' => ['sometimes', 'nullable', 'integer'],
            'fields.*.page' => ['required', 'integer', 'min:1'],
            'fields.*.type' => ['required', Rule::in(['signature', 'initials', 'text', 'date', 'checkbox', 'radio', 'dropdown'])],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.x' => ['required', 'numeric', 'min:0', 'max:100'],
            'fields.*.y' => ['required', 'numeric', 'min:0', 'max:100'],
            'fields.*.width' => ['required', 'numeric', 'min:0', 'max:100'],
            'fields.*.height' => ['required', 'numeric', 'min:0', 'max:100'],
            'fields.*.font_size' => ['sometimes', 'integer', 'min:6', 'max:72'],
            'fields.*.multiline' => ['sometimes', 'boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.signer_role' => ['nullable', 'string', 'max:255'],
            'fields.*.order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
