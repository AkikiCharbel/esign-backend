<?php

namespace App\Jobs;

use App\Mail\WorkspaceInvitationMail;
use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWorkspaceInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public WorkspaceInvitation $invitation) {}

    public function handle(): void
    {
        $this->invitation->load('tenant', 'inviter');

        Mail::to($this->invitation->email)
            ->send(new WorkspaceInvitationMail($this->invitation));
    }
}
