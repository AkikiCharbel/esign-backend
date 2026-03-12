<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SigningInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: 'You have a document to sign',
        );

        $document = $this->submission->document;

        if ($document?->reply_to_email) {
            $envelope->replyTo(
                $document->reply_to_email,
                $document->reply_to_name,
            );
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.signing-invitation',
        );
    }
}
