<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackAuthenticatedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->suspended_at !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login', [
                'locale' => $request->route('locale') ?? config('locales.default', 'pl'),
            ])->withErrors(['email' => __('portal_auth.messages.account_suspended')]);
        }

        $routeLocale = $request->route('locale');
        $locale = is_string($routeLocale) && array_key_exists($routeLocale, config('locales.supported', []))
            ? $routeLocale
            : ($user->preferred_locale ?: config('locales.default', 'pl'));
        $activityIsStale = $user->last_activity_at === null
            || $user->last_activity_at->lt(now()->subMinutes(5));

        if ($activityIsStale || $user->preferred_locale !== $locale) {
            $user->forceFill([
                'last_activity_at' => now(),
                'preferred_locale' => $locale,
            ])->saveQuietly();
        }

        return $next($request);
    }
}
