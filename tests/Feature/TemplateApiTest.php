<?php

use App\Models\Template;
use App\Models\TemplateField;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

    app()->instance('currentTenant', $this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
});

// ─── Template CRUD ───────────────────────────────────────────────────

it('index returns only current tenant templates', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    Template::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);

    app()->instance('currentTenant', $otherTenant);
    Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->getJson('/api/templates');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('store creates template', function () {
    $response = $this->actingAs($this->user)->postJson('/api/templates', [
        'name' => 'NDA Agreement',
        'description' => 'Standard NDA template',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'NDA Agreement')
        ->assertJsonPath('data.description', 'Standard NDA template')
        ->assertJsonPath('data.created_by', $this->user->id)
        ->assertJsonPath('data.tenant_id', $this->tenant->id);

    $this->assertDatabaseHas('templates', [
        'name' => 'NDA Agreement',
        'tenant_id' => $this->tenant->id,
    ]);
});

it('show returns template with fields', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);
    TemplateField::factory()->count(2)->create(['template_id' => $template->id]);

    $response = $this->actingAs($this->user)->getJson("/api/templates/{$template->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $template->id)
        ->assertJsonCount(2, 'data.fields');
});

it('update changes name and status', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
        'name' => 'Old Name',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)->patchJson("/api/templates/{$template->id}", [
        'name' => 'New Name',
        'status' => 'active',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.status', 'active');
});

it('destroy deletes template', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->deleteJson("/api/templates/{$template->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('templates', ['id' => $template->id]);
});

// ─── Tenant isolation: show / update / destroy ──────────────────────

it('show returns 404 for another tenant template', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $template = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->getJson("/api/templates/{$template->id}");

    $response->assertNotFound();
});

it('update returns 404 for another tenant template', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $template = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->patchJson("/api/templates/{$template->id}", [
        'name' => 'Hacked',
    ]);

    $response->assertNotFound();
});

it('destroy returns 404 for another tenant template', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $template = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->deleteJson("/api/templates/{$template->id}");

    $response->assertNotFound();
});

// ─── Template PDF upload ────────────────────────────────────────────

it('uploads pdf and extracts page count', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
        'page_count' => 0,
    ]);

    $tempPath = sys_get_temp_dir().'/test_'.uniqid().'.pdf';
    copy(base_path('tests/fixtures/sample.pdf'), $tempPath);

    $pdf = new UploadedFile(
        path: $tempPath,
        originalName: 'document.pdf',
        mimeType: 'application/pdf',
        test: true,
    );

    $response = $this->actingAs($this->user)->postJson("/api/templates/{$template->id}/pdf", [
        'pdf' => $pdf,
    ]);

    $response->assertOk()
        ->assertJsonPath('page_count', 2)
        ->assertJsonStructure(['pdf_url', 'page_count']);

    $template->refresh();
    expect($template->page_count)->toBe(2);
});

// ─── Template Field CRUD ────────────────────────────────────────────

it('creates a field on a template', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/templates/{$template->id}/fields", [
        'page' => 1,
        'type' => 'signature',
        'label' => 'Client Signature',
        'required' => true,
        'x' => 10.0,
        'y' => 80.0,
        'width' => 30.0,
        'height' => 10.0,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.label', 'Client Signature')
        ->assertJsonPath('data.type', 'signature');

    $this->assertDatabaseHas('template_fields', [
        'template_id' => $template->id,
        'label' => 'Client Signature',
    ]);
});

it('updates a template field', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);
    $field = TemplateField::factory()->create([
        'template_id' => $template->id,
        'label' => 'Old Label',
    ]);

    $response = $this->actingAs($this->user)->patchJson("/api/templates/{$template->id}/fields/{$field->id}", [
        'label' => 'New Label',
        'required' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.label', 'New Label')
        ->assertJsonPath('data.required', true);
});

it('deletes a template field', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);
    $field = TemplateField::factory()->create(['template_id' => $template->id]);

    $response = $this->actingAs($this->user)->deleteJson("/api/templates/{$template->id}/fields/{$field->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('template_fields', ['id' => $field->id]);
});

it('returns 404 when updating a field via another tenant template', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    $otherField = TemplateField::factory()->create(['template_id' => $otherTemplate->id]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->patchJson("/api/templates/{$otherTemplate->id}/fields/{$otherField->id}", [
        'label' => 'Hacked',
    ]);

    $response->assertNotFound();
});

it('returns 404 when deleting a field via another tenant template', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    $otherField = TemplateField::factory()->create(['template_id' => $otherTemplate->id]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->deleteJson("/api/templates/{$otherTemplate->id}/fields/{$otherField->id}");

    $response->assertNotFound();
});

// ─── Bulk sync ──────────────────────────────────────────────────────

it('bulk sync replaces all fields', function () {
    $template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);
    TemplateField::factory()->count(3)->create(['template_id' => $template->id]);

    $newFields = [
        [
            'page' => 1,
            'type' => 'signature',
            'label' => 'Signature Field',
            'required' => true,
            'x' => 10.5,
            'y' => 20.0,
            'width' => 30.0,
            'height' => 10.0,
        ],
        [
            'page' => 1,
            'type' => 'text',
            'label' => 'Name Field',
            'required' => true,
            'x' => 50.0,
            'y' => 30.0,
            'width' => 40.0,
            'height' => 5.0,
        ],
    ];

    $response = $this->actingAs($this->user)->putJson("/api/templates/{$template->id}/fields/sync", [
        'fields' => $newFields,
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    expect($template->fields()->count())->toBe(2);
});
