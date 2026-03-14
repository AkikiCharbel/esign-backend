<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\DeleteWorkspaceLogoRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Requests\Workspace\UploadWorkspaceLogoRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class WorkspaceController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => TenantResource::make(currentTenant()),
        ]);
    }

    public function update(UpdateWorkspaceRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = currentTenant();

        $tenant->update($request->safe()->except(['settings']));

        if ($request->has('settings')) {
            /** @var array<string, mixed> $validatedSettings */
            $validatedSettings = $request->validated('settings');
            /** @var array<string, mixed> $currentSettings */
            $currentSettings = $tenant->settings ?? [];
            $tenant->update([
                'settings' => array_merge($currentSettings, $validatedSettings),
            ]);
        }

        return response()->json([
            'data' => TenantResource::make($tenant->fresh()),
        ]);
    }

    public function uploadLogo(UploadWorkspaceLogoRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = currentTenant();
        $tenant->addMediaFromRequest('logo')->toMediaCollection('logo');

        return response()->json([
            'logo_url' => $tenant->getFirstMedia('logo')
                ? route('media.show', $tenant->getFirstMedia('logo'))
                : null,
        ]);
    }

    public function deleteLogo(DeleteWorkspaceLogoRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = currentTenant();
        $tenant->clearMediaCollection('logo');

        return response()->json(null, 204);
    }
}
