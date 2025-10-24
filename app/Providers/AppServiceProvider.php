<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define gate for admin-only actions using spatie permissions
        Gate::define('admin-only', function (User $user) {
            return $user->hasRole('admin');
        });

        // Define gate for updating users (admin or self)
        Gate::define('update-user', function (User $user, User $targetUser) {
            return $user->hasRole('admin') || $user->id === $targetUser->id;
        });
    }
}
