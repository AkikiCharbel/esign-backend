@extends('emails.layout')

@section('content')
    {{-- Success banner --}}
    <div style="background-color: #ecfdf5; border-radius: 8px; padding: 24px; margin-bottom: 24px; text-align: center;">
        <div style="font-size: 32px; margin-bottom: 8px;">✅</div>
        <div style="font-size: 20px; color: #065f46; font-weight: 700;">Document Signed!</div>
    </div>

    {{-- Details --}}
    <p style="font-size: 15px; color: #374151; line-height: 1.6; margin: 0 0 24px 0;">
        {{ $submission->recipient_name }} ({{ $submission->recipient_email }}) has completed signing.
    </p>

    {{-- Info table --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin: 0 0 24px 0;">
        <tr style="background-color: #ffffff;">
            <td style="padding: 12px 16px; font-size: 14px; color: #6b7280; width: 120px;">Document</td>
            <td style="padding: 12px 16px; font-size: 14px; color: #111827;">{{ $submission->document->name }}</td>
        </tr>
        <tr style="background-color: #f9fafb;">
            <td style="padding: 12px 16px; font-size: 14px; color: #6b7280;">Signed At</td>
            <td style="padding: 12px 16px; font-size: 14px; color: #111827;">{{ $submission->signed_at->format('F j, Y g:i A') }}</td>
        </tr>
        <tr style="background-color: #ffffff;">
            <td style="padding: 12px 16px; font-size: 14px; color: #6b7280;">IP Address</td>
            <td style="padding: 12px 16px; font-size: 14px; color: #111827;">{{ $submission->ip_address ?? 'Not recorded' }}</td>
        </tr>
    </table>

    {{-- View button --}}
    @include('emails.partials.button', ['url' => $dashboard_url, 'label' => 'View Submission', 'color' => '#059669'])

    {{-- PDF note --}}
    @if($submission->getFirstMediaUrl('signed-pdf'))
        <p style="font-size: 13px; color: #6b7280; text-align: center; margin: 0 0 24px 0;">
            The signed PDF is available for download in your dashboard.
        </p>
    @endif
@endsection
