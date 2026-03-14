<?php

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RegisterTenantAction
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{token: string, user: User, tenant: Tenant}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $tenant = Tenant::query()->create([
                'name' => $data['workspace_name'],
                'slug' => $data['workspace_slug'],
                'settings' => [],
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'tenant_id' => $tenant->id,
            ]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            $user->assignRole('admin');

            return [
                'token' => $user->createToken('app')->plainTextToken,
                'user' => $user,
                'tenant' => $tenant,
            ];
        });
    }
}
