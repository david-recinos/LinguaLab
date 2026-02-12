<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use App\Http\Requests\StoreSourceLanguageRequest;
use App\Http\Requests\StoreTargetLanguageRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LanguageSetupController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $user = Auth::user();
        $languages = Language::orderBy('name')->get();
        $sourceLanguages = $user->sourceLanguages()->with('language')->get();
        $activeSource = $user->activeSourceLanguage();

        $targetLanguages = collect();
        if ($activeSource) {
            $targetLanguages = UserTargetLanguage::where('user_id', $user->id)
                ->where('source_language_id', $activeSource->language_id)
                ->with('targetLanguage')
                ->get();
        }

        return view('languages.index', compact('languages', 'sourceLanguages', 'activeSource', 'targetLanguages'));
    }

    public function storeSource(StoreSourceLanguageRequest $request)
    {
        $user = Auth::user();

        $exists = UserSourceLanguage::where('user_id', $user->id)
            ->where('language_id', $request->language_id)
            ->exists();

        if ($exists) {
            return redirect()->route('languages.index')->with('error', 'Source language already added.');
        }

        $isFirst = $user->sourceLanguages()->count() === 0;

        UserSourceLanguage::create([
            'user_id' => $user->id,
            'language_id' => $request->language_id,
            'is_active' => $isFirst,
        ]);

        return redirect()->route('languages.index')->with('success', 'Source language added.');
    }

    public function destroySource(int $id)
    {
        $sourceLanguage = UserSourceLanguage::findOrFail($id);
        $this->authorize('manage-source-language', $sourceLanguage);

        $wasActive = $sourceLanguage->is_active;
        $userId = $sourceLanguage->user_id;
        $languageId = $sourceLanguage->language_id;

        // Delete associated target languages
        UserTargetLanguage::where('user_id', $userId)
            ->where('source_language_id', $languageId)
            ->delete();

        // Delete translations for this source language
        Auth::user()->translations()
            ->where('source_language_id', $languageId)
            ->delete();

        $sourceLanguage->delete();

        // If was active, activate another one
        if ($wasActive) {
            $next = UserSourceLanguage::where('user_id', $userId)->first();
            if ($next) {
                $next->update(['is_active' => true]);
            }
        }

        return redirect()->route('languages.index')->with('success', 'Source language removed.');
    }

    public function switchSource(int $id)
    {
        $sourceLanguage = UserSourceLanguage::findOrFail($id);
        $this->authorize('manage-source-language', $sourceLanguage);

        // Deactivate all, then activate selected
        UserSourceLanguage::where('user_id', Auth::id())->update(['is_active' => false]);
        $sourceLanguage->update(['is_active' => true]);

        return redirect()->route('languages.index')->with('success', 'Active source language switched.');
    }

    public function storeTarget(StoreTargetLanguageRequest $request)
    {
        $user = Auth::user();

        // Verify user owns this source language
        $ownsSource = UserSourceLanguage::where('user_id', $user->id)
            ->where('language_id', $request->source_language_id)
            ->exists();

        if (!$ownsSource) {
            return redirect()->route('languages.index')->with('error', 'Invalid source language.');
        }

        $exists = UserTargetLanguage::where('user_id', $user->id)
            ->where('source_language_id', $request->source_language_id)
            ->where('target_language_id', $request->target_language_id)
            ->exists();

        if ($exists) {
            return redirect()->route('languages.index')->with('error', 'Target language already added.');
        }

        UserTargetLanguage::create([
            'user_id' => $user->id,
            'source_language_id' => $request->source_language_id,
            'target_language_id' => $request->target_language_id,
        ]);

        return redirect()->route('languages.index')->with('success', 'Target language added.');
    }

    public function destroyTarget(int $id)
    {
        $targetLanguage = UserTargetLanguage::findOrFail($id);
        $this->authorize('manage-target-language', $targetLanguage);

        $targetLanguage->delete();

        return redirect()->route('languages.index')->with('success', 'Target language removed.');
    }
}
