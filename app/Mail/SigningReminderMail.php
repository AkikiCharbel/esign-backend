<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SigningReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission)
    {
        $this->submission->loadMissing('document.template', 'document.creator');
    }

    public function envelope(): Envelope
    {
        /** @var Document $document */
        $document = $this->submission->document;

        $envelope = new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ),
            subject: "Reminder: {$document->name} is waiting for your signature",
        );

        if ($document->reply_to_email) {
            $envelope->replyTo[] = new Address(
                $document->reply_to_email,
                $document->reply_to_name ?? '',
            );
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signing-reminder',
            with: [
                'signing_url' => config('app.frontend_url').'/public/esign/'.$this->submission->token,
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
