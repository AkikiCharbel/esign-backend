<?php

namespace App\Http\Resources;

use App\Models\SubmissionFieldValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubmissionFieldValue */
class SubmissionFieldValueResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'template_field_id' => $this->template_field_id,
            'value' => $this->value,
            'field' => TemplateFieldResource::make($this->whenLoaded('templateField')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
