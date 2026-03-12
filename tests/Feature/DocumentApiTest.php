<?php

use App\Models\Document;
use App\Models\DocumentSigner;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);

    app()->instance('currentTenant', $this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
});

// ─── Document CRUD ──────────────────────────────────────────────────

it('index returns only current tenant documents', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    Document::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    Document::factory()->create([
        'tenant_id' => $otherTenant->id,
        'template_id' => $otherTemplate->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->getJson('/api/documents');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('store creates document linked to template', function () {
    $response = $this->actingAs($this->user)->postJson('/api/documents', [
        'template_id' => $this->template->id,
        'name' => 'Q1 NDA',
        'custom_message' => 'Please sign this NDA',
        'reply_to_email' => 'hr@example.com',
        'reply_to_name' => 'HR Department',
        'has_attachments' => true,
        'attachment_instructions' => 'Attach your ID',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Q1 NDA')
        ->assertJsonPath('data.template_id', $this->template->id)
        ->assertJsonPath('data.created_by', $this->user->id)
        ->assertJsonPath('data.tenant_id', $this->tenant->id)
        ->assertJsonPath('data.custom_message', 'Please sign this NDA')
        ->assertJsonPath('data.has_attachments', true);

    $this->assertDatabaseHas('documents', [
        'name' => 'Q1 NDA',
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
    ]);
});

it('store validates required fields', function () {
    $response = $this->actingAs($this->user)->postJson('/api/documents', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['template_id', 'name']);
});

it('store rejects another tenant template_id', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->postJson('/api/documents', [
        'template_id' => $otherTemplate->id,
        'name' => 'Sneaky Document',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['template_id']);
});

it('show returns document with template and signers', function () {
    $document = Document::factory()->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);

    DocumentSigner::factory()->count(2)->create([
        'document_id' => $document->id,
    ]);

    $response = $this->actingAs($this->user)->getJson("/api/documents/{$document->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $document->id)
        ->assertJsonCount(2, 'data.signers')
        ->assertJsonStructure(['data' => ['template']]);
});

it('destroy deletes document', function () {
    $document = Document::factory()->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->deleteJson("/api/documents/{$document->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

// ─── Tenant isolation ───────────────────────────────────────────────

it('show returns 404 for another tenant document', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    $document = Document::factory()->create([
        'tenant_id' => $otherTenant->id,
        'template_id' => $otherTemplate->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->getJson("/api/documents/{$document->id}");

    $response->assertNotFound();
});

it('destroy returns 404 for another tenant document', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    $document = Document::factory()->create([
        'tenant_id' => $otherTenant->id,
        'template_id' => $otherTemplate->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->deleteJson("/api/documents/{$document->id}");

    $response->assertNotFound();
});

// ─── Document Signers ───────────────────────────────────────────────

it('adds a signer to a document', function () {
    $document = Document::factory()->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/documents/{$document->id}/signers", [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'signer',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'John Doe')
        ->assertJsonPath('data.email', 'john@example.com')
        ->assertJsonPath('data.role', 'signer')
        ->assertJsonPath('data.sign_order', 1);

    $this->assertDatabaseHas('document_signers', [
        'document_id' => $document->id,
        'email' => 'john@example.com',
    ]);
});

it('auto-increments sign_order when not provided', function () {
    $document = Document::factory()->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);

    DocumentSigner::factory()->create([
        'document_id' => $document->id,
        'sign_order' => 1,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/documents/{$document->id}/signers", [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'signer',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.sign_order', 2);
});

it('uses provided sign_order', function () {
    $document = Document::factory()->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/documents/{$document->id}/signers", [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'signer',
        'sign_order' => 5,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.sign_order', 5);
});

it('removes a signer from a document', function () {
    $document = Document::factory()->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);

    $signer = DocumentSigner::factory()->create([
        'document_id' => $document->id,
    ]);

    $response = $this->actingAs($this->user)->deleteJson("/api/documents/{$document->id}/signers/{$signer->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('document_signers', ['id' => $signer->id]);
});

it('cannot add signer to another tenant document', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    $otherDocument = Document::factory()->create([
        'tenant_id' => $otherTenant->id,
        'template_id' => $otherTemplate->id,
        'created_by' => $otherUser->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->postJson("/api/documents/{$otherDocument->id}/signers", [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'signer',
    ]);

    $response->assertNotFound();
});

it('cannot remove a signer belonging to another tenant document', function () {
    $otherTenant = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    app()->instance('currentTenant', $otherTenant);
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $otherTenant->id,
        'created_by' => $otherUser->id,
    ]);
    $otherDocument = Document::factory()->create([
        'tenant_id' => $otherTenant->id,
        'template_id' => $otherTemplate->id,
        'created_by' => $otherUser->id,
    ]);
    $otherSigner = DocumentSigner::factory()->create([
        'document_id' => $otherDocument->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->actingAs($this->user)->deleteJson("/api/documents/{$otherDocument->id}/signers/{$otherSigner->id}");

    $response->assertNotFound();
    $this->assertDatabaseHas('document_signers', ['id' => $otherSigner->id]);
});
