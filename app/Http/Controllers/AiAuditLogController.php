<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiAuditLog;
use App\Repositories\AiAuditLogRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAuditLogController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly AiAuditLogRepository $repository) {}

    public function index(Request $request): View
    {
        $this->authorize('admin-only');

        $query = AiAuditLog::with('user')->orderBy('created_at', 'desc');
        $query->applyFilters($request->only(['success', 'provider', 'feature', 'date_from', 'date_to']));

        $logs      = $query->paginate(20)->withQueryString();
        $stats     = $this->repository->getAdminStats();
        $providers = $this->repository->getUniqueProviders();
        $features  = $this->repository->getUniqueFeatures();

        return view('ai-audit-logs.index', compact('logs', 'stats', 'providers', 'features'));
    }

    public function show(AiAuditLog $aiAuditLog): View
    {
        $this->authorize('admin-only');

        $aiAuditLog->load('user');

        return view('ai-audit-logs.show', compact('aiAuditLog'));
    }

    public function status(): JsonResponse
    {
        $this->authorize('admin-only');

        $provider = config('ai.default');

        $config = [
            'provider'            => $provider,
            'provider_name'       => config("ai.providers.{$provider}.name", 'Unknown'),
            'base_url'            => config("ai.providers.{$provider}.base_url"),
            'model'               => config("ai.providers.{$provider}.model"),
            'timeout'             => config("ai.providers.{$provider}.timeout"),
            'distractors_enabled' => config('ai.features.distractors.enabled'),
        ];

        return response()->json([
            'config'     => $config,
            'recent_24h' => $this->repository->getRecentActivity(),
        ]);
    }
}
