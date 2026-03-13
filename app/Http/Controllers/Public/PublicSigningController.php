<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\SigningSubmitRequest;
use App\Http\Resources\SubmissionResource;
use App\Jobs\GenerateSignedPdfJob;
use App\Models\Submission;
use App\Models\SubmissionFieldValue;
use Illuminate\Http\JsonResponse;

class PublicSigningController extends Controller
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

        if (in_array($submission->status, ['signed', 'processing']) || ($submission->expires_at !== null && now()->greaterThan($submission->expires_at))) {
            return response()->json(['message' => 'This signing link has expired or already been completed.'], 410);
        }

        if ($submission->viewed_at === null) {
            $submission->auditLogs()->create([
                'event' => 'viewed',
                'ip' => request()->ip(),
            ]);

            $submission->update([
                'viewed_at' => now(),
                'status' => 'pending',
            ]);
        }

        $submission->load(['document.template.fields', 'fieldValues']);

        return response()->json(new SubmissionResource($submission));
    }

    public function update(string $token, SigningSubmitRequest $request): JsonResponse
    {
        $submission = Submission::query()
            ->withoutGlobalScope('tenant')
            ->where('token', $token)
            ->first();

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        if (in_array($submission->status, ['signed', 'processing']) || ($submission->expires_at !== null && now()->greaterThan($submission->expires_at))) {
            return response()->json(['message' => 'This signing link has expired or already been completed.'], 410);
        }

        $document = $submission->document;

        if (! $document || ! $document->template) {
            return response()->json(['message' => 'Submission is missing document or template.'], 500);
        }

        $template = $document->template;
        $validFieldIds = $template->fields()->pluck('id')->toArray();

        /** @var array<int, array{template_field_id: int, value: string|null}> $fieldValuesInput */
        $fieldValuesInput = $request->validated('field_values');

        $submittedFieldIds = collect($fieldValuesInput)
            ->pluck('template_field_id')
            ->map(fn ($id): int => (int) $id)
            ->toArray();

        $invalidIds = array_diff($submittedFieldIds, $validFieldIds);
        if (! empty($invalidIds)) {
            return response()->json([
                'message' => 'Invalid field IDs.',
                'errors' => ['field_values' => ['One or more field IDs do not belong to this template.']],
            ], 422);
        }

        $requiredFieldIds = $template->fields()
            ->where('required', true)
            ->pluck('id')
            ->toArray();

        $missingRequired = array_diff($requiredFieldIds, $submittedFieldIds);

        if (! empty($missingRequired)) {
            $missingLabels = $template->fields()
                ->whereIn('id', $missingRequired)
                ->pluck('label')
                ->toArray();

            return response()->json([
                'message' => 'Missing required fields.',
                'errors' => ['field_values' => ['Missing required fields: '.implode(', ', $missingLabels)]],
            ], 422);
        }

        foreach ($fieldValuesInput as $fv) {
            SubmissionFieldValue::query()->updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'template_field_id' => $fv['template_field_id'],
                ],
                ['value' => $fv['value'] ?? null],
            );
        }

        $submission->update(['status' => 'processing']);

        GenerateSignedPdfJob::dispatch($submission, $fieldValuesInput, $request->ip(), $request->userAgent());

        return response()->json([
            'message' => 'Signing complete',
            'signed_pdf_url' => null,
        ]);
    }
}
