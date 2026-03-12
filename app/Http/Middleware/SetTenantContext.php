<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->tenant_id;

        if ($tenantId) {
            $tenant = Tenant::query()->find($tenantId);
            app()->instance('currentTenant', $tenant);
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        }

        return $next($request);
    }
}
