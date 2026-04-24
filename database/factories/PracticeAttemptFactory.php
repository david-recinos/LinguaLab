<?php

namespace Database\Factories;

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PracticeAttempt>
 */
class PracticeAttemptFactory extends Factory
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
            'translation_id' => Translation::factory(),
            'direction' => fake()->randomElement(PracticeDirection::cases()),
            'input_method' => fake()->randomElement(PracticeInputMethod::cases()),
            'is_correct' => fake()->boolean(),
            'time_spent_seconds' => fake()->numberBetween(1, 60),
        ];
    }
}
