<?php

namespace App\Http\Resources;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Submission */
class SubmissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'document_id' => $this->document_id,
            'recipient_name' => $this->recipient_name,
            'recipient_email' => $this->recipient_email,
            'status' => $this->status,
            'token' => $this->token,
            'sent_at' => $this->sent_at,
            'viewed_at' => $this->viewed_at,
            'signed_at' => $this->signed_at,
            'expires_at' => $this->expires_at,
            'signed_pdf_url' => $this->getFirstMediaUrl('signed-pdf') ?: null,
            'document' => DocumentResource::make($this->whenLoaded('document')),
            'field_values' => SubmissionFieldValueResource::collection($this->whenLoaded('fieldValues')),
            'audit_logs' => AuditLogResource::collection($this->whenLoaded('auditLogs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
