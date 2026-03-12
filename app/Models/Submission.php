<?php

namespace App\Models;

use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Submission extends TenantAwareModel implements HasMedia
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'document_id',
        'recipient_name',
        'recipient_email',
        'status',
        'token',
        'ip_address',
        'user_agent',
        'sent_at',
        'viewed_at',
        'signed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'signed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('signed-pdf')->singleFile();
        $this->addMediaCollection('attachments');
        $this->addMediaCollection('signatures');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return HasMany<SubmissionFieldValue, $this> */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(SubmissionFieldValue::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
