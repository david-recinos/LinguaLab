<?php

namespace App\Models;

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'translation_id',
        'direction',
        'input_method',
        'is_correct',
        'time_spent_seconds',
    ];

    protected $casts = [
        'direction' => PracticeDirection::class,
        'input_method' => PracticeInputMethod::class,
        'is_correct' => 'boolean',
        'time_spent_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }
}
