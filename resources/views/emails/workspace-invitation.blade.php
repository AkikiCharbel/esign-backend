@extends('emails.layout')

@section('content')
    <p style="font-size: 16px; color: #111827; margin: 0 0 16px 0;">
        Hello,
    </p>

    <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0 0 24px 0;">
        {{ $invitation->inviter->name }} has invited you to join {{ $invitation->tenant->name }}.
    </p>

    <div style="text-align: center; margin: 0 0 24px 0;">
        <span style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 600;">
            Your role: {{ ucfirst($invitation->role) }}
        </span>
    </div>

    @include('emails.partials.button', [
        'url' => config('app.frontend_url') . '/accept-invite/' . $invitation->token,
        'label' => 'Accept Invitation',
    ])

    <p style="font-size: 13px; color: #6b7280; text-align: center; margin: 0 0 24px 0;">
        This invitation expires on {{ $invitation->expires_at->format('F j, Y') }}.
    </p>

    <p style="font-size: 13px; color: #6b7280; text-align: center; margin: 0;">
        If you did not expect this invitation, you can safely ignore this email.
    </p>
@endsection
