<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'created_by' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'page_count' => fake()->numberBetween(1, 10),
            'status' => 'draft',
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }
}
