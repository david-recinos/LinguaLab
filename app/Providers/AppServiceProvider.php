<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Translation;
use App\Models\User;
use App\Models\UserSourceLanguage;
use App\Models\UserTargetLanguage;
use App\Services\AI\DistractorGeneratorInterface;
use App\Services\AI\OpenAiCompatibleGenerator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DistractorGeneratorInterface::class, OpenAiCompatibleGenerator::class);
    }

    public function boot(): void
    {
        Gate::define('admin-only', fn (User $user) => $user->hasRole('admin'));

        Gate::define('update-user', fn (User $user, User $targetUser) =>
            $user->hasRole('admin') || $user->id === $targetUser->id
        );

        Gate::define('manage-translation', fn (User $user, Translation $translation) =>
            $user->id === $translation->user_id
        );

        Gate::define('manage-source-language', fn (User $user, UserSourceLanguage $sourceLanguage) =>
            $user->id === $sourceLanguage->user_id
        );

        Gate::define('manage-target-language', fn (User $user, UserTargetLanguage $targetLanguage) =>
            $user->id === $targetLanguage->user_id
        );
    }
}
