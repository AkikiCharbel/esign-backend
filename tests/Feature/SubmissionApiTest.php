<?php

use App\Jobs\CreateSubmissionJob;
use App\Jobs\SendSigningInvitationJob;
use App\Models\Document;
use App\Models\Submission;
use App\Models\Template;
use App\Models\TemplateField;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\PermissionRegistrar;

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

    app()->instance('currentTenant', $this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
});

// ─── Store ─────────────────────────────────────────────────────────

it('store creates submission and sets status to sent', function () {
    Queue::fake();

    TemplateField::factory()->count(2)->create([
        'template_id' => $this->template->id,
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/submissions', [
        'document_id' => $this->document->id,
        'recipient_name' => 'John Doe',
        'recipient_email' => 'john@example.com',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'sent')
        ->assertJsonPath('data.recipient_name', 'John Doe')
        ->assertJsonPath('data.recipient_email', 'john@example.com')
        ->assertJsonPath('data.document_id', $this->document->id);

    $this->assertDatabaseHas('submissions', [
        'document_id' => $this->document->id,
        'recipient_email' => 'john@example.com',
        'status' => 'sent',
    ]);

    $this->assertDatabaseCount('submission_field_values', 2);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'sent',
    ]);

    Queue::assertPushed(SendSigningInvitationJob::class);
});

it('store validates required fields', function () {
    $response = $this->actingAs($this->user)->postJson('/api/submissions', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['document_id', 'recipient_name', 'recipient_email']);
});

// ─── Index ─────────────────────────────────────────────────────────

it('index returns paginated submissions', function () {
    Submission::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/submissions');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('index filters by status', function () {
    Submission::factory()->count(2)->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'sent',
    ]);

    Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'signed',
        'signed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/submissions?status=sent');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('index searches by recipient name or email', function () {
    Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'recipient_name' => 'Alice Smith',
        'recipient_email' => 'alice@example.com',
    ]);

    Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'recipient_name' => 'Bob Jones',
        'recipient_email' => 'bob@example.com',
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/submissions?search=alice');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// ─── Show ──────────────────────────────────────────────────────────

it('show returns submission with document, field values, and audit logs', function () {
    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
    ]);

    $response = $this->actingAs($this->user)->getJson("/api/submissions/{$submission->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $submission->id)
        ->assertJsonStructure(['data' => ['document', 'field_values', 'audit_logs']]);
});

// ─── Resend ────────────────────────────────────────────────────────

it('resend dispatches signing invitation job', function () {
    Queue::fake();

    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/submissions/{$submission->id}/resend");

    $response->assertStatus(204);

    Queue::assertPushed(SendSigningInvitationJob::class);
});

it('resend rejects signed submission', function () {
    $submission = Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'status' => 'signed',
        'signed_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->postJson("/api/submissions/{$submission->id}/resend");

    $response->assertStatus(422);
});

// ─── Bulk ──────────────────────────────────────────────────────────

it('bulk sends correct count with recipients array', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)->postJson('/api/submissions/bulk', [
        'document_id' => $this->document->id,
        'recipients' => [
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
            ['name' => 'Charlie', 'email' => 'charlie@example.com'],
        ],
    ]);

    $response->assertStatus(202)
        ->assertJsonPath('queued', 3);

    Queue::assertPushed(CreateSubmissionJob::class, 3);
});

// ─── Customer submissions ──────────────────────────────────────────

it('customer endpoint returns submissions for email', function () {
    Submission::factory()->count(2)->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'recipient_email' => 'customer@example.com',
    ]);

    Submission::factory()->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
        'recipient_email' => 'other@example.com',
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/customers/customer@example.com/submissions');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// ─── Tenant isolation ──────────────────────────────────────────────

it('index does not return other tenant submissions', function () {
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
    Submission::factory()->create([
        'tenant_id' => $otherTenant->id,
        'document_id' => $otherDocument->id,
    ]);
    app()->instance('currentTenant', $this->tenant);

    Submission::factory()->count(2)->create([
        'tenant_id' => $this->tenant->id,
        'document_id' => $this->document->id,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/submissions');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});
