<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /** @var list<string> */
    private const PRIVATE_ROUTE_PATTERNS = [
        'admin.*',
        'login',
        'login.store',
        'register',
        'register.store',
        'password.*',
        'account',
        'account.*',
        'cart.*',
        'checkout.*',
        'order.*',
        'payment.*',
        'newsletter.confirm',
        'newsletter.unsubscribe.form',
        'newsletter.unsubscribe',
        'health.ready',
    ];

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $response = $next($request);

        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN'
        );

        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );

        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), usb=(), payment=(self)'
        );

        if (
            app()->environment('production')
            && (
                $request->isSecure()
                || str_starts_with(
                    (string) config('app.url'),
                    'https://'
                )
            )
        ) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000'
            );
        }

        if (
            $this->isPrivateRoute(
                $request->route()?->getName()
            )
        ) {
            $response->headers->set(
                'Cache-Control',
                'no-store, private, max-age=0'
            );

            $response->headers->set(
                'Pragma',
                'no-cache'
            );
        }

        return $response;
    }

    private function isPrivateRoute(
        ?string $routeName
    ): bool {
        if (! $routeName) {
            return false;
        }

        foreach (
            self::PRIVATE_ROUTE_PATTERNS
            as $pattern
        ) {
            if (
                Str::is(
                    $pattern,
                    $routeName
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
