<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentSignerRequest;
use App\Http\Resources\DocumentSignerResource;
use App\Models\Document;
use App\Models\DocumentSigner;
use Illuminate\Http\JsonResponse;

class DocumentSignerController extends Controller
{
    public function store(StoreDocumentSignerRequest $request, Document $document): JsonResponse
    {
        $data = $request->validated();

        if (! isset($data['sign_order'])) {
            $data['sign_order'] = $document->signers()->max('sign_order') + 1;
        }

        $signer = $document->signers()->create($data);

        return DocumentSignerResource::make($signer)
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Document $document, DocumentSigner $signer): JsonResponse
    {
        $signer->delete();

        return response()->json(null, 204);
    }
}
