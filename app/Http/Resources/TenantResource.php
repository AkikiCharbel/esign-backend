<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tenant */
class TenantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'settings' => $this->settings,
            'logo_url' => $this->getFirstMedia('logo')
                ? route('media.show', $this->getFirstMedia('logo'))
                : null,
            'created_at' => $this->created_at,
        ];
    }
}
