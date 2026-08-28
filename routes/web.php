<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CommerceSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayNowNotificationController;
use App\Http\Controllers\SalesDocumentController;
use App\Http\Controllers\ShopController;
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

Route::redirect('/', '/' . $defaultLocale);

Route::prefix('{locale}')
    ->where(['locale' => $localePattern])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', function () {
            return view('home');
        })->name('home');

        Route::get('/articles/{slug}', [ArticleController::class, 'show'])
            ->name('articles.show');

        Route::get('/shop', [ShopController::class, 'index'])
            ->name('shop.index');

        Route::get('/shop/{slug}', [ShopController::class, 'show'])
            ->name('shop.show');

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
            Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

            Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
                ->name('password.request');
            Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
                ->name('password.email');

            Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
                ->name('password.reset');
            Route::post('/reset-password', [ResetPasswordController::class, 'store'])
                ->name('password.update');
        });

        Route::middleware('auth')->group(function () {
            Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

            Route::get('/account', [AccountController::class, 'show'])->name('account');
            Route::put('/account/profile', [AccountController::class, 'updateProfile'])
                ->name('account.profile.update');
            Route::put('/account/password', [AccountController::class, 'updatePassword'])
                ->name('account.password.update');

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

        Route::get('/content', fn () => redirect()->route('admin.articles.index'))
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

        Route::get('/translations', [PlaceholderController::class, 'show'])
            ->defaults('section', 'translations')
            ->name('translations');

        Route::get('/analytics', [PlaceholderController::class, 'show'])
            ->defaults('section', 'analytics')
            ->name('analytics');

        Route::middleware('role:' . User::ROLE_ADMIN . ',' . User::ROLE_SUPER_ADMIN)
            ->group(function () {
                Route::get('/shop', fn () => redirect()->route('admin.products.index'))
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

                Route::get('/orchestrator', [PlaceholderController::class, 'show'])
                    ->defaults('section', 'orchestrator')
                    ->name('orchestrator');
            });

        Route::middleware('role:' . User::ROLE_SUPER_ADMIN)
            ->group(function () {
                Route::get('/settings', [CommerceSettingsController::class, 'index'])
                    ->name('settings');

                Route::put('/settings', [CommerceSettingsController::class, 'update'])
                    ->name('settings.update');
            });
    });


Route::post(
    '/payments/paynow/notification',
    PayNowNotificationController::class
)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('payments.paynow.notification');
