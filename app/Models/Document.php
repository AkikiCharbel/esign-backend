<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends TenantAwareModel
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'template_id',
        'created_by',
        'name',
        'custom_message',
        'reply_to_email',
        'reply_to_name',
        'has_attachments',
        'attachment_instructions',
    ];

    protected function casts(): array
    {
        return [
            'has_attachments' => 'boolean',
        ];
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<DocumentSigner, $this> */
    public function signers(): HasMany
    {
        return $this->hasMany(DocumentSigner::class);
    }

    /** @return HasMany<Submission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
