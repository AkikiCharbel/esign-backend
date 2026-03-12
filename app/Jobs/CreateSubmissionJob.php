<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\SubmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param  array{recipient_name: string, recipient_email: string}  $recipientData */
    public function __construct(
        public Document $document,
        public array $recipientData,
        public ?string $ip = null,
    ) {}

    public function handle(SubmissionService $submissionService): void
    {
        $submissionService->createAndSend($this->document, $this->recipientData, $this->ip);
    }
}
