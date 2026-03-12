<?php

namespace App\Models;

use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Template extends TenantAwareModel implements HasMedia
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'description',
        'page_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'page_count' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('template-pdf')->singleFile();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<TemplateField, $this> */
    public function fields(): HasMany
    {
        return $this->hasMany(TemplateField::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
