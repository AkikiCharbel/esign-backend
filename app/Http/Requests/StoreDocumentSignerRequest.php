<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentSignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'sign_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
