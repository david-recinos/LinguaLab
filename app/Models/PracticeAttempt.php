<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticeDirection;
use App\Enums\PracticeInputMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPracticeAttempt
 */
class PracticeAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'translation_id',
        'direction',
        'input_method',
        'is_correct',
        'time_spent_seconds',
    ];

    protected function casts(): array
    {
        return [
            'direction'          => PracticeDirection::class,
            'input_method'       => PracticeInputMethod::class,
            'is_correct'         => 'boolean',
            'time_spent_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }
}
