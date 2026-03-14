<?php

use App\Mail\SigningCompletedMail;
use App\Mail\SigningInvitationMail;
use App\Mail\SigningReminderMail;
use App\Models\Submission;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/email-preview/signing-invitation', function () {
        $submission = Submission::with([
            'document.template',
            'document.creator',
        ])->latest()->firstOrFail();

        return new SigningInvitationMail($submission);
    });

    Route::get('/email-preview/signing-completed', function () {
        $submission = Submission::with([
            'document.template',
            'document.creator',
        ])->where('status', 'signed')->latest()->firstOrFail();

        return new SigningCompletedMail($submission);
    });

    Route::get('/email-preview/signing-reminder', function () {
        $submission = Submission::with([
            'document.template',
            'document.creator',
        ])->latest()->firstOrFail();

        return new SigningReminderMail($submission);
    });
}
