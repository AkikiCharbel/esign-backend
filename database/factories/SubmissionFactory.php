<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Submission;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'document_id' => Document::factory(),
            'recipient_name' => fake()->name(),
            'recipient_email' => fake()->unique()->safeEmail(),
            'status' => 'sent',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
            'sent_at' => now(),
        ];
    }

    public function signed(): static
    {
        return $this->state(fn () => [
            'status' => 'signed',
            'signed_at' => now(),
        ]);
    }
}
