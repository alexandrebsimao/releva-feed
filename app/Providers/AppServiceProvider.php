<?php

namespace App\Providers;

use App\AI\AiClient;
use App\AI\LaravelAiClient;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiClient::class, LaravelAiClient::class);
    }

    public function boot(): void
    {
        // Never let a stale local HMR marker redirect production assets to localhost.
        Vite::useHotFile(app()->environment('local')
            ? public_path('hot')
            : storage_path('framework/vite.hot'));

        Gate::define('admin', fn (User $user): bool => $user->is_admin === true);
    }
}
