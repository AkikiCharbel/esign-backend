<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentSigner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSigner>
 */
class DocumentSignerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => fn () => Document::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'signer',
            'sign_order' => fake()->numberBetween(1, 10),
            'status' => 'pending',
        ];
    }
}
