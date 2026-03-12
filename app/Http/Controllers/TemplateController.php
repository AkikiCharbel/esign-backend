<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TemplateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $templates = Template::query()
            ->with('media')
            ->latest()
            ->paginate(15);

        return TemplateResource::collection($templates);
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $template = Template::query()->create([
            ...$request->validated(),
            'created_by' => $user->id,
        ]);

        return TemplateResource::make($template)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Template $template): TemplateResource
    {
        $template->load('fields');

        return TemplateResource::make($template);
    }

    public function update(UpdateTemplateRequest $request, Template $template): TemplateResource
    {
        $template->update($request->validated());

        return TemplateResource::make($template);
    }

    public function destroy(Template $template): JsonResponse
    {
        $template->delete();

        return response()->json(null, 204);
    }
}
