<?php

namespace App\Http\Requests\Workspace;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeleteWorkspaceLogoRequest extends FormRequest
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
        return [];
    }
}
