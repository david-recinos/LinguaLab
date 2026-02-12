<?php

use App\Http\Controllers\LanguageSetupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = \Illuminate\Support\Facades\Auth::user();
    $activeSource = $user->activeSourceLanguage();
    $translationCount = $user->translations()->count();
    $sourceLanguageCount = $user->sourceLanguages()->count();
    $targetLanguageCount = $activeSource
        ? \App\Models\UserTargetLanguage::where('user_id', $user->id)
            ->where('source_language_id', $activeSource->language_id)
            ->count()
        : 0;

    return view('dashboard', compact('activeSource', 'translationCount', 'sourceLanguageCount', 'targetLanguageCount'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User CRUD routes
    Route::resource('users', \App\Http\Controllers\UserController::class);

    // Language setup routes
    Route::get('/languages', [LanguageSetupController::class, 'index'])->name('languages.index');
    Route::post('/languages/source', [LanguageSetupController::class, 'storeSource'])->name('languages.source.store');
    Route::delete('/languages/source/{id}', [LanguageSetupController::class, 'destroySource'])->name('languages.source.destroy');
    Route::patch('/languages/source/{id}/switch', [LanguageSetupController::class, 'switchSource'])->name('languages.source.switch');
    Route::post('/languages/target', [LanguageSetupController::class, 'storeTarget'])->name('languages.target.store');
    Route::delete('/languages/target/{id}', [LanguageSetupController::class, 'destroyTarget'])->name('languages.target.destroy');

    // Translation routes
    Route::resource('translations', TranslationController::class);
});

require __DIR__.'/auth.php';
