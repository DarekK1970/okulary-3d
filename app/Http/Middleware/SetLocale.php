<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->route('locale');
        $supportedLocales = array_keys(config('locales.supported', []));

        if (! in_array($locale, $supportedLocales, true)) {
            abort(404);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
