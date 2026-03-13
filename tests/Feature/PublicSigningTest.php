<?php

use App\Jobs\GenerateSignedPdfJob;
use App\Models\Document;
use App\Models\Submission;
use App\Models\Template;
use App\Models\TemplateField;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->template = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);
    $this->document = Document::factory()->create([
        'tenant_id' => $this->tenant->id,
        'template_id' => $this->template->id,
        'created_by' => $this->user->id,
    ]);
});

// ─── Show ──────────────────────────────────────────────────────────

it('show returns 404 for invalid token', function () {
    $response = $this->getJson('/api/public/esign/invalid-token-123');

    $response->assertStatus(404)
        ->assertJsonPath('message', 'Submission not found.');
});

it('show returns 410 for expired submission', function () {
    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->getJson("/api/public/esign/{$submission->token}");

    $response->assertStatus(410);
});

it('show returns 410 for already signed submission', function () {
    $submission = Submission::factory()->signed()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
    ]);

    $response = $this->getJson("/api/public/esign/{$submission->token}");

    $response->assertStatus(410);
});

it('show returns 410 for processing submission', function () {
    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'processing',
    ]);

    $response = $this->getJson("/api/public/esign/{$submission->token}");

    $response->assertStatus(410);
});

it('show logs viewed event on first view', function () {
    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'viewed_at' => null,
        'status' => 'sent',
    ]);

    $response = $this->getJson("/api/public/esign/{$submission->token}");

    $response->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'submission_id' => $submission->id,
        'event' => 'viewed',
    ]);

    $submission->refresh();
    expect($submission->viewed_at)->not->toBeNull();
    expect($submission->status)->toBe('pending');
});

it('show does not log viewed event on subsequent views', function () {
    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'viewed_at' => now()->subHour(),
        'status' => 'pending',
    ]);

    $this->getJson("/api/public/esign/{$submission->token}");

    $this->assertDatabaseCount('audit_logs', 0);
});

it('show returns submission from another tenant via public token', function () {
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
    $submission = Submission::factory()->create([
        'tenant_id' => $otherTenant->id,
        'document_id' => $otherDocument->id,
        'viewed_at' => null,
        'status' => 'sent',
    ]);
    app()->instance('currentTenant', $this->tenant);

    $response = $this->getJson("/api/public/esign/{$submission->token}");

    $response->assertOk();
});

// ─── Update ────────────────────────────────────────────────────────

it('update saves field values and dispatches job', function () {
    Queue::fake();

    $field = TemplateField::factory()->create([
        'template_id' => $this->template->id,
        'type' => 'text',
        'required' => false,
    ]);

    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/public/esign/{$submission->token}", [
        'field_values' => [
            ['template_field_id' => $field->id, 'value' => 'John Doe'],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Signing complete');

    $this->assertDatabaseHas('submission_field_values', [
        'submission_id' => $submission->id,
        'template_field_id' => $field->id,
        'value' => 'John Doe',
    ]);

    $submission->refresh();
    expect($submission->status)->toBe('processing');

    Queue::assertPushed(GenerateSignedPdfJob::class);
});

it('update fails validation when required fields are missing', function () {
    $requiredField = TemplateField::factory()->create([
        'template_id' => $this->template->id,
        'type' => 'text',
        'required' => true,
        'label' => 'Full Name',
    ]);

    $optionalField = TemplateField::factory()->create([
        'template_id' => $this->template->id,
        'type' => 'text',
        'required' => false,
    ]);

    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/public/esign/{$submission->token}", [
        'field_values' => [
            ['template_field_id' => $optionalField->id, 'value' => 'test'],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Missing required fields.');
});

it('update rejects field IDs from another template', function () {
    $otherTemplate = Template::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_by' => $this->user->id,
    ]);
    $foreignField = TemplateField::factory()->create([
        'template_id' => $otherTemplate->id,
        'type' => 'text',
    ]);

    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'pending',
    ]);

    $response = $this->postJson("/api/public/esign/{$submission->token}", [
        'field_values' => [
            ['template_field_id' => $foreignField->id, 'value' => 'injected'],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Invalid field IDs.');
});

it('update returns 404 for invalid token', function () {
    $field = TemplateField::factory()->create(['template_id' => $this->template->id]);

    $response = $this->postJson('/api/public/esign/invalid-token', [
        'field_values' => [
            ['template_field_id' => $field->id, 'value' => 'test'],
        ],
    ]);

    $response->assertStatus(404);
});

it('update returns 410 for signed submission', function () {
    $field = TemplateField::factory()->create(['template_id' => $this->template->id]);

    $submission = Submission::factory()->signed()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
    ]);

    $response = $this->postJson("/api/public/esign/{$submission->token}", [
        'field_values' => [
            ['template_field_id' => $field->id, 'value' => 'test'],
        ],
    ]);

    $response->assertStatus(410);
});

it('update returns 410 for processing submission preventing double submit', function () {
    $field = TemplateField::factory()->create(['template_id' => $this->template->id]);

    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'processing',
    ]);

    $response = $this->postJson("/api/public/esign/{$submission->token}", [
        'field_values' => [
            ['template_field_id' => $field->id, 'value' => 'test'],
        ],
    ]);

    $response->assertStatus(410);
});
