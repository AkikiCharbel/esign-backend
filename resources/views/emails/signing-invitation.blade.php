@extends('emails.layout')

@section('content')
    {{-- Greeting --}}
    <p style="font-size: 16px; color: #111827; margin: 0 0 16px 0;">
        Hello {{ $submission->recipient_name }},
    </p>

    {{-- Main message --}}
    <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0 0 24px 0;">
        {{ $submission->document->creator->name ?? 'Someone' }} has sent you a document to review and sign.
    </p>

    {{-- Document card --}}
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 24px 0;">
        <div style="font-size: 15px; font-weight: 600; color: #111827;">
            &#128196; {{ $submission->document->name }}
        </div>
        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
            {{ $submission->document->template->page_count }} page(s)
        </div>
    </div>

    {{-- Custom message --}}
    @if(!empty($submission->document->custom_message))
        <div style="margin: 24px 0;">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                Message from sender:
            </div>
            <div style="background-color: #f9fafb; border-left: 3px solid #6366f1; padding: 12px 16px; font-size: 14px; color: #374151; line-height: 1.5;">
                {{ $submission->document->custom_message }}
            </div>
        </div>
    @endif

    {{-- Sign button --}}
    @include('emails.partials.button', ['url' => $signing_url, 'label' => 'Sign Document'])

    {{-- Expiry notice --}}
    @if($submission->expires_at)
        <p style="font-size: 13px; color: #6b7280; text-align: center; margin: 0 0 24px 0;">
            This signing link expires on {{ $submission->expires_at->format('F j, Y') }}.
        </p>
    @endif

    {{-- Security note --}}
    <div style="font-size: 12px; color: #9ca3af; background-color: #f9fafb; padding: 12px; border-radius: 6px; text-align: center; margin: 0 0 24px 0;">
        &#128274; This is a secure signing link. Do not share this email with others.
    </div>

    {{-- Link fallback --}}
    <p style="font-size: 13px; color: #6b7280; margin: 0 0 8px 0;">
        If the button above doesn't work, copy and paste this link into your browser:
    </p>
    <a href="{{ $signing_url }}" style="color: #6366f1; word-break: break-all; font-size: 13px;">{{ $signing_url }}</a>
@endsection
