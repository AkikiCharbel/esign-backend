<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SigningCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission)
    {
        $this->submission->loadMissing('document.creator');
    }

    public function envelope(): Envelope
    {
        /** @var Document $document */
        $document = $this->submission->document;
        /** @var User $creator */
        $creator = $document->creator;

        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ),
            to: [new Address($creator->email)],
            subject: "✅ {$this->submission->recipient_name} has signed {$document->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signing-completed',
            with: [
                'dashboard_url' => config('app.frontend_url').'/submissions/'.$this->submission->id,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
