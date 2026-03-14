<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\InviteMemberRequest;
use App\Http\Resources\WorkspaceInvitationResource;
use App\Jobs\SendWorkspaceInvitationJob;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole('admin'), 403);

        $invitations = WorkspaceInvitation::query()
            ->pending()
            ->with('inviter')
            ->latest()
            ->get();

        return response()->json([
            'data' => WorkspaceInvitationResource::collection($invitations),
        ]);
    }

    public function store(InviteMemberRequest $request): JsonResponse
    {
        $invitation = WorkspaceInvitation::query()->create([
            'tenant_id' => currentTenant()?->id,
            'invited_by' => auth()->id(),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
        ]);

        SendWorkspaceInvitationJob::dispatch($invitation);

        return response()->json([
            'data' => WorkspaceInvitationResource::make($invitation),
        ], 201);
    }

    public function destroy(WorkspaceInvitation $invitation): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->can('delete', $invitation), 403);

        $invitation->delete();

        return response()->json(null, 204);
    }
}
