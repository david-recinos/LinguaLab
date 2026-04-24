<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\User;
use App\Models\WordType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
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
            'source_language_id' => Language::factory(),
            'target_language_id' => Language::factory(),
            'type' => fake()->randomElement(['word', 'phrase']),
            'word_type_id' => WordType::factory(),
            'source_text' => fake()->word(),
            'target_text' => fake()->word(),
            'example_sentence' => null,
            'notes' => null,
            'pronunciation' => null,
            'ease_factor' => 2.50,
            'interval_days' => 1,
            'next_review_at' => null,
            'last_reviewed_at' => null,
            'total_reviews' => 0,
            'successful_reviews' => 0,
        ];
    }

    /**
     * State: translation is due for review.
     */
    public function dueForReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_review_at' => now()->subDay(),
        ]);
    }

    /**
     * State: translation is not yet due for review.
     */
    public function notDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_review_at' => now()->addDays(7),
        ]);
    }
}
