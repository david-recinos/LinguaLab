<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSourceLanguageRequest;
use App\Http\Requests\StoreTargetLanguageRequest;
use App\Models\Language;
use App\Models\Translation;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $isFirst = $user->sourceLanguages()->count() === 0;

        try {
            $sourceLanguage = UserSourceLanguage::create([
                'user_id' => $user->id,
                'language_id' => $request->language_id,
                'is_active' => $isFirst,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return redirect()->route('languages.index')->with('error', 'Source language already added.');
            }
            throw $e;
        }

        Log::info('Source language added', [
            'user_id' => $user->id,
            'language_id' => $request->language_id,
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

        Log::info('Source language removed', [
            'user_id' => $userId,
            'source_language_id' => $languageId,
            'was_active' => $wasActive,
        ]);

        DB::transaction(function () use ($userId, $languageId, $sourceLanguage, $wasActive) {
            UserTargetLanguage::where('user_id', $userId)
                ->where('source_language_id', $languageId)
                ->delete();

            Translation::where('user_id', $userId)
                ->where('source_language_id', $languageId)
                ->delete();

            $sourceLanguage->delete();

            if ($wasActive) {
                $next = UserSourceLanguage::where('user_id', $userId)->orderBy('id')->first();
                if ($next) {
                    $next->update(['is_active' => true]);
                }
            }
        });

        return redirect()->route('languages.index')->with('success', 'Source language removed.');
    }

    public function switchSource(int $id)
    {
        $sourceLanguage = UserSourceLanguage::findOrFail($id);
        $this->authorize('manage-source-language', $sourceLanguage);

        $oldActive = UserSourceLanguage::where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        DB::transaction(function () use ($sourceLanguage) {
            UserSourceLanguage::where('user_id', Auth::id())->update(['is_active' => false]);
            $sourceLanguage->update(['is_active' => true]);
        });

        Log::info('Source language switched', [
            'user_id' => Auth::id(),
            'old_active_id' => $oldActive?->language_id,
            'new_active_id' => $sourceLanguage->language_id,
        ]);

        return redirect()->route('languages.index')->with('success', 'Active source language switched.');
    }

    public function storeTarget(StoreTargetLanguageRequest $request)
    {
        $user = Auth::user();

        $ownsSource = UserSourceLanguage::where('user_id', $user->id)
            ->where('language_id', $request->source_language_id)
            ->exists();

        if (! $ownsSource) {
            return redirect()->route('languages.index')->with('error', 'Invalid source language.');
        }

        try {
            UserTargetLanguage::create([
                'user_id' => $user->id,
                'source_language_id' => $request->source_language_id,
                'target_language_id' => $request->target_language_id,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return redirect()->route('languages.index')->with('error', 'Target language already added.');
            }
            throw $e;
        }

        Log::info('Target language added', [
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

        $userId = $targetLanguage->user_id;
        $sourceLanguageId = $targetLanguage->source_language_id;
        $targetLanguageId = $targetLanguage->target_language_id;

        Log::info('Target language removed', [
            'user_id' => $userId,
            'source_language_id' => $sourceLanguageId,
            'target_language_id' => $targetLanguageId,
        ]);

        DB::transaction(function () use ($userId, $sourceLanguageId, $targetLanguageId, $targetLanguage) {
            Translation::where('user_id', $userId)
                ->where('source_language_id', $sourceLanguageId)
                ->where('target_language_id', $targetLanguageId)
                ->delete();

            $targetLanguage->delete();
        });

        return redirect()->route('languages.index')->with('success', 'Target language removed.');
    }
}
