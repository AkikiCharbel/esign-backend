<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('staff', 'web');
    Role::findOrCreate('viewer', 'web');
});

function createSettingsAdmin(): User
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole('admin');

    return $user;
}

function createSettingsViewer(?Tenant $tenant = null): User
{
    $tenant ??= Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole('viewer');

    return $user;
}

it('admin can view workspace', function () {
    $admin = createSettingsAdmin();

    $response = $this->actingAs($admin)->getJson('/api/workspace');

    $response->assertOk()
        ->assertJsonPath('data.id', $admin->tenant_id)
        ->assertJsonPath('data.name', $admin->tenant->name)
        ->assertJsonPath('data.slug', $admin->tenant->slug);
});

it('admin can update workspace name', function () {
    $admin = createSettingsAdmin();

    $response = $this->actingAs($admin)->patchJson('/api/workspace', [
        'name' => 'Updated Company',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Company');

    $this->assertDatabaseHas('tenants', [
        'id' => $admin->tenant_id,
        'name' => 'Updated Company',
    ]);
});

it('cannot set duplicate slug', function () {
    $admin = createSettingsAdmin();
    Tenant::factory()->create(['slug' => 'taken-slug']);

    $response = $this->actingAs($admin)->patchJson('/api/workspace', [
        'slug' => 'taken-slug',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

it('can update slug to same value', function () {
    $admin = createSettingsAdmin();
    $currentSlug = $admin->tenant->slug;

    $response = $this->actingAs($admin)->patchJson('/api/workspace', [
        'slug' => $currentSlug,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.slug', $currentSlug);
});

it('can update settings', function () {
    $admin = createSettingsAdmin();

    $response = $this->actingAs($admin)->patchJson('/api/workspace', [
        'settings' => [
            'timezone' => 'America/New_York',
            'date_format' => 'd/m/Y',
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.settings.timezone', 'America/New_York')
        ->assertJsonPath('data.settings.date_format', 'd/m/Y');
});

it('non-admin cannot update workspace', function () {
    $admin = createSettingsAdmin();
    $viewer = createSettingsViewer($admin->tenant);

    $response = $this->actingAs($viewer)->patchJson('/api/workspace', [
        'name' => 'Hacked Name',
    ]);

    $response->assertForbidden();
});

it('logo upload stores in media library', function () {
    Storage::fake('public');
    $admin = createSettingsAdmin();

    $response = $this->actingAs($admin)->postJson('/api/workspace/logo', [
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
    ]);

    $response->assertOk()
        ->assertJsonStructure(['logo_url']);

    expect($admin->tenant->fresh()->getFirstMedia('logo'))->not->toBeNull();
});

it('admin can delete logo', function () {
    Storage::fake('public');
    $admin = createSettingsAdmin();
    $admin->tenant->addMedia(UploadedFile::fake()->image('logo.png'))
        ->toMediaCollection('logo');

    $response = $this->actingAs($admin)->deleteJson('/api/workspace/logo');

    $response->assertNoContent();
    expect($admin->tenant->fresh()->getFirstMedia('logo'))->toBeNull();
});

it('non-admin cannot upload logo', function () {
    $admin = createSettingsAdmin();
    $viewer = createSettingsViewer($admin->tenant);

    $response = $this->actingAs($viewer)->postJson('/api/workspace/logo', [
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
    ]);

    $response->assertForbidden();
});

it('non-admin cannot delete logo', function () {
    $admin = createSettingsAdmin();
    $viewer = createSettingsViewer($admin->tenant);

    $response = $this->actingAs($viewer)->deleteJson('/api/workspace/logo');

    $response->assertForbidden();
});
