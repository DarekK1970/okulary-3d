<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class CurrencyController extends Controller
{
    public function update(
        Request $request,
        string $locale,
        CurrencyService $currencies
    ): RedirectResponse {
        $validated = $request->validate([
            'currency' => [
                'required',
                'string',
                'size:3',
            ],
        ]);

        $code = strtoupper(
            trim(
                (string) $validated['currency']
            )
        );

        if (! $currencies->isSelectable($code)) {
            throw ValidationException
                ::withMessages([
                    'currency' => __(
                        'currency.errors.unavailable'
                    ),
                ]);
        }

        $request->session()->put(
            CurrencyService::SESSION_KEY,
            $code
        );

        $cookie = Cookie::make(
            CurrencyService::COOKIE_KEY,
            $code,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );

        return back()->withCookie($cookie);
    }
}
