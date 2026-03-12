<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'event' => 'sent',
            'metadata' => [],
            'ip' => fake()->ipv4(),
        ];
    }
}
