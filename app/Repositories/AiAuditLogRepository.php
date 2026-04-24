<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AiAuditLog;
use Illuminate\Support\Collection;

class AiAuditLogRepository
{
    /**
     * Get raw aggregate statistics for the admin panel.
     */
    public function getAdminStats(): array
    {
        return $this->getAggregateStats();
    }

    /**
     * Get formatted statistics for display.
     */
    public function getStats(): array
    {
        $raw = $this->getAggregateStats();

        return [
            'total_calls'          => $raw['total'],
            'successful_calls'     => $raw['successful'],
            'failed_calls'         => $raw['failed'],
            'success_rate'         => $raw['total'] > 0
                ? round(($raw['successful'] / $raw['total']) * 100, 1)
                : 0,
            'avg_response_time_ms' => $raw['avg_response_time']
                ? (int) round($raw['avg_response_time'])
                : 0,
        ];
    }

    /**
     * Get recent activity counts for the last 24 hours.
     * Uses a single grouped query to avoid an extra COUNT round-trip.
     */
    public function getRecentActivity(): array
    {
        $rows = AiAuditLog::where('created_at', '>=', now()->subDay())
            ->selectRaw('success, COUNT(*) as count')
            ->groupBy('success')
            ->get()
            ->keyBy('success');

        $successful = (int) ($rows->get(1)?->count ?? 0);
        $failed     = (int) ($rows->get(0)?->count ?? 0);

        return [
            'total'      => $successful + $failed,
            'successful' => $successful,
            'failed'     => $failed,
        ];
    }

    /**
     * Get the distinct AI providers that have been used.
     */
    public function getUniqueProviders(): Collection
    {
        return AiAuditLog::distinct()->pluck('provider');
    }

    /**
     * Get the distinct features that have been logged.
     */
    public function getUniqueFeatures(): Collection
    {
        return AiAuditLog::distinct()->pluck('feature');
    }

    /**
     * Run a single aggregate query used by both getAdminStats() and getStats().
     * Avoids separate COUNT/AVG/SUM round-trips to the database.
     */
    private function getAggregateStats(): array
    {
        $row = AiAuditLog::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed,
            AVG(response_time_ms) as avg_response_time,
            SUM(tokens_used) as total_tokens
        ')->first();

        return [
            'total'             => (int) ($row->total ?? 0),
            'successful'        => (int) ($row->successful ?? 0),
            'failed'            => (int) ($row->failed ?? 0),
            'avg_response_time' => $row->avg_response_time,
            'total_tokens'      => (int) ($row->total_tokens ?? 0),
        ];
    }
}
