<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\MediaResource;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;

class PublicAttachmentController extends Controller
{
    private const MAX_ATTACHMENTS = 10;

    public function index(string $token): JsonResponse
    {
        $submission = $this->findSubmission($token);

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        return response()->json(MediaResource::collection($submission->getMedia('attachments')));
    }

    public function store(string $token, StoreAttachmentRequest $request): JsonResponse
    {
        $submission = $this->findSubmission($token);

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        if ($this->isCompleteOrExpired($submission)) {
            return response()->json(['message' => 'This signing link has expired or already been completed.'], 410);
        }

        if ($submission->getMedia('attachments')->count() >= self::MAX_ATTACHMENTS) {
            return response()->json(['message' => 'Maximum attachments reached.'], 422);
        }

        $media = $submission->addMediaFromRequest('file')
            ->toMediaCollection('attachments');

        return response()->json(new MediaResource($media), 201);
    }

    public function destroy(string $token, int $mediaId): JsonResponse
    {
        $submission = $this->findSubmission($token);

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        if ($this->isCompleteOrExpired($submission)) {
            return response()->json(['message' => 'This signing link has expired or already been completed.'], 410);
        }

        $media = $submission->getMedia('attachments')->firstWhere('id', $mediaId);

        if (! $media) {
            return response()->json(['message' => 'Attachment not found.'], 404);
        }

        $media->delete();

        return response()->json(null, 204);
    }

    private function findSubmission(string $token): ?Submission
    {
        return Submission::query()
            ->withoutGlobalScope('tenant')
            ->where('token', $token)
            ->first();
    }

    private function isCompleteOrExpired(Submission $submission): bool
    {
        return in_array($submission->status, ['signed', 'processing'])
            || ($submission->expires_at !== null && now()->greaterThan($submission->expires_at));
    }
}
