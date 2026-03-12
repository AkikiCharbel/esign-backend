<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkSubmissionRequest;
use App\Jobs\CreateSubmissionJob;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class BulkSubmissionController extends Controller
{
    public function store(BulkSubmissionRequest $request): JsonResponse
    {
        /** @var Document $document */
        $document = Document::query()->findOrFail($request->validated('document_id'));
        $ip = $request->ip();

        $recipients = [];
        $totalRows = 0;

        if ($request->hasFile('csv')) {
            /** @var UploadedFile $csvFile */
            $csvFile = $request->file('csv');
            $handle = fopen($csvFile->getPathname(), 'r');
            abort_if($handle === false, 422, 'Could not read CSV file.');

            try {
                $headerRow = fgetcsv($handle);
                abort_if($headerRow === false, 422, 'CSV file is empty.');

                $header = array_map(fn (?string $col) => strtolower(trim((string) $col)), $headerRow);
                $nameIndex = array_search('name', $header);
                $emailIndex = array_search('email', $header);

                abort_if($nameIndex === false || $emailIndex === false, 422, 'CSV must have "name" and "email" columns.');

                while (($row = fgetcsv($handle)) !== false) {
                    $totalRows++;
                    $email = trim($row[$emailIndex] ?? '');
                    $name = trim($row[$nameIndex] ?? '');

                    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = [
                            'recipient_name' => $name,
                            'recipient_email' => $email,
                        ];
                    }
                }
            } finally {
                fclose($handle);
            }
        } else {
            foreach ($request->validated('recipients') as $recipient) {
                $totalRows++;
                $recipients[] = [
                    'recipient_name' => $recipient['name'],
                    'recipient_email' => $recipient['email'],
                ];
            }
        }

        foreach ($recipients as $recipientData) {
            CreateSubmissionJob::dispatch($document, $recipientData, $ip);
        }

        $response = ['queued' => count($recipients)];

        $skipped = $totalRows - count($recipients);
        if ($skipped > 0) {
            $response['skipped'] = $skipped;
        }

        return response()->json($response, 202);
    }
}
