<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\Admin\AdminNavigationController;
use App\Http\Controllers\Admin\AiTranslationController;
use App\Http\Controllers\Admin\AiTranslationSettingsController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ArchiveController as AdminArchiveController;
use App\Http\Controllers\Admin\ArticleAiController;
use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CommerceSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscoveryController;
use App\Http\Controllers\Admin\DiscoverySettingsController;
use App\Http\Controllers\Admin\FalAiSettingsController;
use App\Http\Controllers\Admin\MaintenanceSettingsController;
use App\Http\Controllers\Admin\MarketplaceCategoryController;
use App\Http\Controllers\Admin\MarketplaceProductController;
use App\Http\Controllers\Admin\MarketplaceShippingProviderController;
use App\Http\Controllers\Admin\PlanSettingsController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsletterCampaignController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\OrchestratorController;
use App\Http\Controllers\Admin\OrchestratorSettingsController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StaticPageController as AdminStaticPageController;
use App\Http\Controllers\Admin\StereoGalleryController as AdminStereoGalleryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AiLenticularProjectController;
use App\Http\Controllers\AnalyticsEventController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\FalAiInputController;
use App\Http\Controllers\FalAiWebhookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\LenticularProjectController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PayNowNotificationController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\SalesDocumentController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\StereoGalleryController;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

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

Route::get('/integrations/fal/input/{file}', FalAiInputController::class)
    ->middleware('signed')
    ->name('integrations.fal.input');
Route::post('/integrations/fal/webhook', FalAiWebhookController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware('throttle:120,1')
    ->name('integrations.fal.webhook');

Route::redirect('/', '/'.$defaultLocale);

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
        '/{locale}/'.$categorySegment.'/{path}',
        [ShopController::class, 'category']
    )
        ->where([
            'locale' => preg_quote($categoryLocale, '/'),
            'path' => '.*',
        ])
        ->middleware(SetLocale::class)
        ->name('shop.category.'.$categoryLocale);
}
Route::prefix('{locale}')
    ->where(['locale' => $localePattern])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', HomeController::class)
            ->name('home');

        Route::get('/info/{key}', [StaticPageController::class, 'show'])
            ->where('key', '[a-z0-9-]+')
            ->name('static-pages.show');

        Route::get('/articles', [ArticleController::class, 'index'])
            ->name('articles.index');

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

        Route::get('/marketplace', [MarketplaceController::class, 'index'])
            ->name('marketplace.index');

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
        Route::get('/lab/lenticular/studio', [LabController::class, 'lenticularStudio'])
            ->middleware('auth')
            ->name('lab.lenticular.studio');
        Route::middleware('auth')->prefix('/lab/lenticular/studio')->name('lab.lenticular.ai.')->group(function (): void {
            Route::get('/two-photos', [AiLenticularProjectController::class, 'createPair'])->name('pair.create');
            Route::post('/two-photos', [AiLenticularProjectController::class, 'storePair'])->name('pair.store');
            Route::get('/one-photo', [AiLenticularProjectController::class, 'createSingle'])->name('single.create');
            Route::post('/one-photo', [AiLenticularProjectController::class, 'storeSingle'])->name('single.store');
            Route::get('/photo-sequence', [AiLenticularProjectController::class, 'createSequence'])->name('sequence.create');
            Route::post('/photo-sequence', [AiLenticularProjectController::class, 'storeSequence'])->name('sequence.store');
            Route::get('/jobs/{job}', [AiLenticularProjectController::class, 'showJob'])->name('jobs.show');
        });
        Route::middleware('auth')->prefix('/lab/lenticular/projects')->name('lab.projects.')->group(function (): void {
            Route::get('/create', [LenticularProjectController::class, 'create'])->name('create');
            Route::post('/', [LenticularProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [LenticularProjectController::class, 'show'])->name('show');
            Route::post('/{project}/video', [LenticularProjectController::class, 'uploadVideo'])->name('video.store');
            Route::post('/{project}/images', [LenticularProjectController::class, 'uploadImages'])->name('images.store');
            Route::post('/{project}/frames', [LenticularProjectController::class, 'selectFrames'])->name('frames.store');
            Route::post('/{project}/alignment', [LenticularProjectController::class, 'alignFrames'])->name('alignment.store');
            Route::post('/{project}/finalize', [LenticularProjectController::class, 'finalize'])->name('finalize.store');
            Route::get('/{project}/download', [LenticularProjectController::class, 'download'])->name('download');
            Route::delete('/{project}', [LenticularProjectController::class, 'destroy'])->name('destroy');
        });
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
            Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
            Route::post('/plans/purchase', [PlanController::class, 'purchase'])->middleware('throttle:10,1')->name('plans.purchase');
            Route::get('/plans/payment/{purchase}', [PlanController::class, 'paymentReturn'])->name('plans.payment.return');
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

        Route::get('/static-pages', [AdminStaticPageController::class, 'index'])
            ->name('static-pages.index');
        Route::get('/static-pages/{staticPage}/edit', [AdminStaticPageController::class, 'edit'])
            ->name('static-pages.edit');
        Route::put('/static-pages/{staticPage}', [AdminStaticPageController::class, 'update'])
            ->name('static-pages.update');
        Route::post('/static-pages/{staticPage}/translate', [AdminStaticPageController::class, 'translate'])
            ->name('static-pages.translate');

        Route::post(
            '/articles/{article}/generate-image',
            [ArticleAiController::class, 'generateImage']
        )
            ->whereNumber('article')
            ->name('articles.generate-image');

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
        Route::middleware('role:'.User::ROLE_ADMIN.','.User::ROLE_SUPER_ADMIN)
            ->group(function () {
                Route::get('/shop', [AdminNavigationController::class, 'shop'])
                    ->name('shop');

                Route::resource('marketplace/products', MarketplaceProductController::class)
                    ->except(['show'])
                    ->names('marketplace.products')
                    ->parameters(['products' => 'product']);
                Route::get('/marketplace/categories', [MarketplaceCategoryController::class, 'index'])->name('marketplace.categories.index');
                Route::post('/marketplace/categories', [MarketplaceCategoryController::class, 'store'])->name('marketplace.categories.store');
                Route::get('/marketplace/categories/{category}/edit', [MarketplaceCategoryController::class, 'edit'])->name('marketplace.categories.edit');
                Route::put('/marketplace/categories/{category}', [MarketplaceCategoryController::class, 'update'])->name('marketplace.categories.update');
                Route::delete('/marketplace/categories/{category}', [MarketplaceCategoryController::class, 'destroy'])->name('marketplace.categories.destroy');
                Route::resource('marketplace/providers', MarketplaceShippingProviderController::class)
                    ->except(['show'])
                    ->names('marketplace.providers')
                    ->parameters(['providers' => 'provider']);

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
                Route::get('/users', [AdminUserController::class, 'index'])->name('users');
                Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
                Route::get('/users/{user}/projects', [AdminUserController::class, 'projects'])->name('users.projects');
                Route::get('/users/{user}/projects/{project}/files', [AdminUserController::class, 'projectFiles'])->name('users.projects.files');
                Route::get('/users/{user}/projects/{project}/files/{file}', [AdminUserController::class, 'projectFile'])->name('users.projects.files.show');
                Route::get('/users/{user}/projects/{project}/artifacts/{artifact}', [AdminUserController::class, 'projectArtifact'])->name('users.projects.artifacts.show');
                Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
                Route::post('/users/{user}/token-lens', [AdminUserController::class, 'adjustTokens'])->name('users.tokens.adjust');
                Route::patch('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
                Route::patch('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
                Route::get('/settings/plans', [PlanSettingsController::class, 'edit'])->name('settings.plans');
                Route::put('/settings/plans', [PlanSettingsController::class, 'update'])->name('settings.plans.update');

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
        Route::middleware('role:'.User::ROLE_SUPER_ADMIN)
            ->group(function () {
                Route::get('/settings', [CommerceSettingsController::class, 'index'])
                    ->name('settings');

                Route::get('/settings/maintenance', [MaintenanceSettingsController::class, 'edit'])
                    ->name('settings.maintenance');
                Route::put('/settings/maintenance', [MaintenanceSettingsController::class, 'update'])
                    ->name('settings.maintenance.update');

                Route::put('/settings', [CommerceSettingsController::class, 'update'])
                    ->name('settings.update');

                Route::get('/settings/ai-translation', [AiTranslationSettingsController::class, 'edit'])
                    ->name('settings.ai-translation');

                Route::put('/settings/ai-translation', [AiTranslationSettingsController::class, 'update'])
                    ->name('settings.ai-translation.update');

                Route::get('/settings/fal-ai', [FalAiSettingsController::class, 'edit'])
                    ->name('settings.fal-ai');
                Route::put('/settings/fal-ai', [FalAiSettingsController::class, 'update'])
                    ->name('settings.fal-ai.update');
                Route::post('/settings/fal-ai/test', [FalAiSettingsController::class, 'test'])
                    ->middleware('throttle:5,1')
                    ->name('settings.fal-ai.test');

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
