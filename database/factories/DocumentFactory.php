<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::factory(),
            'template_id' => fn () => Template::factory(),
            'created_by' => fn () => User::factory(),
            'name' => fake()->sentence(3),
            'custom_message' => fake()->optional()->sentence(),
            'reply_to_email' => fake()->optional()->safeEmail(),
            'reply_to_name' => fake()->optional()->name(),
            'has_attachments' => false,
            'attachment_instructions' => null,
        ];
    }
}
