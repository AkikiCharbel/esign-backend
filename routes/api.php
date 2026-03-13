<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BulkSubmissionController;
use App\Http\Controllers\CustomerSubmissionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentSignerController;
use App\Http\Controllers\Public\PublicSigningController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateFieldController;
use App\Http\Controllers\TemplatePdfController;
use Illuminate\Support\Facades\Route;

// Public signing routes (no auth required)
Route::get('/public/esign/{token}', [PublicSigningController::class, 'show'])->name('public.esign.show');
Route::post('/public/esign/{token}', [PublicSigningController::class, 'update'])->name('public.esign.update');

Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    // Templates
    Route::apiResource('templates', TemplateController::class)->except(['update']);
    Route::patch('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');

    // Template PDF upload
    Route::post('/templates/{template}/pdf', [TemplatePdfController::class, 'store'])->name('templates.pdf.store');

    // Template Fields
    Route::post('/templates/{template}/fields', [TemplateFieldController::class, 'store'])->name('templates.fields.store');
    Route::put('/templates/{template}/fields/sync', [TemplateFieldController::class, 'sync'])->name('templates.fields.sync');
    Route::patch('/templates/{template}/fields/{field}', [TemplateFieldController::class, 'update'])->scopeBindings()->name('template-fields.update');
    Route::delete('/templates/{template}/fields/{field}', [TemplateFieldController::class, 'destroy'])->scopeBindings()->name('template-fields.destroy');

    // Documents
    Route::apiResource('documents', DocumentController::class)->only(['index', 'store', 'show', 'destroy']);

    // Document Signers
    Route::post('/documents/{document}/signers', [DocumentSignerController::class, 'store'])->name('documents.signers.store');
    Route::delete('/documents/{document}/signers/{signer}', [DocumentSignerController::class, 'destroy'])->scopeBindings()->name('document-signers.destroy');

    // Submissions
    Route::post('/submissions/bulk', [BulkSubmissionController::class, 'store'])->name('submissions.bulk.store');
    Route::apiResource('submissions', SubmissionController::class)->only(['index', 'store', 'show']);
    Route::post('/submissions/{submission}/resend', [SubmissionController::class, 'resend'])->name('submissions.resend');

    // Customer Submissions
    Route::get('/customers/{email}/submissions', [CustomerSubmissionController::class, 'index'])->name('customers.submissions.index');
});
