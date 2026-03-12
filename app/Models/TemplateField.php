<?php

namespace App\Models;

use Database\Factories\TemplateFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateField extends Model
{
    /** @use HasFactory<TemplateFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'template_id',
        'page',
        'type',
        'label',
        'required',
        'x',
        'y',
        'width',
        'height',
        'font_size',
        'multiline',
        'options',
        'signer_role',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'page' => 'integer',
            'required' => 'boolean',
            'x' => 'float',
            'y' => 'float',
            'width' => 'float',
            'height' => 'float',
            'font_size' => 'integer',
            'multiline' => 'boolean',
            'options' => 'json',
            'order' => 'integer',
        ];
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return HasMany<SubmissionFieldValue, $this> */
    public function submissionFieldValues(): HasMany
    {
        return $this->hasMany(SubmissionFieldValue::class);
    }
}
