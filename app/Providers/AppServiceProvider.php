<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceRootUrl((string) config('app.url'));
            URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return route('password.reset', [
                'locale' => app()->getLocale(),
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });
    }
}
