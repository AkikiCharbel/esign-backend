<?php

namespace App\Http\Resources;

use App\Models\TemplateField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TemplateField */
class TemplateFieldResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'page' => $this->page,
            'type' => $this->type,
            'label' => $this->label,
            'required' => $this->required,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'font_size' => $this->font_size,
            'multiline' => $this->multiline,
            'options' => $this->options,
            'signer_role' => $this->signer_role,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
