<?php

namespace App\Providers;

use App\Http\Controllers\Admin\FurgonetkaController;
use App\Http\Controllers\Admin\ShippingSettingsController;
use App\Http\Controllers\Api\FurgonetkaUniversalController;
use App\Http\Controllers\ShippingQuoteController;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VerifyFurgonetkaUniversalToken;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $supportedLocales = array_keys(
            config(
                'locales.supported',
                ['pl' => []]
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
            ->prefix('{locale}')
            ->where([
                'locale' =>
                    $localePattern,
            ])
            ->group(function (): void {
                Route::get(
                    '/checkout/shipping-options',
                    ShippingQuoteController::class
                )
                    ->middleware(
                        'throttle:60,1'
                    )
                    ->name(
                        'checkout.shipping-options'
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
                'admin/shipping'
            )
            ->name(
                'admin.shipping.'
            )
            ->group(function (): void {
                Route::get(
                    '/',
                    [
                        ShippingSettingsController::class,
                        'index',
                    ]
                )->name('index');

                Route::put(
                    '/settings',
                    [
                        ShippingSettingsController::class,
                        'updateSettings',
                    ]
                )->name(
                    'settings.update'
                );

                Route::put(
                    '/weights',
                    [
                        ShippingSettingsController::class,
                        'updateWeights',
                    ]
                )->name(
                    'weights.update'
                );

                Route::post(
                    '/rates',
                    [
                        ShippingSettingsController::class,
                        'storeRate',
                    ]
                )->name(
                    'rates.store'
                );

                Route::put(
                    '/rates/{shippingRate}',
                    [
                        ShippingSettingsController::class,
                        'updateRate',
                    ]
                )
                    ->whereNumber(
                        'shippingRate'
                    )
                    ->name(
                        'rates.update'
                    );

                Route::delete(
                    '/rates/{shippingRate}',
                    [
                        ShippingSettingsController::class,
                        'destroyRate',
                    ]
                )
                    ->whereNumber(
                        'shippingRate'
                    )
                    ->name(
                        'rates.destroy'
                    );

                Route::get(
                    '/furgonetka',
                    [
                        FurgonetkaController::class,
                        'settings',
                    ]
                )->name(
                    'furgonetka.settings'
                );

                Route::put(
                    '/furgonetka',
                    [
                        FurgonetkaController::class,
                        'updateSettings',
                    ]
                )->name(
                    'furgonetka.update'
                );

                Route::post(
                    '/furgonetka/token',
                    [
                        FurgonetkaController::class,
                        'generateToken',
                    ]
                )->name(
                    'furgonetka.token.generate'
                );
            });

        /*
         * Furgonetka Universal E-commerce Integration calls
         * these endpoints directly.
         *
         * Deliberately no "web" middleware:
         * - no session,
         * - no CSRF token,
         * - Authorization token is verified by dedicated
         *   constant-time middleware.
         */
        Route::middleware([
            VerifyFurgonetkaUniversalToken::class,
            'throttle:120,1',
        ])->group(function (): void {
            Route::get(
                '/orders',
                [
                    FurgonetkaUniversalController::class,
                    'orders',
                ]
            )->name(
                'furgonetka.universal.orders'
            );

            Route::post(
                '/orders/{id}/tracking_number',
                [
                    FurgonetkaUniversalController::class,
                    'tracking',
                ]
            )
                ->where(
                    'id',
                    '[A-Za-z0-9\-]+'
                )
                ->name(
                    'furgonetka.universal.tracking'
                );
        });
    }
}
