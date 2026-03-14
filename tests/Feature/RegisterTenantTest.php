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

it('creates tenant, user, and assigns admin role', function () {
    $response = $this->postJson('/api/auth/register', [
        'workspace_name' => 'Acme Corp',
        'workspace_slug' => 'acme-corp',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'tenant_id', 'roles'],
            'tenant' => ['id', 'name', 'slug', 'settings', 'created_at'],
        ])
        ->assertJsonPath('user.name', 'John Doe')
        ->assertJsonPath('user.email', 'john@example.com')
        ->assertJsonPath('tenant.name', 'Acme Corp')
        ->assertJsonPath('tenant.slug', 'acme-corp')
        ->assertJsonMissingPath('user.password');

    $this->assertDatabaseHas('tenants', ['slug' => 'acme-corp']);
    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);

    $user = User::query()->where('email', 'john@example.com')->first();
    app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
    expect($user->hasRole('admin'))->toBeTrue();
});

it('rejects duplicate workspace slug', function () {
    Tenant::factory()->create(['slug' => 'taken-slug']);

    $response = $this->postJson('/api/auth/register', [
        'workspace_name' => 'Another Corp',
        'workspace_slug' => 'taken-slug',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['workspace_slug']);
});

it('rejects duplicate email', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'taken@example.com']);

    $response = $this->postJson('/api/auth/register', [
        'workspace_name' => 'New Corp',
        'workspace_slug' => 'new-corp',
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires password confirmation', function () {
    $response = $this->postJson('/api/auth/register', [
        'workspace_name' => 'Corp',
        'workspace_slug' => 'corp',
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects slug with uppercase and spaces', function (string $slug) {
    $response = $this->postJson('/api/auth/register', [
        'workspace_name' => 'Corp',
        'workspace_slug' => $slug,
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['workspace_slug']);
})->with([
    'uppercase' => 'Acme-Corp',
    'spaces' => 'acme corp',
    'special chars' => 'acme_corp!',
]);

it('rejects password shorter than 8 characters', function () {
    $response = $this->postJson('/api/auth/register', [
        'workspace_name' => 'Corp',
        'workspace_slug' => 'corp',
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
