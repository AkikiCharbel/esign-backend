<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

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
}
