<?php

namespace App\Http\Resources;

use App\Models\DocumentSigner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentSigner */
class DocumentSignerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'sign_order' => $this->sign_order,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
