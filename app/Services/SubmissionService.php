<?php

namespace App\Services;

use App\Jobs\SendSigningInvitationJob;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Submission;
use App\Models\SubmissionFieldValue;
use App\Models\Template;
use Illuminate\Support\Str;

class SubmissionService
{
    /** @param  array{recipient_name: string, recipient_email: string}  $recipientData */
    public function createAndSend(Document $document, array $recipientData, ?string $ip = null): Submission
    {
        $submission = Submission::query()->create([
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->id,
            'recipient_name' => $recipientData['recipient_name'],
            'recipient_email' => $recipientData['recipient_email'],
            'status' => 'sent',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
            'sent_at' => now(),
        ]);

        $document->loadMissing('template.fields');

        /** @var Template $template */
        $template = $document->template;
        $templateFields = $template->fields;

        foreach ($templateFields as $field) {
            SubmissionFieldValue::query()->create([
                'submission_id' => $submission->id,
                'template_field_id' => $field->id,
                'value' => null,
            ]);
        }

        SendSigningInvitationJob::dispatch($submission);

        AuditLog::query()->create([
            'submission_id' => $submission->id,
            'event' => 'sent',
            'metadata' => [
                'recipient_name' => $recipientData['recipient_name'],
                'recipient_email' => $recipientData['recipient_email'],
            ],
            'ip' => $ip,
        ]);

        return $submission;
    }
}
