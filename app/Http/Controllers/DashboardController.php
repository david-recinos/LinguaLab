<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\UserLanguageService;
use App\Services\UserProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserLanguageService $languageService,
        private readonly UserProgressService $progressService,
    ) {}

    public function index(): View
    {
        $user         = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        $translationCount    = $user->translations()->count();
        $sourceLanguageCount = $user->sourceLanguages()->count();
        $targetLanguageCount = $this->languageService->getTargetLanguageCountForActiveSource($user);

        $dueForReviewCount        = 0;
        $dueTranslations          = collect();
        $totalTranslationsForSource = 0;

        if ($activeSource) {
            $dueTranslations            = $this->progressService->getTranslationsDueForReview($user)->take(10);
            $dueForReviewCount          = $this->progressService->getDueForReviewCount($user);
            $totalTranslationsForSource = $this->progressService->getTotalTranslationsForActiveSource($user);
        }

        return view('dashboard', compact(
            'activeSource',
            'translationCount',
            'sourceLanguageCount',
            'targetLanguageCount',
            'dueForReviewCount',
            'dueTranslations',
            'totalTranslationsForSource'
        ));
    }
}
