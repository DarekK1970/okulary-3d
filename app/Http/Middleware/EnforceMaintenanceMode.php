<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceMaintenanceMode
{
    public function __construct(
        private readonly MaintenanceModeService $maintenance
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (
            ! $this->maintenance->enabled()
            || $this->maintenance->isIpAllowed($request->ip())
            || $this->isExempt($request)
        ) {
            return $next($request);
        }

        $this->applyRequestLocale($request);

        return response()
            ->view(
                'maintenance',
                [
                    'currentIp' => $request->ip(),
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            )
            ->header('Retry-After', '3600')
            ->header(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function isExempt(Request $request): bool
    {
        if (
            $request->is('admin')
            || $request->is('admin/*')
            || $request->is('health/ready')
            || $request->is('up')
        ) {
            return true;
        }

        $routeName = $request->route()?->getName();

        return in_array(
            $routeName,
            [
                'health.ready',
                'login',
                'login.store',
                'logout',
                'password.request',
                'password.email',
                'password.reset',
                'password.update',
                'payments.paynow.notification',
                'payment.paynow.return',
                'payment.paynow.retry',
                'order.success',
                'order.document',
            ],
            true
        );
    }

    private function applyRequestLocale(Request $request): void
    {
        $defaultLocale = config('locales.default', 'pl');
        $supported = config('locales.supported', []);
        $locale = (string) (
            $request->route('locale')
            ?? $defaultLocale
        );

        if (! array_key_exists($locale, $supported)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);
    }
}
