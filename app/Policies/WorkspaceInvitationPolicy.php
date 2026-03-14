<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceInvitation;

class WorkspaceInvitationPolicy
{
    public function delete(User $user, WorkspaceInvitation $workspaceInvitation): bool
    {
        return $user->tenant_id === $workspaceInvitation->tenant_id
            && $user->hasRole('admin');
    }
}
