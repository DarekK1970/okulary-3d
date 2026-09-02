@php
    $locale = app()->getLocale();
    $routeName = request()->route()?->getName();
    $routeParameters = request()->route()?->parameters() ?? [];

    $headerSeo = $pageSeo
        ?? app(\App\Services\SeoService::class)->current();

    $headerAlternates =
        $headerSeo['alternates'] ?? [];

    $headerPrivatePage =
        str_starts_with(
            (string) (
                $headerSeo['robots']
                ?? ''
            ),
            'noindex'
        );
    $localizedUrl = static function (string $code) use (
        $routeName,
        $routeParameters,
        $headerAlternates,
        $headerPrivatePage
    ): string {
        if (isset($headerAlternates[$code])) {
            return $headerAlternates[$code];
        }

        if ($headerPrivatePage) {
            return route(
                'home',
                ['locale' => $code]
            );
        }

        if (! $routeName) {
            return url('/' . $code);
        }
        try {
            if (in_array(
                $routeName,
                [
                    'articles.show',
                    'shop.show',
                    'archive.show',
                ],
                true
            )) {
                return route(
                    'home',
                    ['locale' => $code]
                );
            }
            return route(
                $routeName,
                array_merge(
                    $routeParameters,
                    ['locale' => $code]
                )
            );
        } catch (\Throwable) {
            return url('/' . $code);
        }
    };

    $accountUrl = auth()->check()
        ? route('account', ['locale' => $locale])
        : route('login', ['locale' => $locale]);

    $currencyService = app(
        \App\Services\CurrencyService::class
    );
    $headerCurrencies =
        $currencyService->selectableCurrencies();

    $selectedCurrencyCode =
        $currencyService->selectedCode();
@endphp
<header class="site-header" data-site-header>
    <div class="site-container header-inner">
        <a
            class="brand"
            href="{{ route('home', ['locale' => $locale]) }}"
            aria-label="{{ __('site.brand_home_aria') }}"
        >
            <img
                class="brand-logo"
                src="{{ asset('images/logo-okulary-3d.svg') }}"
                alt="{{ __('site.brand_logo_alt') }}"
                width="188"
                height="46"
            >
        </a>
        <nav
            class="primary-navigation"
            id="primary-navigation"
            aria-label="{{ __('site.main_navigation') }}"
            data-primary-navigation
        >
            <div class="mobile-nav-head">
                <span>{{ __('site.mobile_menu_title') }}</span>
                <button
                    class="mobile-nav-close"
                    type="button"
                    aria-label="{{ __('site.close_menu') }}"
                    data-menu-close
                >
                    ×
                </button>
            </div>
            <a
                class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}"
                href="{{ route('home', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.home') }}
            </a>

            <a
                class="nav-link {{ request()->routeIs('articles.*') ? 'is-active' : '' }}"
                href="{{ route('articles.index', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.articles') }}
            </a>
            <a
                class="nav-link {{ request()->routeIs('archive.*') ? 'is-active' : '' }}"
                href="{{ route('archive.index', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.history') }}
            </a>

            <a
                class="nav-link"
                href="{{ route('home', ['locale' => $locale]) }}#techniques"
            >
                {{ __('site.nav.techniques') }}
            </a>
            <a
                class="nav-link {{ request()->routeIs('lab.*') ? 'is-active' : '' }}"
                href="{{ route('lab.index', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.lab') }}
            </a>
            <a
                class="nav-link {{ request()->routeIs('gallery.*') || request()->routeIs('account.gallery.*') ? 'is-active' : '' }}"
                href="{{ route('gallery.index', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.gallery') }}
            </a>
            <a
                class="nav-link {{ request()->routeIs('shop.*') ? 'is-active' : '' }}"
                href="{{ route('shop.index', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.shop') }}
            </a>

            <a
                class="nav-link {{ request()->routeIs('static-pages.show') && request()->route('key') === 'about' ? 'is-active' : '' }}"
                href="{{ route('static-pages.show', [
                    'locale' => $locale,
                    'key' => 'about',
                ]) }}"
            >
                {{ __('site.nav.about') }}
            </a>
            <div class="mobile-nav-actions">
                <div
                    class="mobile-language-switcher"
                    aria-label="{{ __('site.language_switcher') }}"
                >
                    @foreach (config('locales.supported', []) as $code => $language)
                        <a
                            class="mobile-language-link {{ $locale === $code ? 'is-active' : '' }}"
                            href="{{ $localizedUrl($code) }}"
                            hreflang="{{ $code }}"
                            lang="{{ $code }}"
                            @if ($locale === $code)
                                aria-current="page"
                            @endif
                        >
                            {{ $language['native'] }}
                        </a>
                    @endforeach
                </div>
                @if ($headerCurrencies->isNotEmpty())
                    <form
                        class="mobile-currency-switcher"
                        method="post"
                        action="{{ route('currency.update', [
                            'locale' => $locale,
                        ]) }}"
                    >
                        @csrf
                        <label
                            for="mobile-currency-select"
                            class="sr-only"
                        >
                            {{ __('currency.switcher') }}
                        </label>
                        <select
                            id="mobile-currency-select"
                            name="currency"
                            onchange="this.form.submit()"
                            aria-label="{{ __('currency.switcher') }}"
                        >
                            @foreach ($headerCurrencies as $currency)
                                <option
                                    value="{{ $currency->code }}"
                                    @selected(
                                        $selectedCurrencyCode
                                        === $currency->code
                                    )
                                >
                                    {{ $currency->code }}
                                    · {{ $currency->symbol }}
                                </option>
                            @endforeach
                        </select>
                        <noscript>
                            <button type="submit">
                                {{ __('currency.apply') }}
                            </button>
                        </noscript>
                    </form>
                @endif
                <div class="mobile-utility-row">
                    <button
                        class="mobile-utility-button"
                        type="button"
                    >
                        <span aria-hidden="true">⌕</span>
                        {{ __('site.search') }}
                    </button>
                    <a
                        class="mobile-utility-button"
                        href="{{ $accountUrl }}"
                    >
                        <span aria-hidden="true">○</span>
                        {{ auth()->check()
                            ? __('portal_auth.common.my_account')
                            : __('site.account') }}
                    </a>
                    @auth
                        <a
                            class="mobile-utility-button"
                            href="{{ route('account.orders.index', [
                                'locale' => $locale,
                            ]) }}"
                        >
                            <span aria-hidden="true">▤</span>
                            {{ __('cart.header.orders') }}
                        </a>
                    @endauth
                    <a
                        class="mobile-utility-button"
                        href="{{ route('cart.index', [
                            'locale' => $locale,
                        ]) }}"
                    >
                        <span aria-hidden="true">🛒</span>
                        {{ __('site.cart') }}
                        ({{ app(\App\Services\CartService::class)->count() }})
                    </a>
                </div>
            </div>
        </nav>
        <div class="header-actions">
            <div
                class="language-switcher"
                aria-label="{{ __('site.language_switcher') }}"
            >
                @foreach (config('locales.supported', []) as $code => $language)
                    <a
                        class="language-link {{ $locale === $code ? 'is-active' : '' }}"
                        href="{{ $localizedUrl($code) }}"
                        hreflang="{{ $code }}"
                        lang="{{ $code }}"
                        @if ($locale === $code)
                            aria-current="page"
                        @endif
                    >
                        {{ strtoupper($code) }}
                    </a>
                    @if (! $loop->last)
                        <span
                            class="language-separator"
                            aria-hidden="true"
                        >/</span>
                    @endif
                @endforeach
            </div>
            @if ($headerCurrencies->isNotEmpty())
                <form
                    class="currency-switcher"
                    method="post"
                    action="{{ route('currency.update', [
                        'locale' => $locale,
                    ]) }}"
                >
                    @csrf
                    <label
                        for="desktop-currency-select"
                        class="sr-only"
                    >
                        {{ __('currency.switcher') }}
                    </label>
                    <select
                        id="desktop-currency-select"
                        name="currency"
                        onchange="this.form.submit()"
                        aria-label="{{ __('currency.switcher') }}"
                        title="{{ __('currency.switcher') }}"
                    >
                        @foreach ($headerCurrencies as $currency)
                            <option
                                value="{{ $currency->code }}"
                                @selected(
                                    $selectedCurrencyCode
                                    === $currency->code
                                )
                            >
                                {{ $currency->code }}
                            </option>
                        @endforeach
                    </select>
                    <noscript>
                        <button type="submit">
                            {{ __('currency.apply') }}
                        </button>
                    </noscript>
                </form>
            @endif
            <button
                class="icon-button"
                type="button"
                aria-label="{{ __('site.search') }}"
                title="{{ __('site.search') }}"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m21 21-4.35-4.35m2.35-5.15a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/>
                </svg>
            </button>
            <a
                class="icon-button"
                href="{{ $accountUrl }}"
                aria-label="{{ __('site.account') }}"
                title="{{ __('site.account') }}"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
                </svg>
            </a>
            @auth
                <a
                    class="icon-button"
                    href="{{ route('account.orders.index', [
                        'locale' => $locale,
                    ]) }}"
                    aria-label="{{ __('cart.header.orders') }}"
                    title="{{ __('cart.header.orders') }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2Zm2 5h8M8 12h8"/>
                    </svg>
                </a>
            @endauth
            <a
                class="icon-button cart-button"
                href="{{ route('cart.index', [
                    'locale' => $locale,
                ]) }}"
                aria-label="{{ __('site.cart') }}"
                title="{{ __('site.cart') }}"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L20 8H6m4 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm8 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/>
                </svg>
                <span class="cart-count">
                    {{ app(\App\Services\CartService::class)->count() }}
                </span>
            </a>
        </div>
        <button
            class="mobile-menu-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="primary-navigation"
            aria-label="{{ __('site.open_menu') }}"
            data-menu-toggle
        >
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <div
        class="mobile-nav-backdrop"
        data-menu-backdrop
    ></div>
</header>
