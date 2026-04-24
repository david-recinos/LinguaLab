<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PracticeInputMethod;
use App\Http\Requests\SubmitAnswerRequest;
use App\Services\DistractorService;
use App\Services\ReviewSessionService;
use App\Services\UserLanguageService;
use App\Services\UserProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewSessionService $sessionService,
        private readonly DistractorService $distractorService,
        private readonly UserLanguageService $languageService,
        private readonly UserProgressService $progressService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $user         = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return redirect()->route('languages.index')
                ->with('error', __('Please set up a source language first.'));
        }

        $dueTranslations  = $this->progressService->getTranslationsDueForReview($user);
        $dueCount         = $dueTranslations->count();
        $targetLanguages  = $this->languageService->getTargetLanguagesForActiveSource($user);
        $hasActiveSession = $this->sessionService->hasActiveSession();

        return view('review.index', compact(
            'dueTranslations',
            'dueCount',
            'activeSource',
            'targetLanguages',
            'hasActiveSession'
        ));
    }

    public function start(Request $request): RedirectResponse
    {
        if ($this->sessionService->hasActiveSession()) {
            return redirect()->route('review.question');
        }

        $user            = Auth::user();
        $dueTranslations = $this->progressService->getTranslationsDueForReview($user);

        if ($dueTranslations->isEmpty() && $request->has('practice_all')) {
            $dueTranslations = $this->progressService->getTranslationsForPractice($user, 10);
        }

        if ($dueTranslations->isEmpty()) {
            return redirect()->route('review.index')
                ->with('info', __('No translations available for review.'));
        }

        $this->distractorService->generateBatchForSession($dueTranslations);
        $this->sessionService->startSession($dueTranslations);

        return redirect()->route('review.question');
    }

    public function question(): View|RedirectResponse
    {
        $question = $this->sessionService->getCurrentQuestion();

        if (! $question) {
            return redirect()->route('review.complete');
        }

        $translation          = $question['translation'];
        $multipleChoiceOptions = null;
        $distractorsSource    = 'none';

        if ($question['input_method'] === PracticeInputMethod::MULTIPLE_CHOICE) {
            if ($translation->hasDistractors()) {
                $distractors       = $translation->getStoredDistractors(3);
                $distractorsSource = $translation->areDistractorsAiGenerated() ? 'ai' : 'fallback';
            } else {
                $distractors       = $this->distractorService->generateFallbackNow($translation, 3);
                $distractorsSource = 'fallback';
            }

            $options               = array_merge([$question['correct_answer']], $distractors);
            shuffle($options);
            $multipleChoiceOptions = $options;
        }

        return view('review.question', compact(
            'question',
            'translation',
            'multipleChoiceOptions',
            'distractorsSource'
        ));
    }

    public function retryDistractors(): RedirectResponse
    {
        $question = $this->sessionService->getCurrentQuestion();

        if (! $question) {
            return redirect()->route('review.complete');
        }

        $result = $this->distractorService->regenerate($question['translation'], 3);

        return redirect()->route('review.question')
            ->with('distractors_source', $result['source']);
    }

    public function submit(SubmitAnswerRequest $request): View|RedirectResponse
    {
        $result = $this->sessionService->submitAnswer(
            $request->input('answer'),
            (int) $request->input('time_spent', 0)
        );

        if (isset($result['error'])) {
            return redirect()->route('review.index')
                ->with('error', $result['error']);
        }

        return view('review.feedback', compact('result'));
    }

    public function skip(): RedirectResponse
    {
        $result = $this->sessionService->skipCurrent();

        if (isset($result['error'])) {
            return redirect()->route('review.index')
                ->with('error', $result['error']);
        }

        if ($this->sessionService->getCurrentQuestion()) {
            return redirect()->route('review.question')
                ->with('info', __('Question skipped.'));
        }

        return redirect()->route('review.complete');
    }

    /**
     * Explicitly end the session early and redirect to the summary page.
     * POST — must redirect, never render directly.
     */
    public function end(): RedirectResponse
    {
        $summary = $this->sessionService->endSession();

        return redirect()->route('review.complete')
            ->with('session_summary', $summary);
    }

    public function next(): RedirectResponse
    {
        $this->sessionService->advance();

        if ($this->sessionService->getCurrentQuestion()) {
            return redirect()->route('review.question');
        }

        return redirect()->route('review.complete');
    }

    /**
     * Display the session summary.
     *
     * Entry path A — via next() exhausting the queue: session still active, end it here.
     * Entry path B — via end() early termination: summary already stored in flash data.
     */
    public function complete(): View
    {
        $summary = $this->sessionService->hasActiveSession()
            ? $this->sessionService->endSession()
            : session('session_summary', ReviewSessionService::emptySummary());

        $totalTranslations = $this->progressService->getTotalTranslationsForActiveSource(Auth::user());

        return view('review.complete', compact('summary', 'totalTranslations'));
    }
}
