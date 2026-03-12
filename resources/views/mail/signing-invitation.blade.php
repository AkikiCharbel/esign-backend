<x-mail::message>
# You have a document to sign

@if($submission->document->custom_message)
{{ $submission->document->custom_message }}
@endif

Please click the button below to review and sign your document.

<x-mail::button :url="config('app.frontend_url') . '/public/esign/' . $submission->token">
Sign Document
</x-mail::button>

This link will expire on **{{ $submission->expires_at->format('F j, Y') }}**.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
