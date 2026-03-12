<?php

namespace App\Models;

use Database\Factories\DocumentSignerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSigner extends Model
{
    /** @use HasFactory<DocumentSignerFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'name',
        'email',
        'role',
        'sign_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sign_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
