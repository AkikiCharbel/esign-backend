<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class DocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'template_id' => $this->template_id,
            'created_by' => $this->created_by,
            'name' => $this->name,
            'custom_message' => $this->custom_message,
            'reply_to_email' => $this->reply_to_email,
            'reply_to_name' => $this->reply_to_name,
            'has_attachments' => $this->has_attachments,
            'attachment_instructions' => $this->attachment_instructions,
            'template' => TemplateResource::make($this->whenLoaded('template')),
            'signers' => DocumentSignerResource::collection($this->whenLoaded('signers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
