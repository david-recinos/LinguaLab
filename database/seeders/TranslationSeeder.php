<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use App\Models\WordType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TranslationSeeder extends Seeder
{
    /**
     * Seed 100 sample translations for testing the review system.
     * Creates Spanish → English vocabulary across different categories.
     */
    public function run(): void
    {
        // Get the regular user created by DatabaseSeeder
        $user = User::where('email', 'regular@lingualab.com')->first();

        if (! $user) {
            $this->command->warn('User regular@lingualab.com not found. Creating user...');

            // Create the user if not exists
            $user = User::factory()->create([
                'name' => 'Regular User',
                'email' => 'regular@lingualab.com',
                'password' => Hash::make('userpass'),
            ]);
            $user->assignRole('user');
        }

        // Get languages
        $english = Language::where('code', 'en')->first();
        $spanish = Language::where('code', 'es')->first();

        if (! $english || ! $spanish) {
            $this->command->warn('Languages not found. Please run LanguageSeeder first.');

            return;
        }

        // Set up user's source and target languages if not exists
        // Spanish is SOURCE (what you know), English is TARGET (what you're learning)
        UserSourceLanguage::firstOrCreate(
            ['user_id' => $user->id, 'language_id' => $spanish->id],
            ['is_active' => true]
        );

        UserTargetLanguage::firstOrCreate(
            ['user_id' => $user->id, 'source_language_id' => $spanish->id, 'target_language_id' => $english->id]
        );

        // Get word types
        $noun = WordType::where('name', 'Noun')->first();
        $verb = WordType::where('name', 'Verb')->first();
        $adjective = WordType::where('name', 'Adjective')->first();
        $adverb = WordType::where('name', 'Adverb')->first();
        $preposition = WordType::where('name', 'Preposition')->first();
        $conjunction = WordType::where('name', 'Conjunction')->first();

        // 100 sample vocabulary words organized by category
        // Format: [source (Spanish), target (English), wordType, notes]
        $vocabulary = [
            // Greetings & Basics (1-10)
            ['hola', 'hello', $noun, 'A greeting'],
            ['adiós', 'goodbye', $noun, 'A farewell'],
            ['por favor', 'please', $adverb, 'A polite request'],
            ['gracias', 'thank you', $adverb, 'Expression of gratitude'],
            ['sí', 'yes', $adverb, 'Affirmation'],
            ['no', 'no', $adverb, 'Negation'],
            ['disculpe', 'excuse me', $adverb, 'Polite interruption'],
            ['lo siento', 'sorry', $adjective, 'Apology'],
            ['buenos días', 'good morning', $noun, 'Morning greeting'],
            ['buenas noches', 'good night', $noun, 'Night farewell'],

            // Numbers (11-20)
            ['uno', 'one', $noun, 'Number 1'],
            ['dos', 'two', $noun, 'Number 2'],
            ['tres', 'three', $noun, 'Number 3'],
            ['cuatro', 'four', $noun, 'Number 4'],
            ['cinco', 'five', $noun, 'Number 5'],
            ['seis', 'six', $noun, 'Number 6'],
            ['siete', 'seven', $noun, 'Number 7'],
            ['ocho', 'eight', $noun, 'Number 8'],
            ['nueve', 'nine', $noun, 'Number 9'],
            ['diez', 'ten', $noun, 'Number 10'],

            // Family (21-30)
            ['madre', 'mother', $noun, 'Female parent'],
            ['padre', 'father', $noun, 'Male parent'],
            ['hermano', 'brother', $noun, 'Male sibling'],
            ['hermana', 'sister', $noun, 'Female sibling'],
            ['hijo', 'son', $noun, 'Male child'],
            ['hija', 'daughter', $noun, 'Female child'],
            ['abuela', 'grandmother', $noun, 'Mother of a parent'],
            ['abuelo', 'grandfather', $noun, 'Father of a parent'],
            ['tío', 'uncle', $noun, 'Brother of a parent'],
            ['tía', 'aunt', $noun, 'Sister of a parent'],

            // Food & Drinks (31-45)
            ['agua', 'water', $noun, 'Essential liquid'],
            ['pan', 'bread', $noun, 'Baked food'],
            ['leche', 'milk', $noun, 'Dairy product'],
            ['café', 'coffee', $noun, 'Caffeinated beverage'],
            ['té', 'tea', $noun, 'Hot beverage'],
            ['vino', 'wine', $noun, 'Alcoholic beverage'],
            ['cerveza', 'beer', $noun, 'Alcoholic beverage'],
            ['carne', 'meat', $noun, 'Animal protein'],
            ['pescado', 'fish', $noun, 'Seafood'],
            ['pollo', 'chicken', $noun, 'Poultry'],
            ['huevo', 'egg', $noun, 'Breakfast food'],
            ['queso', 'cheese', $noun, 'Dairy product'],
            ['fruta', 'fruit', $noun, 'Sweet plant food'],
            ['verdura', 'vegetable', $noun, 'Healthy plant food'],
            ['arroz', 'rice', $noun, 'Staple grain'],

            // Colors (46-55)
            ['rojo', 'red', $adjective, 'Color of blood'],
            ['azul', 'blue', $adjective, 'Color of the sky'],
            ['verde', 'green', $adjective, 'Color of grass'],
            ['amarillo', 'yellow', $adjective, 'Color of the sun'],
            ['negro', 'black', $adjective, 'Darkest color'],
            ['blanco', 'white', $adjective, 'Lightest color'],
            ['naranja', 'orange', $adjective, 'Color of the fruit'],
            ['morado', 'purple', $adjective, 'Color of royalty'],
            ['rosa', 'pink', $adjective, 'Light red color'],
            ['marrón', 'brown', $adjective, 'Color of earth'],

            // Common Verbs (56-75)
            ['ser/estar', 'to be', $verb, 'State of existence'],
            ['tener', 'to have', $verb, 'To possess'],
            ['hacer', 'to do', $verb, 'To perform an action'],
            ['decir', 'to say', $verb, 'To speak words'],
            ['ir', 'to go', $verb, 'To move somewhere'],
            ['venir', 'to come', $verb, 'To arrive'],
            ['ver', 'to see', $verb, 'To use eyes'],
            ['saber', 'to know', $verb, 'To have knowledge'],
            ['querer', 'to want', $verb, 'To desire'],
            ['dar', 'to give', $verb, 'To transfer possession'],
            ['pensar', 'to think', $verb, 'To use mind'],
            ['tomar', 'to take', $verb, 'To grab or consume'],
            ['trabajar', 'to work', $verb, 'To do a job'],
            ['llamar', 'to call', $verb, 'To contact or name'],
            ['intentar', 'to try', $verb, 'To make an effort'],
            ['preguntar', 'to ask', $verb, 'To inquire'],
            ['necesitar', 'to need', $verb, 'To require'],
            ['sentir', 'to feel', $verb, 'To experience emotion'],
            ['convertirse', 'to become', $verb, 'To transform'],
            ['salir', 'to leave', $verb, 'To depart'],

            // Time (76-85)
            ['hoy', 'today', $adverb, 'Current day'],
            ['mañana', 'tomorrow', $adverb, 'Next day'],
            ['ayer', 'yesterday', $adverb, 'Previous day'],
            ['ahora', 'now', $adverb, 'Current moment'],
            ['más tarde', 'later', $adverb, 'After the present'],
            ['siempre', 'always', $adverb, 'At all times'],
            ['nunca', 'never', $adverb, 'At no time'],
            ['a veces', 'sometimes', $adverb, 'Occasionally'],
            ['semana', 'week', $noun, 'Seven days'],
            ['mes', 'month', $noun, 'Calendar period'],

            // Weather (86-92)
            ['sol', 'sun', $noun, 'Star in the sky'],
            ['luna', 'moon', $noun, 'Earth\'s satellite'],
            ['lluvia', 'rain', $noun, 'Water from clouds'],
            ['nieve', 'snow', $noun, 'Frozen precipitation'],
            ['viento', 'wind', $noun, 'Moving air'],
            ['nube', 'cloud', $noun, 'White mass in sky'],
            ['tormenta', 'storm', $noun, 'Severe weather'],

            // Prepositions & Conjunctions (93-100)
            ['en', 'in', $preposition, 'Inside'],
            ['sobre', 'on', $preposition, 'Above'],
            ['a', 'at', $preposition, 'Location'],
            ['con', 'with', $preposition, 'Accompaniment'],
            ['para', 'for', $preposition, 'Purpose'],
            ['y', 'and', $conjunction, 'Addition'],
            ['pero', 'but', $conjunction, 'Contrast'],
            ['o', 'or', $conjunction, 'Alternative'],
        ];

        $this->command->info('Creating 100 sample translations...');

        $now = now();
        $dueCount = 0;

        foreach ($vocabulary as $index => [$source, $target, $wordType, $note]) {
            // Create varied next_review_at times to simulate spaced repetition
            // Some due now, some in the past, some in the future
            $reviewOffset = match (true) {
                $index < 20 => rand(-2, 0),      // Due or overdue (first 20)
                $index < 50 => rand(-1, 3),      // Mixed (middle)
                default => rand(0, 7),           // Future (rest)
            };

            $nextReview = $now->copy()->addDays($reviewOffset)->addHours(rand(-12, 12));

            Translation::create([
                'user_id'            => $user->id,
                'source_language_id' => $spanish->id,
                'target_language_id' => $english->id,
                'type'               => 'word',
                'word_type_id'       => $wordType?->id,
                'source_text'        => $source,
                'target_text'        => $target,
                'notes'              => $note,
                'ease_factor'        => 2.50,
                'interval_days'      => 1,
                'next_review_at'     => $nextReview,
                'last_reviewed_at'   => null,
                'total_reviews'      => 0,
                'successful_reviews' => 0,
            ]);

            if ($nextReview <= $now) {
                $dueCount++;
            }
        }

        $this->command->info("Created 100 translations for user 'regular@lingualab.com'");
        $this->command->info('Spanish → English vocabulary');
        $this->command->info("{$dueCount} translations are due for review now");
    }
}
