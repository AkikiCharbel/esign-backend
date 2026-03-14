<?php

namespace App\Http\Requests\Workspace;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                Rule::unique(User::class, 'email')->where('tenant_id', $tenantId),
                Rule::unique(WorkspaceInvitation::class, 'email')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('accepted_at')
                    ->where(fn ($query) => $query->where('expires_at', '>', now())),
            ],
            'role' => ['required', 'string', Rule::in(['staff', 'viewer'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already a member or has a pending invitation.',
        ];
    }
}
