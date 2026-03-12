<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\TemplateField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateField>
 */
class TemplateFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'page' => 1,
            'type' => fake()->randomElement(['signature', 'initials', 'text', 'date', 'checkbox', 'radio', 'dropdown']),
            'label' => fake()->word(),
            'required' => fake()->boolean(),
            'x' => fake()->randomFloat(2, 0, 100),
            'y' => fake()->randomFloat(2, 0, 100),
            'width' => fake()->randomFloat(2, 5, 50),
            'height' => fake()->randomFloat(2, 5, 20),
            'font_size' => 12,
            'multiline' => false,
            'options' => null,
            'signer_role' => null,
            'order' => 0,
        ];
    }
}
