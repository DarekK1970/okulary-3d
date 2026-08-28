<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Support\Facades\Route;

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
                Route::get('/shop', [PlaceholderController::class, 'show'])
                    ->defaults('section', 'shop')
                    ->name('shop');

                Route::get('/users', [PlaceholderController::class, 'show'])
                    ->defaults('section', 'users')
                    ->name('users');

                Route::get('/orchestrator', [PlaceholderController::class, 'show'])
                    ->defaults('section', 'orchestrator')
                    ->name('orchestrator');
            });

        Route::get('/settings', [PlaceholderController::class, 'show'])
            ->defaults('section', 'settings')
            ->middleware('role:' . User::ROLE_SUPER_ADMIN)
            ->name('settings');
    });
