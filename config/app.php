<?php

return [
    'name' => env('APP_NAME', 'LinguaLab'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'providers' => [
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],
    'aliases' => [
        'App'       => Illuminate\Support\Facades\App::class,
        'Auth'      => Illuminate\Support\Facades\Auth::class,
        'DB'        => Illuminate\Support\Facades\DB::class,
        'Route'     => Illuminate\Support\Facades\Route::class,
        'Session'   => Illuminate\Support\Facades\Session::class,
        'View'      => Illuminate\Support\Facades\View::class,
    ],
];
