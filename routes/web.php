<?php

use App\Http\Controllers\AiAuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageSetupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

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

    // Review routes
    Route::prefix('review')->name('review.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::post('/start', [ReviewController::class, 'start'])->name('start');
        Route::get('/question', [ReviewController::class, 'question'])->name('question');
        Route::post('/submit', [ReviewController::class, 'submit'])->name('submit');
        Route::post('/skip', [ReviewController::class, 'skip'])->name('skip');
        Route::post('/next', [ReviewController::class, 'next'])->name('next');
        Route::post('/end', [ReviewController::class, 'end'])->name('end');
        Route::get('/complete', [ReviewController::class, 'complete'])->name('complete');
        Route::post('/retry-distractors', [ReviewController::class, 'retryDistractors'])->name('retry-distractors');
    });

    // AI Audit Log routes (admin only)
    Route::prefix('ai-audit-logs')->name('ai-audit-logs.')->group(function () {
        Route::get('/', [AiAuditLogController::class, 'index'])->name('index');
        Route::get('/status', [AiAuditLogController::class, 'status'])->name('status');
        Route::get('/{aiAuditLog}', [AiAuditLogController::class, 'show'])->name('show');
    });
});

require __DIR__.'/auth.php';
