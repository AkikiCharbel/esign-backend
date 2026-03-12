<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'template_id' => [
                'required',
                'integer',
                Rule::exists('templates', 'id')->where('tenant_id', currentTenant()?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'custom_message' => ['nullable', 'string'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'reply_to_name' => ['nullable', 'string', 'max:255'],
            'has_attachments' => ['sometimes', 'boolean'],
            'attachment_instructions' => ['nullable', 'string'],
        ];
    }
}
