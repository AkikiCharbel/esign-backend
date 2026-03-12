<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionFieldValue;
use App\Models\TemplateField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionFieldValue>
 */
class SubmissionFieldValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'template_field_id' => TemplateField::factory(),
            'value' => null,
        ];
    }
}
