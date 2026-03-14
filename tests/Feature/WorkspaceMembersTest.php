<?php

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('staff', 'web');
    Role::findOrCreate('viewer', 'web');
});

function createTenantAdmin(?Tenant $tenant = null): User
{
    $tenant ??= Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole('admin');

    return $user;
}

function createTenantMember(?Tenant $tenant = null, string $role = 'staff'): User
{
    $tenant ??= Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole($role);

    return $user;
}

it('index returns only members of current tenant', function () {
    $admin = createTenantAdmin();
    $tenant = Tenant::find($admin->tenant_id);

    createTenantMember($tenant);
    createTenantMember($tenant, 'viewer');

    // Other tenant member — should not appear
    createTenantMember();

    $response = $this->actingAs($admin)->getJson('/api/workspace/members');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role', 'joined_at', 'avatar_url']]]);
});

it('admin can change member role', function () {
    $admin = createTenantAdmin();
    $member = createTenantMember(Tenant::find($admin->tenant_id));

    $response = $this->actingAs($admin)->patchJson("/api/workspace/members/{$member->id}", [
        'role' => 'viewer',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.role', 'viewer');

    app(PermissionRegistrar::class)->setPermissionsTeamId($admin->tenant_id);
    expect($member->fresh()->hasRole('viewer'))->toBeTrue();
});

it('cannot change own role', function () {
    $admin = createTenantAdmin();

    $response = $this->actingAs($admin)->patchJson("/api/workspace/members/{$admin->id}", [
        'role' => 'staff',
    ]);

    $response->assertUnprocessable();
});

it('cannot remove self', function () {
    $admin = createTenantAdmin();

    $response = $this->actingAs($admin)->deleteJson("/api/workspace/members/{$admin->id}");

    $response->assertUnprocessable();
});

it('cannot demote the last admin via update', function () {
    $tenant = Tenant::factory()->create();
    $admin1 = createTenantAdmin($tenant);
    $admin2 = createTenantAdmin($tenant);

    // Two admins: demoting one should succeed
    $this->actingAs($admin1)->patchJson("/api/workspace/members/{$admin2->id}", [
        'role' => 'staff',
    ])->assertSuccessful();

    // admin1 is now the sole admin. They cannot demote themselves (self-check).
    // The last-admin guard on update() is defense-in-depth for concurrent requests
    // and cannot be triggered in a single-threaded test (caller must be admin → count >= 2).
    $this->actingAs($admin1)->patchJson("/api/workspace/members/{$admin1->id}", [
        'role' => 'staff',
    ])->assertUnprocessable();
});

it('demote guard allows demotion when multiple admins exist', function () {
    $tenant = Tenant::factory()->create();
    $admin1 = createTenantAdmin($tenant);
    $admin2 = createTenantAdmin($tenant);
    $admin3 = createTenantAdmin($tenant);

    // Three admins: demoting one leaves two, should succeed
    $this->actingAs($admin1)->patchJson("/api/workspace/members/{$admin2->id}", [
        'role' => 'viewer',
    ])->assertSuccessful();

    // Two admins left: demoting one leaves one, should still succeed
    $this->actingAs($admin1)->patchJson("/api/workspace/members/{$admin3->id}", [
        'role' => 'staff',
    ])->assertSuccessful();

    // admin1 is now the sole admin
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $adminCount = User::query()->where('tenant_id', $tenant->id)->role('admin')->count();
    expect($adminCount)->toBe(1);
});

it('cannot remove the last admin via destroy', function () {
    $tenant = Tenant::factory()->create();
    $admin = createTenantAdmin($tenant);
    $staff = createTenantMember($tenant);

    // admin removes staff — should work
    $this->actingAs($admin)->deleteJson("/api/workspace/members/{$staff->id}")
        ->assertNoContent();

    // admin is sole admin and can't remove self (self-check fires first)
    $this->actingAs($admin)->deleteJson("/api/workspace/members/{$admin->id}")
        ->assertUnprocessable();
});

it('admin can remove another member from workspace', function () {
    $tenant = Tenant::factory()->create();
    $admin = createTenantAdmin($tenant);
    $member = createTenantMember($tenant);

    $response = $this->actingAs($admin)->deleteJson("/api/workspace/members/{$member->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});

it('cannot access members of another tenant', function () {
    $admin = createTenantAdmin();
    $otherTenantMember = createTenantMember();

    $response = $this->actingAs($admin)->patchJson("/api/workspace/members/{$otherTenantMember->id}", [
        'role' => 'viewer',
    ]);

    $response->assertForbidden();
});

it('non-admin cannot change roles', function () {
    $tenant = Tenant::factory()->create();
    $staff = createTenantMember($tenant);
    $member = createTenantMember($tenant, 'viewer');

    $response = $this->actingAs($staff)->patchJson("/api/workspace/members/{$member->id}", [
        'role' => 'admin',
    ]);

    $response->assertForbidden();
});

it('non-admin cannot remove members', function () {
    $tenant = Tenant::factory()->create();
    $staff = createTenantMember($tenant);
    $member = createTenantMember($tenant, 'viewer');

    $response = $this->actingAs($staff)->deleteJson("/api/workspace/members/{$member->id}");

    $response->assertForbidden();
});

it('non-admin cannot list members', function () {
    $staff = createTenantMember();

    $response = $this->actingAs($staff)->getJson('/api/workspace/members');

    $response->assertForbidden();
});
