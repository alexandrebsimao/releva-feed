<?php

namespace App\Providers;

use App\AI\AiClient;
use App\AI\LaravelAiClient;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            $minutes = (int) config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject('Confirme seu e-mail · '.config('app.name'))
                ->greeting('Olá, '.$notifiable->name.'!')
                ->line('Uma conta no '.config('app.name').' foi criada para você.')
                ->line('Para começar a usar, confirme o seu endereço de e-mail clicando no botão abaixo.')
                ->action('Confirmar e-mail', $url)
                ->line("O link expira em {$minutes} minutos. Se ele expirar, entre no app e peça um novo.")
                ->line('Se você não esperava este e-mail, pode ignorá-lo com segurança.')
                ->salutation('Equipe '.config('app.name'));
        });
    }
}
