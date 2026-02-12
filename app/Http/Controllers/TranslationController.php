<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Models\UserTargetLanguage;
use App\Models\WordType;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TranslationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        if (!$activeSource) {
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

        $translations = $query->latest()->paginate(15)->withQueryString();

        $targetLanguages = UserTargetLanguage::where('user_id', $user->id)
            ->where('source_language_id', $activeSource->language_id)
            ->with('targetLanguage')
            ->get();

        return view('translations.index', compact('translations', 'activeSource', 'targetLanguages'));
    }

    public function create()
    {
        $user = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        if (!$activeSource) {
            return redirect()->route('languages.index')
                ->with('error', 'Please set up your source language first.');
        }

        $targetLanguages = UserTargetLanguage::where('user_id', $user->id)
            ->where('source_language_id', $activeSource->language_id)
            ->with('targetLanguage')
            ->get();

        if ($targetLanguages->isEmpty()) {
            return redirect()->route('languages.index')
                ->with('error', 'Please add at least one target language first.');
        }

        $wordTypes = WordType::orderBy('name')->get();

        return view('translations.create', compact('activeSource', 'targetLanguages', 'wordTypes'));
    }

    public function store(StoreTranslationRequest $request)
    {
        $user = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['source_language_id'] = $activeSource->language_id;

        if ($data['type'] !== 'word') {
            $data['word_type_id'] = null;
        }

        Translation::create($data);

        return redirect()->route('translations.index')->with('success', 'Translation created.');
    }

    public function show(Translation $translation)
    {
        $this->authorize('manage-translation', $translation);
        $translation->load(['sourceLanguage', 'targetLanguage', 'wordType']);

        return view('translations.show', compact('translation'));
    }

    public function edit(Translation $translation)
    {
        $this->authorize('manage-translation', $translation);

        $user = Auth::user();
        $activeSource = $user->activeSourceLanguage();

        $targetLanguages = UserTargetLanguage::where('user_id', $user->id)
            ->where('source_language_id', $translation->source_language_id)
            ->with('targetLanguage')
            ->get();

        $wordTypes = WordType::orderBy('name')->get();

        return view('translations.edit', compact('translation', 'activeSource', 'targetLanguages', 'wordTypes'));
    }

    public function update(UpdateTranslationRequest $request, Translation $translation)
    {
        $this->authorize('manage-translation', $translation);

        $data = $request->validated();

        if ($data['type'] !== 'word') {
            $data['word_type_id'] = null;
        }

        $translation->update($data);

        return redirect()->route('translations.index')->with('success', 'Translation updated.');
    }

    public function destroy(Translation $translation)
    {
        $this->authorize('manage-translation', $translation);
        $translation->delete();

        return redirect()->route('translations.index')->with('success', 'Translation deleted.');
    }
}
