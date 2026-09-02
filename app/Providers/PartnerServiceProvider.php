<?php

namespace App\Providers;

use App\Http\Controllers\Admin\PartnerLinkController as AdminPartnerLinkController;
use App\Http\Controllers\PartnerLinkController;
use App\Http\Middleware\SetLocale;
use App\Enums\PartnerLinkStatus;
use App\Models\PartnerLink;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PartnerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $supportedLocales = array_keys(
            config('locales.supported', ['pl' => []])
        );

        $localePattern = implode('|', array_map(
            static fn (string $locale): string => preg_quote($locale, '/'),
            $supportedLocales
        ));

        Route::prefix('{locale}')
            ->where(['locale' => $localePattern])
            ->middleware(['web', SetLocale::class])
            ->group(function (): void {
                Route::get('/partners', [PartnerLinkController::class, 'create'])
                    ->name('partners.create');

                Route::post('/partners', [PartnerLinkController::class, 'store'])
                    ->middleware('throttle:5,60')
                    ->name('partners.store');

                Route::get(
                    '/partners/verify/{partner}/{token}',
                    [PartnerLinkController::class, 'verify']
                )
                    ->whereNumber('partner')
                    ->middleware('throttle:30,1')
                    ->name('partners.verify');

                Route::post(
                    '/partners/{partner}/resend-verification',
                    [PartnerLinkController::class, 'resend']
                )
                    ->whereNumber('partner')
                    ->middleware('throttle:3,60')
                    ->name('partners.resend');

                Route::get(
                    '/partners/{partner}/go',
                    [PartnerLinkController::class, 'go']
                )
                    ->whereNumber('partner')
                    ->middleware('throttle:120,1')
                    ->name('partners.go');
            });

        View::composer('partials.footer', function ($view): void {
            /*
             * Some lightweight feature tests boot and render the public layout
             * without running database migrations. The footer must therefore
             * fail open until the partner_links table exists.
             *
             * This also makes a production deployment safer during the short
             * interval between pulling new application code and running the
             * migration that creates the partner table.
             */
            if (! Schema::hasTable((new PartnerLink())->getTable())) {
                $view->with('footerPartners', collect());

                return;
            }

            $partners = PartnerLink::query()
                ->where('status', PartnerLinkStatus::Active)
                ->whereNotNull('email_verified_at')
                ->whereNotNull('approved_at')
                ->orderBy('name')
                ->limit(60)
                ->get();

            $view->with('footerPartners', $partners);
        });

        Route::prefix('admin/partners')
            ->name('admin.partners.')
            ->middleware([
                'web',
                'auth',
                'admin.access',
                'role:' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN,
            ])
            ->group(function (): void {
                Route::get('/', [AdminPartnerLinkController::class, 'index'])
                    ->name('index');
                Route::get('/{partner}/edit', [AdminPartnerLinkController::class, 'edit'])
                    ->whereNumber('partner')
                    ->name('edit');
                Route::put('/{partner}', [AdminPartnerLinkController::class, 'update'])
                    ->whereNumber('partner')
                    ->name('update');
                Route::patch('/{partner}/approve', [AdminPartnerLinkController::class, 'approve'])
                    ->whereNumber('partner')
                    ->name('approve');
                Route::patch('/{partner}/revoke', [AdminPartnerLinkController::class, 'revoke'])
                    ->whereNumber('partner')
                    ->name('revoke');
                Route::patch('/{partner}/reject', [AdminPartnerLinkController::class, 'reject'])
                    ->whereNumber('partner')
                    ->name('reject');
                Route::patch('/{partner}/ban', [AdminPartnerLinkController::class, 'ban'])
                    ->whereNumber('partner')
                    ->name('ban');
                Route::post('/{partner}/check-backlink', [AdminPartnerLinkController::class, 'checkBacklink'])
                    ->whereNumber('partner')
                    ->middleware('throttle:20,1')
                    ->name('check-backlink');
                Route::delete('/{partner}', [AdminPartnerLinkController::class, 'destroy'])
                    ->whereNumber('partner')
                    ->name('destroy');
            });
    }
}
