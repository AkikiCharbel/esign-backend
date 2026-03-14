<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateFieldRequest;
use App\Http\Requests\SyncTemplateFieldsRequest;
use App\Http\Requests\UpdateTemplateFieldRequest;
use App\Http\Resources\TemplateFieldResource;
use App\Models\SubmissionFieldValue;
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
        /** @var array<int, array<string, mixed>> $validatedFields */
        $validatedFields = $request->validated('fields');

        $fields = DB::transaction(function () use ($validatedFields, $template) {
            $incoming = collect($validatedFields);
            $incomingIds = $incoming->pluck('id')->filter()->all();

            // Remove fields no longer in the payload (and their submission values)
            $removedFieldIds = $template->fields()
                ->whereNotIn('id', $incomingIds)
                ->pluck('id');

            if ($removedFieldIds->isNotEmpty()) {
                SubmissionFieldValue::whereIn('template_field_id', $removedFieldIds)->delete();
                TemplateField::whereIn('id', $removedFieldIds)->delete();
            }

            /** @var TemplateField[] $result */
            $result = [];

            foreach ($incoming as $fieldData) {
                if (! empty($fieldData['id'])) {
                    /** @var TemplateField|null $field */
                    $field = $template->fields()->find($fieldData['id']);
                    if ($field) {
                        $field->update($fieldData);
                        $result[] = $field;
                        continue;
                    }
                }

                // Create new field
                unset($fieldData['id']);
                $result[] = $template->fields()->create($fieldData);
            }

            return collect($result);
        });

        return TemplateFieldResource::collection($fields);
    }
}
