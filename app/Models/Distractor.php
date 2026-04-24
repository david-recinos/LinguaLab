<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $translation_id
 * @property string $distractor_text
 * @property string $source
 * @property Translation $translation
 * @mixin IdeHelperDistractor
 */
class Distractor extends Model
{
    use HasFactory;

    protected $fillable = [
        'translation_id',
        'distractor_text',
        'source',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }

    public function isAiGenerated(): bool
    {
        return $this->source === 'ai';
    }

    public function scopeAi(Builder $query): Builder
    {
        return $query->where('source', 'ai');
    }

    public function scopeFallback(Builder $query): Builder
    {
        return $query->where('source', 'fallback');
    }
}
