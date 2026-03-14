<?php

namespace App\Jobs;

use App\Mail\SigningCompletedMail;
use App\Models\Submission;
use App\Services\SignedPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class GenerateSignedPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param  array<int, array{template_field_id: int, value: string|null}>  $fieldValues */
    public function __construct(
        public Submission $submission,
        public array $fieldValues,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public function handle(SignedPdfService $service): void
    {
        $tempPath = $service->generate($this->submission, $this->fieldValues);

        try {
            $this->submission->addMedia($tempPath)->toMediaCollection('signed-pdf');
        } finally {
            @unlink($tempPath);
        }

        $this->submission->update([
            'status' => 'signed',
            'signed_at' => now(),
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ]);

        $this->submission->auditLogs()->create([
            'event' => 'signed',
            'ip' => $this->ipAddress,
        ]);

        Mail::send(new SigningCompletedMail($this->submission));
    }
}
