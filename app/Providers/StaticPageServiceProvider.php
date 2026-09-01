<?php

namespace App\Providers;

use App\Http\Controllers\Admin\StaticPageController as AdminStaticPageController;
use App\Http\Controllers\StaticPageController;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StaticPageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $supportedLocales =
            array_keys(
                config(
                    'locales.supported',
                    [
                        'pl' => [],
                    ]
                )
            );

        $localePattern = implode(
            '|',
            array_map(
                static fn (
                    string $locale
                ): string =>
                    preg_quote(
                        $locale,
                        '/'
                    ),
                $supportedLocales
            )
        );

        Route::middleware([
            'web',
            SetLocale::class,
        ])
            ->prefix(
                '{locale}/info'
            )
            ->where([
                'locale' =>
                    $localePattern,
            ])
            ->group(function (): void {
                Route::get(
                    '/{key}',
                    [
                        StaticPageController::class,
                        'show',
                    ]
                )
                    ->where(
                        'key',
                        '[a-z0-9\-]+'
                    )
                    ->name(
                        'static-pages.show'
                    );
            });

        Route::middleware([
            'web',
            'auth',
            'admin.access',
            'role:'
                . User::ROLE_ADMIN
                . ','
                . User::ROLE_SUPER_ADMIN,
        ])
            ->prefix(
                'admin/static-pages'
            )
            ->name(
                'admin.static-pages.'
            )
            ->group(function (): void {
                Route::get(
                    '/',
                    [
                        AdminStaticPageController::class,
                        'index',
                    ]
                )->name('index');

                Route::get(
                    '/{staticPage}/edit',
                    [
                        AdminStaticPageController::class,
                        'edit',
                    ]
                )
                    ->whereNumber(
                        'staticPage'
                    )
                    ->name('edit');

                Route::put(
                    '/{staticPage}',
                    [
                        AdminStaticPageController::class,
                        'update',
                    ]
                )
                    ->whereNumber(
                        'staticPage'
                    )
                    ->name('update');

                Route::post(
                    '/{staticPage}/translate',
                    [
                        AdminStaticPageController::class,
                        'translate',
                    ]
                )
                    ->whereNumber(
                        'staticPage'
                    )
                    ->name('translate');
            });
    }
}
