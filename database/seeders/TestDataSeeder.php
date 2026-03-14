<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentSigner;
use App\Models\Submission;
use App\Models\SubmissionFieldValue;
use App\Models\Template;
use App\Models\TemplateField;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;
use Spatie\Permission\PermissionRegistrar;
use TCPDF;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'tenant-1')->firstOrFail();
        $admin = User::where('email', 'admin@admin.com')->firstOrFail();

        // ── Staff user (already member) ────────────────────────────────
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $staff->assignRole('staff');

        // ── Pending invitation ─────────────────────────────────────────
        WorkspaceInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by' => $admin->id,
            'email' => 'viewer@test.com',
            'role' => 'viewer',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
        ]);

        // ── Generate a real 2-page PDF ─────────────────────────────────
        $pdfPath = storage_path('app/private/test-template.pdf');
        $this->createTestPdf($pdfPath);

        // ── Template (active, with PDF + fields) ───────────────────────
        $template = Template::create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
            'name' => 'Employment Agreement',
            'description' => 'Standard employment agreement with signature and date fields.',
            'page_count' => 2,
            'status' => 'active',
        ]);

        // Attach PDF via Spatie Media Library
        $template->addMedia($pdfPath)
            ->preservingOriginal()
            ->toMediaCollection('template-pdf');

        // Fields on page 1
        $sigField = TemplateField::create([
            'template_id' => $template->id,
            'page' => 1,
            'type' => 'signature',
            'label' => 'Signature',
            'required' => true,
            'x' => 10,
            'y' => 75,
            'width' => 30,
            'height' => 8,
            'font_size' => 14,
            'multiline' => false,
            'options' => null,
            'signer_role' => 'signer',
            'order' => 1,
        ]);

        $nameField = TemplateField::create([
            'template_id' => $template->id,
            'page' => 1,
            'type' => 'text',
            'label' => 'Full Name',
            'required' => true,
            'x' => 10,
            'y' => 60,
            'width' => 40,
            'height' => 5,
            'font_size' => 14,
            'multiline' => false,
            'options' => null,
            'signer_role' => 'signer',
            'order' => 2,
        ]);

        $dateField = TemplateField::create([
            'template_id' => $template->id,
            'page' => 1,
            'type' => 'date',
            'label' => 'Date Signed',
            'required' => true,
            'x' => 60,
            'y' => 60,
            'width' => 20,
            'height' => 5,
            'font_size' => 14,
            'multiline' => false,
            'options' => null,
            'signer_role' => 'signer',
            'order' => 3,
        ]);

        // Fields on page 2
        $checkboxField = TemplateField::create([
            'template_id' => $template->id,
            'page' => 2,
            'type' => 'checkbox',
            'label' => 'I agree to the terms and conditions',
            'required' => true,
            'x' => 10,
            'y' => 75,
            'width' => 5,
            'height' => 5,
            'font_size' => 14,
            'multiline' => false,
            'options' => null,
            'signer_role' => 'signer',
            'order' => 4,
        ]);

        $initialsField = TemplateField::create([
            'template_id' => $template->id,
            'page' => 2,
            'type' => 'initials',
            'label' => 'Initials',
            'required' => false,
            'x' => 10,
            'y' => 85,
            'width' => 15,
            'height' => 6,
            'font_size' => 14,
            'multiline' => false,
            'options' => null,
            'signer_role' => 'signer',
            'order' => 5,
        ]);

        $allFields = [$sigField, $nameField, $dateField, $checkboxField, $initialsField];

        // ── Second template (draft, no PDF) ────────────────────────────
        Template::create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
            'name' => 'NDA Template',
            'description' => 'Non-disclosure agreement (draft — no PDF uploaded yet).',
            'page_count' => 0,
            'status' => 'draft',
        ]);

        // ── Document with signers ──────────────────────────────────────
        $document = Document::create([
            'tenant_id' => $tenant->id,
            'template_id' => $template->id,
            'created_by' => $admin->id,
            'name' => 'John Doe — Employment Agreement',
            'custom_message' => 'Please review and sign this employment agreement at your earliest convenience.',
            'reply_to_email' => 'admin@admin.com',
            'reply_to_name' => 'Admin',
            'has_attachments' => false,
        ]);

        DocumentSigner::create([
            'document_id' => $document->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'signer',
            'sign_order' => 1,
        ]);

        // ── Submission 1: "sent" (pending signing) ─────────────────────
        $sub1 = Submission::create([
            'tenant_id' => $tenant->id,
            'document_id' => $document->id,
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'status' => 'sent',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
            'sent_at' => now()->subHours(2),
        ]);

        foreach ($allFields as $field) {
            SubmissionFieldValue::create([
                'submission_id' => $sub1->id,
                'template_field_id' => $field->id,
                'value' => null,
            ]);
        }

        AuditLog::create([
            'submission_id' => $sub1->id,
            'event' => 'sent',
            'metadata' => ['recipient_name' => 'John Doe', 'recipient_email' => 'john@example.com'],
            'ip' => '127.0.0.1',
        ]);

        // ── Submission 2: "signed" ─────────────────────────────────────
        $doc2 = Document::create([
            'tenant_id' => $tenant->id,
            'template_id' => $template->id,
            'created_by' => $admin->id,
            'name' => 'Jane Smith — Employment Agreement',
            'custom_message' => 'Hi Jane, please sign this when you get a chance.',
            'has_attachments' => false,
        ]);

        DocumentSigner::create([
            'document_id' => $doc2->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'role' => 'signer',
            'sign_order' => 1,
        ]);

        $sub2 = Submission::create([
            'tenant_id' => $tenant->id,
            'document_id' => $doc2->id,
            'recipient_name' => 'Jane Smith',
            'recipient_email' => 'jane@example.com',
            'status' => 'signed',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
            'sent_at' => now()->subDays(1),
            'viewed_at' => now()->subDays(1)->addHours(1),
            'signed_at' => now()->subHours(3),
        ]);

        $fieldValues = [
            $sigField->id => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            $nameField->id => 'Jane Smith',
            $dateField->id => now()->subHours(3)->toDateString(),
            $checkboxField->id => 'true',
            $initialsField->id => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ];

        foreach ($fieldValues as $fieldId => $value) {
            SubmissionFieldValue::create([
                'submission_id' => $sub2->id,
                'template_field_id' => $fieldId,
                'value' => $value,
            ]);
        }

        AuditLog::create([
            'submission_id' => $sub2->id,
            'event' => 'sent',
            'metadata' => ['recipient_name' => 'Jane Smith', 'recipient_email' => 'jane@example.com'],
            'ip' => '127.0.0.1',
        ]);
        AuditLog::create([
            'submission_id' => $sub2->id,
            'event' => 'viewed',
            'metadata' => null,
            'ip' => '192.168.1.50',
        ]);
        AuditLog::create([
            'submission_id' => $sub2->id,
            'event' => 'signed',
            'metadata' => null,
            'ip' => '192.168.1.50',
        ]);

        // ── Submission 3: "expired" ────────────────────────────────────
        $sub3 = Submission::create([
            'tenant_id' => $tenant->id,
            'document_id' => $document->id,
            'recipient_name' => 'Bob Wilson',
            'recipient_email' => 'bob@example.com',
            'status' => 'sent',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->subDay(),
            'sent_at' => now()->subDays(8),
        ]);

        foreach ($allFields as $field) {
            SubmissionFieldValue::create([
                'submission_id' => $sub3->id,
                'template_field_id' => $field->id,
                'value' => null,
            ]);
        }

        AuditLog::create([
            'submission_id' => $sub3->id,
            'event' => 'sent',
            'metadata' => ['recipient_name' => 'Bob Wilson', 'recipient_email' => 'bob@example.com'],
            'ip' => '127.0.0.1',
        ]);

        // ── Submission 4: another "sent" (for bulk send demo) ──────────
        $sub4 = Submission::create([
            'tenant_id' => $tenant->id,
            'document_id' => $document->id,
            'recipient_name' => 'Alice Johnson',
            'recipient_email' => 'alice@example.com',
            'status' => 'sent',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
            'sent_at' => now()->subMinutes(30),
        ]);

        foreach ($allFields as $field) {
            SubmissionFieldValue::create([
                'submission_id' => $sub4->id,
                'template_field_id' => $field->id,
                'value' => null,
            ]);
        }

        AuditLog::create([
            'submission_id' => $sub4->id,
            'event' => 'sent',
            'metadata' => ['recipient_name' => 'Alice Johnson', 'recipient_email' => 'alice@example.com'],
            'ip' => '127.0.0.1',
        ]);

        // ── Second tenant (for tenant isolation test) ──────────────────
        $tenant2 = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $admin2 = User::create([
            'name' => 'Acme Admin',
            'email' => 'admin@acme.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant2->id,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant2->id);
        $admin2->assignRole('admin');

        $this->command->info('Test data seeded:');
        $this->command->info("  Tenant 1: {$tenant->name} (admin@admin.com / password)");
        $this->command->info("  Staff user: staff@test.com / password");
        $this->command->info("  Pending invitation for: viewer@test.com");
        $this->command->info("  Template: {$template->name} (active, 2 pages, 5 fields)");
        $this->command->info("  Documents: 2");
        $this->command->info("  Submissions: 4 (1 sent, 1 signed, 1 expired, 1 sent)");
        $this->command->info("  Tenant 2: {$tenant2->name} (admin@acme.com / password)");
        $this->command->info("  Signing link (sent): http://localhost:5173/public/esign/{$sub1->token}");
        $this->command->info("  Signing link (expired): http://localhost:5173/public/esign/{$sub3->token}");
        $this->command->info("  Portal link: http://localhost:5173/portal/{$sub2->token}");
    }

    private function createTestPdf(string $path): void
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Page 1
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Employment Agreement', 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 8, "This Employment Agreement (\"Agreement\") is entered into between the Company and the Employee named below.\n\nThe Employee agrees to perform duties as assigned and to comply with all company policies and procedures.\n\nCompensation, benefits, and other terms of employment are as discussed and agreed upon separately.", 0, 'L');
        $pdf->Ln(15);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, 'Full Name: ____________________________          Date: ______________', 0, 1, 'L');
        $pdf->Ln(10);
        $pdf->Cell(0, 8, 'Signature: ____________________________', 0, 1, 'L');

        // Page 2
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 15, 'Terms and Conditions', 0, 1, 'C');
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 7, "1. The Employee shall devote full working time to the Company.\n\n2. All intellectual property created during employment belongs to the Company.\n\n3. The Employee agrees to maintain confidentiality of proprietary information.\n\n4. This Agreement may be terminated by either party with 30 days written notice.\n\n5. This Agreement shall be governed by the laws of the applicable jurisdiction.", 0, 'L');
        $pdf->Ln(20);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, '[ ] I agree to the terms and conditions above', 0, 1, 'L');
        $pdf->Ln(10);
        $pdf->Cell(0, 8, 'Initials: __________', 0, 1, 'L');

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->Output($path, 'F');
    }
}
