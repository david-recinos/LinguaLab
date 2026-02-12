<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $fillable = [
        'user_id',
        'source_language_id',
        'target_language_id',
        'type',
        'word_type_id',
        'source_text',
        'target_text',
        'example_sentence',
        'notes',
        'pronunciation',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'source_language_id');
    }

    public function targetLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_language_id');
    }

    public function wordType(): BelongsTo
    {
        return $this->belongsTo(WordType::class);
    }
}
