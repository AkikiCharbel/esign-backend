@extends('emails.layout')

@section('content')
    {{-- Reminder badge --}}
    <div style="text-align: center; margin: 0 0 24px 0;">
        <span style="background-color: #fffbeb; border: 1px solid #fcd34d; color: #92400e; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 999px; display: inline-block;">
            &#9200; Reminder
        </span>
    </div>

    {{-- Greeting --}}
    <p style="font-size: 16px; color: #111827; margin: 0 0 16px 0;">
        Hello {{ $submission->recipient_name }},
    </p>

    {{-- Main message --}}
    <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0 0 24px 0;">
        This is a friendly reminder that {{ $submission->document->name }} is still waiting for your signature.
    </p>

    {{-- Days pending --}}
    @php
        $daysSince = $submission->sent_at->diffInDays(now());
    @endphp
    <p style="font-size: 13px; color: #6b7280; margin: 0 0 24px 0;">
        This document was sent {{ $daysSince }} day(s) ago and has not been signed yet.
    </p>

    {{-- Expiry warning --}}
    @if($submission->expires_at && $submission->expires_at->isFuture() && $submission->expires_at->diffInDays(now()) <= 3)
        <div style="background-color: #fef2f2; border: 1px solid #fca5a5; padding: 12px; border-radius: 6px; margin: 0 0 24px 0;">
            <p style="font-size: 13px; color: #991b1b; margin: 0;">
                &#9888;&#65039; This link expires in {{ $submission->expires_at->diffInDays(now()) }} day(s).
            </p>
        </div>
    @endif

    {{-- Sign button --}}
    @include('emails.partials.button', [
        'url' => $signing_url,
        'label' => 'Sign Document Now',
        'color' => '#d97706',
    ])

    {{-- Link fallback --}}
    <p style="font-size: 13px; color: #6b7280; margin: 0 0 8px 0;">
        If the button above doesn't work, copy and paste this link into your browser:
    </p>
    <a href="{{ $signing_url }}" style="color: #6366f1; word-break: break-all; font-size: 13px;">{{ $signing_url }}</a>
@endsection
