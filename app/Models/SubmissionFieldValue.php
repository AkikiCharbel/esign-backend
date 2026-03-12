<?php

namespace App\Models;

use Database\Factories\SubmissionFieldValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFieldValue extends Model
{
    /** @use HasFactory<SubmissionFieldValueFactory> */
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'template_field_id',
        'value',
    ];

    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @return BelongsTo<TemplateField, $this> */
    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class);
    }
}
