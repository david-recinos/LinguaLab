<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Jobs\GenerateDistractorsJob;
use App\Models\Translation;
use App\Models\WordType;
use App\Services\UserLanguageService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TranslationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly UserLanguageService $languageService) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user         = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return redirect()->route('languages.index')
                ->with('error', 'Please set up your source language first.');
        }

        $query = $user->translations()
            ->where('source_language_id', $activeSource->language_id)
            ->with(['sourceLanguage', 'targetLanguage', 'wordType']);

        if ($request->filled('target_language_id')) {
            $query->where('target_language_id', $request->target_language_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('source_text', 'like', "%{$search}%")
                    ->orWhere('target_text', 'like', "%{$search}%");
            });
        }

        $translations    = $query->latest()->paginate(15)->withQueryString();
        $targetLanguages = $this->languageService->getTargetLanguagesForActiveSource($user);

        return view('translations.index', compact('translations', 'activeSource', 'targetLanguages'));
    }

    public function create(): View|RedirectResponse
    {
        $user         = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return redirect()->route('languages.index')
                ->with('error', 'Please set up your source language first.');
        }

        $targetLanguages = $this->languageService->getTargetLanguagesForActiveSource($user);

        if ($targetLanguages->isEmpty()) {
            return redirect()->route('languages.index')
                ->with('error', 'Please add at least one target language first.');
        }

        $wordTypes = WordType::orderBy('name')->get();

        return view('translations.create', compact('activeSource', 'targetLanguages', 'wordTypes'));
    }

    public function store(StoreTranslationRequest $request): RedirectResponse
    {
        $user         = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        if (! $activeSource) {
            return redirect()->route('languages.index')
                ->with('error', 'Please set up your source language first.');
        }

        if (! $this->languageService->ownsTargetLanguageForActiveSource($user, $request->target_language_id)) {
            return redirect()->route('translations.create')
                ->with('error', 'Invalid target language.');
        }

        $data                      = $request->validated();
        $data['user_id']           = $user->id;
        $data['source_language_id'] = $activeSource->language_id;
        $data['next_review_at']    = now();

        if ($data['type'] !== 'word') {
            $data['word_type_id'] = null;
        }

        $translation = Translation::create($data);

        if (config('ai.features.distractors.enabled', true)) {
            GenerateDistractorsJob::dispatch(collect([$translation]));
        }

        Log::info('Translation created', [
            'user_id'            => $user->id,
            'translation_id'     => $translation->id,
            'type'               => $data['type'],
            'source_language_id' => $data['source_language_id'],
            'target_language_id' => $data['target_language_id'],
        ]);

        return redirect()->route('translations.index')->with('success', 'Translation created.');
    }

    public function show(Translation $translation): View
    {
        $this->authorize('manage-translation', $translation);
        $translation->load(['sourceLanguage', 'targetLanguage', 'wordType']);

        return view('translations.show', compact('translation'));
    }

    public function edit(Translation $translation): View
    {
        $this->authorize('manage-translation', $translation);

        $targetLanguages = $this->languageService->getTargetLanguagesForSource(
            Auth::user(),
            $translation->source_language_id
        );
        $wordTypes = WordType::orderBy('name')->get();

        return view('translations.edit', compact('translation', 'targetLanguages', 'wordTypes'));
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): RedirectResponse
    {
        $this->authorize('manage-translation', $translation);

        $user = Auth::user();

        if (! $this->languageService->ownsTargetLanguage($user, $translation->source_language_id, $request->target_language_id)) {
            return redirect()->route('translations.edit', $translation)
                ->with('error', 'Invalid target language.');
        }

        $data = $request->validated();

        if ($data['type'] !== 'word') {
            $data['word_type_id'] = null;
        }

        $translation->update($data);

        $changes = array_diff_key($translation->getChanges(), array_flip(['source_text', 'target_text', 'notes']));

        Log::info('Translation updated', [
            'user_id'        => $user->id,
            'translation_id' => $translation->id,
            'changed_fields' => array_keys($changes),
        ]);

        return redirect()->route('translations.index')->with('success', 'Translation updated.');
    }

    public function destroy(Translation $translation): RedirectResponse
    {
        $this->authorize('manage-translation', $translation);

        Log::info('Translation deleted', [
            'user_id'        => Auth::id(),
            'translation_id' => $translation->id,
        ]);

        $translation->delete();

        return redirect()->route('translations.index')->with('success', 'Translation deleted.');
    }
}
