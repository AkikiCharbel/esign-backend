<?php

namespace App\Http\Controllers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function show(Media $media): Response
    {
        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
        ]);
    }
}
