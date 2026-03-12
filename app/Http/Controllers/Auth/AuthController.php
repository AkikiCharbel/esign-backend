<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->validated())) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        /** @var User $user */
        $user = Auth::user();

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
            'user' => UserResource::make($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => UserResource::make($request->user()),
        ]);
    }
}
