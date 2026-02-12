<?php

namespace Database\Seeders;

use App\Models\WordType;
use Illuminate\Database\Seeder;

class WordTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Verb',
            'Noun',
            'Adjective',
            'Adverb',
            'Pronoun',
            'Preposition',
            'Conjunction',
            'Interjection',
            'Article',
            'Determiner',
            'Numeral',
            'Participle',
            'Gerund',
        ];

        foreach ($types as $type) {
            WordType::create(['name' => $type]);
        }
    }
}
