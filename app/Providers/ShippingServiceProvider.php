<?php

namespace App\Providers;

use App\Http\Controllers\Admin\FurgonetkaController;
use App\Http\Controllers\Admin\ShippingSettingsController;
use App\Http\Controllers\ShippingQuoteController;
use App\Http\Middleware\SetLocale;
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
                'locale' => $localePattern,
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
            ->prefix('admin/shipping')
            ->name('admin.shipping.')
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
                )->name('settings.update');

                Route::put(
                    '/weights',
                    [
                        ShippingSettingsController::class,
                        'updateWeights',
                    ]
                )->name('weights.update');

                Route::post(
                    '/rates',
                    [
                        ShippingSettingsController::class,
                        'storeRate',
                    ]
                )->name('rates.store');

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
                    ->name('rates.update');


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

                Route::get(
                    '/furgonetka/connect',
                    [
                        FurgonetkaController::class,
                        'connect',
                    ]
                )->name(
                    'furgonetka.connect'
                );

                Route::get(
                    '/furgonetka/callback',
                    [
                        FurgonetkaController::class,
                        'callback',
                    ]
                )->name(
                    'furgonetka.callback'
                );

                Route::post(
                    '/furgonetka/disconnect',
                    [
                        FurgonetkaController::class,
                        'disconnect',
                    ]
                )->name(
                    'furgonetka.disconnect'
                );

                Route::post(
                    '/furgonetka/test',
                    [
                        FurgonetkaController::class,
                        'test',
                    ]
                )->name(
                    'furgonetka.test'
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
                    ->name('rates.destroy');

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
            ->prefix('admin/orders/{order}/shipping/furgonetka')
            ->name('admin.shipping.furgonetka.')
            ->group(function (): void {
                Route::get(
                    '/',
                    [
                        FurgonetkaController::class,
                        'orderPage',
                    ]
                )->name('order');

                Route::post(
                    '/shipments',
                    [
                        FurgonetkaController::class,
                        'createShipment',
                    ]
                )->name('shipments.create');

                Route::post(
                    '/shipments/{shipment}/order',
                    [
                        FurgonetkaController::class,
                        'orderShipment',
                    ]
                )
                    ->whereNumber('shipment')
                    ->name('shipments.order');

                Route::post(
                    '/shipments/{shipment}/tracking',
                    [
                        FurgonetkaController::class,
                        'tracking',
                    ]
                )
                    ->whereNumber('shipment')
                    ->name('shipments.tracking');

                Route::get(
                    '/shipments/{shipment}/label',
                    [
                        FurgonetkaController::class,
                        'label',
                    ]
                )
                    ->whereNumber('shipment')
                    ->name('shipments.label');
            });
    }
}
