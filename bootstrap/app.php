<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnforceMaintenanceMode;
use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\TrackPortalAnalytics;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(
            append: [
                AddSecurityHeaders::class,
                EnforceMaintenanceMode::class,
                TrackPortalAnalytics::class,
            ]
        );
        $middleware->alias([
            'admin.access' => EnsureAdminAccess::class,
            'role' => RequireRole::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            return route('login', [
                'locale' => $request->route('locale') ?? config('locales.default', 'pl'),
            ]);
        });
        $middleware->redirectUsersTo(function (Request $request): string {
            return route('account', [
                'locale' => $request->route('locale') ?? config('locales.default', 'pl'),
            ]);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
