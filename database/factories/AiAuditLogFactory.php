<?php

namespace Database\Factories;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiAuditLog>
 */
class AiAuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['openai_compatible', 'openai', 'ollama']),
            'feature' => 'distractors',
            'model' => fake()->randomElement(['gpt-4o-mini', 'llama3', 'mistral']),
            'prompt' => fake()->paragraph(),
            'response' => fake()->paragraph(),
            'parsed_result' => null,
            'success' => fake()->boolean(80),
            'tokens_used' => fake()->numberBetween(100, 4000),
            'response_time_ms' => fake()->numberBetween(200, 5000),
            'error_message' => null,
            'translation_id' => Translation::factory(),
        ];
    }

    /**
     * State: a failed AI audit log.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'error_message' => fake()->sentence(),
            'parsed_result' => null,
        ]);
    }
}
