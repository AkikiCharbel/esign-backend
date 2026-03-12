<?php

namespace App\Jobs;

use App\Mail\SigningInvitationMail;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSigningInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function handle(): void
    {
        $this->submission->load('document');

        Mail::to($this->submission->recipient_email)
            ->send(new SigningInvitationMail($this->submission));
    }
}
