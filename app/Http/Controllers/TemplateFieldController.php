<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateFieldRequest;
use App\Http\Requests\SyncTemplateFieldsRequest;
use App\Http\Requests\UpdateTemplateFieldRequest;
use App\Http\Resources\TemplateFieldResource;
use App\Models\Template;
use App\Models\TemplateField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class TemplateFieldController extends Controller
{
    public function store(StoreTemplateFieldRequest $request, Template $template): JsonResponse
    {
        $field = $template->fields()->create($request->validated());

        return TemplateFieldResource::make($field)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTemplateFieldRequest $request, Template $template, TemplateField $field): TemplateFieldResource
    {
        $field->update($request->validated());

        return TemplateFieldResource::make($field);
    }

    public function destroy(Template $template, TemplateField $field): JsonResponse
    {
        $field->delete();

        return response()->json(null, 204);
    }

    public function sync(SyncTemplateFieldsRequest $request, Template $template): AnonymousResourceCollection
    {
        $fields = DB::transaction(function () use ($request, $template) {
            $template->fields()->delete();

            return $template->fields()->createMany($request->validated('fields'));
        });

        return TemplateFieldResource::collection($fields);
    }
}
