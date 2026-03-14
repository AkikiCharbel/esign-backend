<?php

namespace App\Http\Requests\Workspace;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null && $user->hasRole('admin');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = currentTenant()?->id;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'string',
                'max:50',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tenants', 'slug')->ignore($tenantId),
            ],
            'settings' => ['sometimes', 'array'],
            'settings.timezone' => ['sometimes', 'string', 'timezone'],
            'settings.date_format' => ['sometimes', 'string', Rule::in(['Y-m-d', 'd/m/Y', 'm/d/Y'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already taken.',
        ];
    }
}
