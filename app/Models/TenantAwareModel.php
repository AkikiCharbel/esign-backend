<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class TenantAwareModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenant = currentTenant()) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenant->id);
            }
        });

        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id') && $tenant = currentTenant()) {
                $model->setAttribute('tenant_id', $tenant->id);
            }
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
