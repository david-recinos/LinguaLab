<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAiAuditLog
 */
class AiAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'feature',
        'model',
        'prompt',
        'response',
        'parsed_result',
        'success',
        'tokens_used',
        'response_time_ms',
        'error_message',
        'translation_id',
    ];

    protected function casts(): array
    {
        return [
            'parsed_result' => 'array',
            'success'       => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: filter logs by the given criteria.
     * Accepts: success (bool-string), provider, feature, date_from, date_to.
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['success'])) {
            $query->where('success', $filters['success'] === 'true');
        }

        if (! empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (! empty($filters['feature'])) {
            $query->where('feature', $filters['feature']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
