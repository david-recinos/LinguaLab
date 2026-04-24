<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Distractor>
 */
class DistractorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'translation_id' => Translation::factory(),
            'distractor_text' => fake()->word(),
            'source' => fake()->randomElement(['ai', 'fallback']),
        ];
    }

    /**
     * State: AI-generated distractor.
     */
    public function ai(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'ai',
        ]);
    }

    /**
     * State: fallback distractor.
     */
    public function fallback(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'fallback',
        ]);
    }
}
