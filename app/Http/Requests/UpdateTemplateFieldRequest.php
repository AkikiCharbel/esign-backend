<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', Rule::in(['signature', 'initials', 'text', 'date', 'checkbox', 'radio', 'dropdown'])],
            'label' => ['sometimes', 'string', 'max:255'],
            'required' => ['sometimes', 'boolean'],
            'x' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'y' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'width' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'height' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'font_size' => ['sometimes', 'integer', 'min:6', 'max:72'],
            'multiline' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'array'],
            'signer_role' => ['nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
