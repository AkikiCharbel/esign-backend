<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BulkSubmissionController;
use App\Http\Controllers\CustomerSubmissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentSignerController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Public\CustomerPortalController;
use App\Http\Controllers\Public\PublicAttachmentController;
use App\Http\Controllers\Public\PublicSigningController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateFieldController;
use App\Http\Controllers\TemplatePdfController;
use App\Http\Controllers\Workspace\InvitationController;
use App\Http\Controllers\Workspace\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Public signing routes (no auth required)
Route::get('/public/esign/{token}', [PublicSigningController::class, 'show'])->name('public.esign.show');
Route::post('/public/esign/{token}', [PublicSigningController::class, 'update'])->name('public.esign.update');

// Customer portal
Route::get('/portal/{token}', [CustomerPortalController::class, 'show'])->name('portal.show');

// Public attachments
Route::get('/public/esign/{token}/attachments', [PublicAttachmentController::class, 'index'])->name('public.esign.attachments.index');
Route::post('/public/esign/{token}/attachments', [PublicAttachmentController::class, 'store'])->middleware('throttle:10,1')->name('public.esign.attachments.store');
Route::delete('/public/esign/{token}/attachments/{mediaId}', [PublicAttachmentController::class, 'destroy'])->name('public.esign.attachments.destroy');

// Media (public, served through Laravel for CORS)
Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');

Route::post('/auth/register', [RegisterController::class, 'registerTenant'])->name('auth.register');
Route::post('/auth/accept-invitation', [RegisterController::class, 'acceptInvitation'])->name('auth.accept-invitation');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/recent', [DashboardController::class, 'stats'])->name('dashboard.recent');

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

    // Workspace Settings
    Route::get('/workspace', [WorkspaceController::class, 'show'])->name('workspace.show');
    Route::patch('/workspace', [WorkspaceController::class, 'update'])->name('workspace.update');
    Route::post('/workspace/logo', [WorkspaceController::class, 'uploadLogo'])->name('workspace.logo.store');
    Route::delete('/workspace/logo', [WorkspaceController::class, 'deleteLogo'])->name('workspace.logo.destroy');

    // Workspace Invitations
    Route::get('/workspace/invitations', [InvitationController::class, 'index'])->name('workspace.invitations.index');
    Route::post('/workspace/invitations', [InvitationController::class, 'store'])->name('workspace.invitations.store');
    Route::delete('/workspace/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('workspace.invitations.destroy');
});
