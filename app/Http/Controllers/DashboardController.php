<?php

namespace App\Http\Controllers;

use App\Models\UserTargetLanguage;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeSource = $user->activeSourceLanguage();
        $translationCount = $user->translations()->count();
        $sourceLanguageCount = $user->sourceLanguages()->count();
        $targetLanguageCount = $activeSource
            ? UserTargetLanguage::where('user_id', $user->id)
                ->where('source_language_id', $activeSource->language_id)
                ->count()
            : 0;

        return view('dashboard', compact('activeSource', 'translationCount', 'sourceLanguageCount', 'targetLanguageCount'));
    }
}
