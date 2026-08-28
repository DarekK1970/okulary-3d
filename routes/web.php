<?php

use App\Http\Middleware\SetLocale;
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
    });
