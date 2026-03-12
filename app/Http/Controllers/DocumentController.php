<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $documents = Document::query()
            ->with('template.media', 'signers')
            ->latest()
            ->paginate(15);

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $document = Document::query()->create([
            ...$request->validated(),
            'created_by' => $user->id,
        ]);

        $document->load('template.media', 'signers');

        return DocumentResource::make($document)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        $document->load('template.fields', 'template.media', 'signers');

        return DocumentResource::make($document);
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return response()->json(null, 204);
    }
}
