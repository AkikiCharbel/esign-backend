<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Jobs\SendSigningInvitationJob;
use App\Models\Document;
use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubmissionController extends Controller
{
    public function __construct(public SubmissionService $submissionService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Submission::query()
            ->with('document')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_email', 'like', "%{$search}%");
            });
        }

        return SubmissionResource::collection($query->paginate(15));
    }

    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        /** @var Document $document */
        $document = Document::query()->findOrFail($request->validated('document_id'));

        $submission = $this->submissionService->createAndSend($document, [
            'recipient_name' => $request->validated('recipient_name'),
            'recipient_email' => $request->validated('recipient_email'),
        ], $request->ip());

        $submission->load('document', 'fieldValues.templateField', 'auditLogs');

        return SubmissionResource::make($submission)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Submission $submission): SubmissionResource
    {
        $submission->load('document', 'fieldValues.templateField', 'auditLogs');

        return SubmissionResource::make($submission);
    }

    public function resend(Submission $submission): JsonResponse
    {
        abort_unless(in_array($submission->status, ['sent', 'pending']), 422, 'Cannot resend a completed submission.');

        SendSigningInvitationJob::dispatch($submission);

        return response()->json(null, 204);
    }
}
