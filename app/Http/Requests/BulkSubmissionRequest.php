<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'document_id' => [
                'required',
                'integer',
                Rule::exists('documents', 'id')->where('tenant_id', currentTenant()?->id),
            ],
            'csv' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'recipients' => ['nullable', 'array'],
            'recipients.*.name' => ['required_with:recipients', 'string', 'max:255'],
            'recipients.*.email' => ['required_with:recipients', 'email', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->hasFile('csv') && ! $this->has('recipients')) {
                $validator->errors()->add('recipients', 'Either csv or recipients is required.');
            }
        });
    }
}
