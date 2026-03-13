<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;

class CustomerPortalController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $submission = Submission::query()
            ->withoutGlobalScope('tenant')
            ->where('token', $token)
            ->first();

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        if ($submission->expires_at !== null && now()->greaterThan($submission->expires_at)) {
            return response()->json(['message' => 'This portal link has expired.'], 410);
        }

        $submissions = Submission::query()
            ->withoutGlobalScope('tenant')
            ->where('recipient_email', $submission->recipient_email)
            ->where('tenant_id', $submission->tenant_id)
            ->whereNotNull('sent_at')
            ->with(['document.template'])
            ->latest()
            ->get();

        return response()->json([
            'recipient_name' => $submission->recipient_name,
            'recipient_email' => $submission->recipient_email,
            'submissions' => SubmissionResource::collection($submissions),
        ]);
    }
}
