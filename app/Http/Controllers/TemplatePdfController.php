<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplatePdfRequest;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use setasign\Fpdi\Tcpdf\Fpdi;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TemplatePdfController extends Controller
{
    public function store(StoreTemplatePdfRequest $request, Template $template): JsonResponse
    {
        $template->addMediaFromRequest('pdf')->toMediaCollection('template-pdf');

        $fpdi = new Fpdi;
        /** @var Media $media */
        $media = $template->getFirstMedia('template-pdf');
        $count = $fpdi->setSourceFile($media->getPath());

        $template->update(['page_count' => $count]);

        return response()->json([
            'pdf_url' => route('media.show', $media),
            'page_count' => $count,
        ]);
    }
}
