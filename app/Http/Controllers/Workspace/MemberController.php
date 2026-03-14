<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\PermissionRegistrar;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole('admin'), 403);

        /** @var Tenant $tenant */
        $tenant = currentTenant();

        $members = User::query()
            ->where('tenant_id', $tenant->id)
            ->get();

        return response()->json([
            'data' => MemberResource::collection($members),
        ]);
    }

    public function update(UpdateMemberRequest $request, User $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = currentTenant();

        abort_unless($member->tenant_id === $tenant->id, 403);

        /** @var User $user */
        $user = auth()->user();
        abort_if($user->id === $member->id, 422, 'You cannot change your own role.');

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        if ($member->hasRole('admin') && $request->validated('role') !== 'admin') {
            $adminCount = User::query()
                ->where('tenant_id', $tenant->id)
                ->role('admin')
                ->count();
            abort_if($adminCount <= 1, 422, 'Cannot demote the last admin.');
        }

        $member->syncRoles([$request->validated('role')]);

        return response()->json([
            'data' => MemberResource::make($member->fresh()),
        ]);
    }

    public function destroy(User $member): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = currentTenant();

        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasRole('admin'), 403);
        abort_unless($member->tenant_id === $tenant->id, 403);
        abort_if($user->id === $member->id, 422, 'You cannot remove yourself.');

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $adminCount = User::query()
            ->where('tenant_id', $tenant->id)
            ->role('admin')
            ->count();

        if ($member->hasRole('admin') && $adminCount <= 1) {
            abort(422, 'Cannot remove the last admin.');
        }

        $member->tokens()->delete();
        $member->delete();

        return response()->json(null, 204);
    }
}
