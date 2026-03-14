<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RegisterController extends Controller
{
    public function registerTenant(RegisterTenantRequest $request, RegisterTenantAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return response()->json([
            'token' => $result['token'],
            'user' => UserResource::make($result['user']),
            'tenant' => TenantResource::make($result['tenant']),
        ], 201);
    }

    public function acceptInvitation(AcceptInvitationRequest $request): JsonResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->withoutGlobalScope('tenant')
            ->where('token', $request->validated('token'))
            ->first();

        if (! $invitation) {
            abort(404, 'Invitation not found.');
        }

        if ($invitation->accepted_at) {
            abort(409, 'Invitation has already been accepted.');
        }

        if ($invitation->expires_at->isPast()) {
            abort(410, 'Invitation has expired.');
        }

        $existingUser = User::query()
            ->where('email', $invitation->email)
            ->where('tenant_id', $invitation->tenant_id)
            ->exists();

        if ($existingUser) {
            abort(409, 'This email is already a member of this workspace.');
        }

        $result = DB::transaction(function () use ($request, $invitation): array {
            $user = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $invitation->email,
                'password' => $request->validated('password'),
                'tenant_id' => $invitation->tenant_id,
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($invitation->tenant_id);
            $user->assignRole($invitation->role);

            $invitation->update(['accepted_at' => now()]);

            return [
                'token' => $user->createToken('app')->plainTextToken,
                'user' => $user,
                'tenant' => $invitation->tenant,
            ];
        });

        return response()->json([
            'token' => $result['token'],
            'user' => UserResource::make($result['user']),
            'tenant' => TenantResource::make($result['tenant']),
        ], 201);
    }
}
