<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\AnalyticsEventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\Admin\AdminNavigationController;
use App\Http\Controllers\Admin\AiTranslationController;
use App\Http\Controllers\Admin\AiTranslationSettingsController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ArchiveController as AdminArchiveController;
use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CommerceSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscoveryController;
use App\Http\Controllers\Admin\DiscoverySettingsController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\NewsletterCampaignController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrchestratorController;
use App\Http\Controllers\Admin\OrchestratorSettingsController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StereoGalleryController as AdminStereoGalleryController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayNowNotificationController;
use App\Http\Controllers\SalesDocumentController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StereoGalleryController;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

$defaultLocale = config('locales.default', 'pl');
$supportedLocales = array_keys(config('locales.supported', ['pl' => []]));
$localePattern = implode('|', array_map(
    static fn (string $locale): string => preg_quote($locale, '/'),
    $supportedLocales
));

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])
    ->name('sitemap');

Route::get('/robots.txt', [SeoController::class, 'robots'])
    ->name('robots');

Route::get('/health/ready', ReadinessController::class)
    ->middleware('throttle:60,1')
    ->name('health.ready');

Route::post('/analytics/event', [AnalyticsEventController::class, 'store'])
    ->middleware('throttle:180,1')
    ->name('analytics.event');

Route::redirect('/', '/' . $defaultLocale);

foreach (config('locales.supported', []) as $categoryLocale => $language) {
    $categorySegment = trim(
        (string) ($language['shop_category_segment'] ?? 'shop'),
        '/'
    );

    if ($categorySegment === '' || $categorySegment === 'shop') {
        continue;
    }

    /*
     * Keep {locale} as a real route parameter. SetLocale reads
     * request()->route('locale'), so a route default on a literal
     * /pl/... path is not reliable enough for this middleware.
     */
    Route::get(
        '/{locale}/' . $categorySegment . '/{path}',
        [ShopController::class, 'category']
    )
        ->where([
            'locale' => preg_quote($categoryLocale, '/'),
            'path' => '.*',
        ])
        ->middleware(SetLocale::class)
        ->name('shop.category.' . $categoryLocale);
}

Route::prefix('{locale}')
    ->where(['locale' => $localePattern])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', HomeController::class)
            ->name('home');

        Route::get('/articles/{slug}', [ArticleController::class, 'show'])
            ->name('articles.show');

        Route::post(
            '/currency',
            [CurrencyController::class, 'update']
        )
            ->middleware('throttle:30,1')
            ->name('currency.update');

        Route::get('/shop', [ShopController::class, 'index'])
            ->name('shop.index');

        Route::get('/shop/{slug}', [ShopController::class, 'show'])
            // Last-segment wildcard also supports nested EN category paths.
            ->where('slug', '.*')
            ->name('shop.show');

        Route::get('/lab', [LabController::class, 'index'])
            ->name('lab.index');

        Route::get('/lab/anaglyph-maker', [LabController::class, 'anaglyph'])
            ->name('lab.anaglyph');

        Route::get('/lab/stereo-alignment', [LabController::class, 'stereoAlignment'])
            ->name('lab.stereo-alignment');

        Route::get('/lab/lenticular', [LabController::class, 'lenticular'])
            ->name('lab.lenticular');

        Route::get('/lab/mpo-viewer', [LabController::class, 'mpo'])
            ->name('lab.mpo');

        Route::get('/lab/wigglegram-maker', [LabController::class, 'wigglegram'])
            ->name('lab.wigglegram');



        Route::get('/archive', [ArchiveController::class, 'index'])
            ->name('archive.index');

        Route::get('/archive/{slug}', [ArchiveController::class, 'show'])
            ->name('archive.show');

        Route::get('/gallery', [StereoGalleryController::class, 'index'])
            ->name('gallery.index');

        Route::get('/gallery/submit', [StereoGalleryController::class, 'create'])
            ->middleware('auth')
            ->name('gallery.create');

        Route::post('/gallery', [StereoGalleryController::class, 'store'])
            ->middleware('auth')
            ->name('gallery.store');

        Route::get('/gallery/{galleryItem}', [StereoGalleryController::class, 'show'])
            ->name('gallery.show');

        Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
            ->middleware('throttle:10,1')
            ->name('newsletter.subscribe');

        Route::get('/newsletter/confirm/{subscriber}/{token}', [NewsletterController::class, 'confirm'])
            ->whereNumber('subscriber')
            ->name('newsletter.confirm');

        Route::get('/newsletter/unsubscribe/{subscriber}/{token}', [NewsletterController::class, 'unsubscribeForm'])
            ->whereNumber('subscriber')
            ->name('newsletter.unsubscribe.form');

        Route::post('/newsletter/unsubscribe/{subscriber}/{token}', [NewsletterController::class, 'unsubscribe'])
            ->whereNumber('subscriber')
            ->name('newsletter.unsubscribe');

        Route::get('/cart', [CartController::class, 'index'])
            ->name('cart.index');
        Route::post('/cart/items', [CartController::class, 'store'])
            ->name('cart.items.store');
        Route::patch('/cart/items/{variant}', [CartController::class, 'update'])
            ->name('cart.items.update');
        Route::delete('/cart/items/{variant}', [CartController::class, 'destroy'])
            ->name('cart.items.destroy');
        Route::delete('/cart', [CartController::class, 'clear'])
            ->name('cart.clear');

        Route::get('/checkout', [CheckoutController::class, 'create'])
            ->name('checkout.create');
        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->name('checkout.store');

        Route::get('/order/{order:public_token}', [CheckoutController::class, 'success'])
            ->name('order.success');

        Route::get(
            '/order/{order:public_token}/document/{document}',
            [SalesDocumentController::class, 'publicShow']
        )->name('order.document');

        Route::get(
            '/payment/paynow/return/{order:public_token}',
            [PaymentController::class, 'payNowReturn']
        )->name('payment.paynow.return');

        Route::post(
            '/payment/paynow/retry/{order:public_token}',
            [PaymentController::class, 'retryPayNow']
        )->name('payment.paynow.retry');

        Route::middleware('guest')->group(function () {
            Route::get('/login', [LoginController::class, 'create'])->name('login');
            Route::post('/login', [LoginController::class, 'store'])->name('login.store');

            Route::get('/register', [RegisterController::class, 'create'])->name('register');
            Route::post('/register', [RegisterController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('register.store');

            Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
                ->name('password.request');
            Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('password.email');

            Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
                ->name('password.reset');
            Route::post('/reset-password', [ResetPasswordController::class, 'store'])
                ->middleware('throttle:10,1')
                ->name('password.update');
        });

        Route::middleware('auth')->group(function () {
            Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

            Route::get('/account', [AccountController::class, 'show'])->name('account');
            Route::put('/account/profile', [AccountController::class, 'updateProfile'])
                ->name('account.profile.update');
            Route::put('/account/password', [AccountController::class, 'updatePassword'])
                ->name('account.password.update');


            Route::get('/account/gallery', [StereoGalleryController::class, 'accountIndex'])
                ->name('account.gallery.index');

            Route::delete('/account/gallery/{galleryItem}', [StereoGalleryController::class, 'destroy'])
                ->name('account.gallery.destroy');

            Route::get('/account/orders', [AccountOrderController::class, 'index'])
                ->name('account.orders.index');
            Route::get('/account/orders/{order}', [AccountOrderController::class, 'show'])
                ->name('account.orders.show');
        });
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin.access'])
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/content', [AdminNavigationController::class, 'content'])
            ->name('content');

        Route::resource('articles', AdminArticleController::class)
            ->except(['show']);

        Route::get('/article-categories', [ArticleCategoryController::class, 'index'])
            ->name('article-categories.index');
        Route::post('/article-categories', [ArticleCategoryController::class, 'store'])
            ->name('article-categories.store');
        Route::put('/article-categories/{category}', [ArticleCategoryController::class, 'update'])
            ->name('article-categories.update');
        Route::delete('/article-categories/{category}', [ArticleCategoryController::class, 'destroy'])
            ->name('article-categories.destroy');

        Route::resource('media', MediaController::class)
            ->only(['index', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['media' => 'media']);


        Route::resource('archive', AdminArchiveController::class)
            ->except(['show'])
            ->parameters(['archive' => 'archiveItem']);


        Route::get('/gallery', [AdminStereoGalleryController::class, 'index'])
            ->name('gallery.index');

        Route::get('/gallery/{galleryItem}', [AdminStereoGalleryController::class, 'show'])
            ->name('gallery.show');

        Route::patch('/gallery/{galleryItem}', [AdminStereoGalleryController::class, 'update'])
            ->name('gallery.update');

        Route::get('/translations', [AiTranslationController::class, 'index'])
            ->name('translations');

        Route::post('/translations/{type}/{id}', [AiTranslationController::class, 'translate'])
            ->whereNumber('id')
            ->name('translations.translate');

        Route::get('/analytics', [AnalyticsController::class, 'index'])
            ->name('analytics');

        Route::middleware('role:' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN)
            ->group(function () {
                Route::get('/shop', [AdminNavigationController::class, 'shop'])
                    ->name('shop');

                Route::resource('products', ProductController::class)
                    ->except(['show']);

                Route::get('/product-categories', [ProductCategoryController::class, 'index'])
                    ->name('product-categories.index');
                Route::post('/product-categories', [ProductCategoryController::class, 'store'])
                    ->name('product-categories.store');
                Route::put('/product-categories/{productCategory}', [ProductCategoryController::class, 'update'])
                    ->name('product-categories.update');
                Route::delete('/product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])
                    ->name('product-categories.destroy');

                Route::get('/orders', [AdminOrderController::class, 'index'])
                    ->name('orders.index');
                Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
                    ->name('orders.show');
                Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
                    ->name('orders.status.update');

                Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'updatePayment'])
                    ->name('orders.payment.update');

                Route::get(
                    '/orders/{order}/documents/{document}',
                    [SalesDocumentController::class, 'adminShow']
                )->name('orders.documents.show');

                Route::get('/users', [PlaceholderController::class, 'show'])
                    ->defaults('section', 'users')
                    ->name('users');

                Route::get('/newsletter', [AdminNewsletterController::class, 'index'])
                    ->name('newsletter.index');

                Route::get('/newsletter/subscribers/export', [AdminNewsletterController::class, 'export'])
                    ->name('newsletter.subscribers.export');

                Route::get('/newsletter/campaigns/create', [NewsletterCampaignController::class, 'create'])
                    ->name('newsletter.campaigns.create');

                Route::post('/newsletter/campaigns', [NewsletterCampaignController::class, 'store'])
                    ->name('newsletter.campaigns.store');

                Route::get('/newsletter/campaigns/{campaign}/edit', [NewsletterCampaignController::class, 'edit'])
                    ->whereNumber('campaign')
                    ->name('newsletter.campaigns.edit');

                Route::put('/newsletter/campaigns/{campaign}', [NewsletterCampaignController::class, 'update'])
                    ->whereNumber('campaign')
                    ->name('newsletter.campaigns.update');

                Route::post('/newsletter/campaigns/{campaign}/schedule', [NewsletterCampaignController::class, 'schedule'])
                    ->whereNumber('campaign')
                    ->name('newsletter.campaigns.schedule');

                Route::post('/newsletter/campaigns/{campaign}/send-now', [NewsletterCampaignController::class, 'sendNow'])
                    ->whereNumber('campaign')
                    ->name('newsletter.campaigns.send-now');

                Route::post('/newsletter/campaigns/{campaign}/send-test', [NewsletterCampaignController::class, 'sendTest'])
                    ->whereNumber('campaign')
                    ->name('newsletter.campaigns.send-test');

                Route::delete('/newsletter/campaigns/{campaign}', [NewsletterCampaignController::class, 'destroy'])
                    ->whereNumber('campaign')
                    ->name('newsletter.campaigns.destroy');

                Route::get('/discovery', [DiscoveryController::class, 'index'])
                    ->name('discovery.index');

                Route::post('/discovery/run', [DiscoveryController::class, 'run'])
                    ->name('discovery.run');

                Route::get('/discovery/{candidate}', [DiscoveryController::class, 'show'])
                    ->whereNumber('candidate')
                    ->name('discovery.show');

                Route::patch('/discovery/{candidate}/decision', [DiscoveryController::class, 'decision'])
                    ->whereNumber('candidate')
                    ->name('discovery.decision');

                Route::get('/orchestrator', [OrchestratorController::class, 'index'])
                    ->name('orchestrator.index');

                Route::post('/orchestrator/plans', [OrchestratorController::class, 'createPlan'])
                    ->name('orchestrator.plans.store');

                Route::get('/orchestrator/plans/{plan}', [OrchestratorController::class, 'show'])
                    ->whereNumber('plan')
                    ->name('orchestrator.plans.show');

                Route::patch('/orchestrator/plans/{plan}/approve', [OrchestratorController::class, 'approve'])
                    ->whereNumber('plan')
                    ->name('orchestrator.plans.approve');

                Route::delete('/orchestrator/plans/{plan}', [OrchestratorController::class, 'destroy'])
                    ->whereNumber('plan')
                    ->name('orchestrator.plans.destroy');

                Route::post('/orchestrator/items/{item}/draft', [OrchestratorController::class, 'generateDraft'])
                    ->whereNumber('item')
                    ->name('orchestrator.items.draft');
            });

        Route::middleware('role:' . User::ROLE_SUPER_ADMIN)
            ->group(function () {
                Route::get('/settings', [CommerceSettingsController::class, 'index'])
                    ->name('settings');

                Route::put('/settings', [CommerceSettingsController::class, 'update'])
                    ->name('settings.update');


                Route::get('/settings/ai-translation', [AiTranslationSettingsController::class, 'edit'])
                    ->name('settings.ai-translation');

                Route::put('/settings/ai-translation', [AiTranslationSettingsController::class, 'update'])
                    ->name('settings.ai-translation.update');

                Route::get('/settings/discovery', [DiscoverySettingsController::class, 'edit'])
                    ->name('settings.discovery');

                Route::put('/settings/discovery', [DiscoverySettingsController::class, 'update'])
                    ->name('settings.discovery.update');

                Route::get('/settings/orchestrator', [OrchestratorSettingsController::class, 'edit'])
                    ->name('settings.orchestrator');

                Route::put('/settings/orchestrator', [OrchestratorSettingsController::class, 'update'])
                    ->name('settings.orchestrator.update');
            });
    });


Route::post(
    '/payments/paynow/notification',
    PayNowNotificationController::class
)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('payments.paynow.notification');
