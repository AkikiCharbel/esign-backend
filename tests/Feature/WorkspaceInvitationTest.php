<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Mail::fake();
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('staff', 'web');
    Role::findOrCreate('viewer', 'web');
});

function createAdminUser(): User
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole('admin');

    return $user;
}

function createStaffUser(?Tenant $tenant = null): User
{
    $tenant ??= Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole('staff');

    return $user;
}

it('admin can invite by email', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->postJson('/api/workspace/invitations', [
        'email' => 'newuser@example.com',
        'role' => 'staff',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'newuser@example.com')
        ->assertJsonPath('data.role', 'staff');

    $this->assertDatabaseHas('workspace_invitations', [
        'tenant_id' => $admin->tenant_id,
        'invited_by' => $admin->id,
        'email' => 'newuser@example.com',
        'role' => 'staff',
    ]);
});

it('cannot invite existing member', function () {
    $admin = createAdminUser();
    User::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'email' => 'existing@example.com',
    ]);

    $response = $this->actingAs($admin)->postJson('/api/workspace/invitations', [
        'email' => 'existing@example.com',
        'role' => 'staff',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('cannot invite with duplicate pending invitation', function () {
    $admin = createAdminUser();
    WorkspaceInvitation::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'invited_by' => $admin->id,
        'email' => 'pending@example.com',
    ]);

    $response = $this->actingAs($admin)->postJson('/api/workspace/invitations', [
        'email' => 'pending@example.com',
        'role' => 'staff',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('accept creates user with correct role', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $invitation = WorkspaceInvitation::factory()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $admin->id,
        'email' => 'invited@example.com',
        'role' => 'viewer',
    ]);

    $response = $this->postJson('/api/auth/accept-invitation', [
        'token' => $invitation->token,
        'name' => 'Invited User',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'tenant_id', 'roles'],
            'tenant' => ['id', 'name', 'slug'],
        ])
        ->assertJsonPath('user.email', 'invited@example.com')
        ->assertJsonPath('user.name', 'Invited User')
        ->assertJsonPath('tenant.id', $tenant->id);

    $user = User::query()->where('email', 'invited@example.com')->first();
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    expect($user->hasRole('viewer'))->toBeTrue();

    $invitation->refresh();
    expect($invitation->accepted_at)->not->toBeNull();
});

it('expired token returns 410', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $invitation = WorkspaceInvitation::factory()->expired()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $admin->id,
        'email' => 'expired@example.com',
    ]);

    $response = $this->postJson('/api/auth/accept-invitation', [
        'token' => $invitation->token,
        'name' => 'Expired User',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(410);
});

it('already accepted token returns 409', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $invitation = WorkspaceInvitation::factory()->accepted()->create([
        'tenant_id' => $tenant->id,
        'invited_by' => $admin->id,
        'email' => 'accepted@example.com',
    ]);

    $response = $this->postJson('/api/auth/accept-invitation', [
        'token' => $invitation->token,
        'name' => 'Already Accepted',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(409);
});

it('non-admin cannot invite', function () {
    $staff = createStaffUser();

    $response = $this->actingAs($staff)->postJson('/api/workspace/invitations', [
        'email' => 'someone@example.com',
        'role' => 'staff',
    ]);

    $response->assertForbidden();
});

it('admin can list pending invitations', function () {
    $admin = createAdminUser();

    WorkspaceInvitation::factory()->count(2)->create([
        'tenant_id' => $admin->tenant_id,
        'invited_by' => $admin->id,
    ]);

    // Expired and accepted should not appear
    WorkspaceInvitation::factory()->expired()->create([
        'tenant_id' => $admin->tenant_id,
        'invited_by' => $admin->id,
    ]);
    WorkspaceInvitation::factory()->accepted()->create([
        'tenant_id' => $admin->tenant_id,
        'invited_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->getJson('/api/workspace/invitations');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('non-admin cannot list invitations', function () {
    $staff = createStaffUser();

    $response = $this->actingAs($staff)->getJson('/api/workspace/invitations');

    $response->assertForbidden();
});

it('admin can cancel invitation', function () {
    $admin = createAdminUser();
    $invitation = WorkspaceInvitation::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'invited_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->deleteJson("/api/workspace/invitations/{$invitation->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('workspace_invitations', ['id' => $invitation->id]);
});

it('non-admin cannot cancel invitation', function () {
    $staff = createStaffUser();
    $invitation = WorkspaceInvitation::factory()->create([
        'tenant_id' => $staff->tenant_id,
        'invited_by' => $staff->id,
    ]);

    $response = $this->actingAs($staff)->deleteJson("/api/workspace/invitations/{$invitation->id}");

    $response->assertForbidden();
});

it('admin cannot cancel invitation from another tenant', function () {
    $admin = createAdminUser();
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
    $invitation = WorkspaceInvitation::factory()->create([
        'tenant_id' => $otherTenant->id,
        'invited_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($admin)->deleteJson("/api/workspace/invitations/{$invitation->id}");

    $response->assertForbidden();
});
