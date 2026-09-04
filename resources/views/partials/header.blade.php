@php
    $locale = app()->getLocale();
    $routeName = request()->route()?->getName();
    $routeParameters = request()->route()?->parameters() ?? [];
    $routeQuery = request()->query();

    $headerSeo = $pageSeo
        ?? app(\App\Services\SeoService::class)->current();

    $headerAlternates = $headerSeo['alternates'] ?? [];
    $headerPrivatePage = str_starts_with(
        (string) ($headerSeo['robots'] ?? ''),
        'noindex'
    );

    $localizedUrl = static function (string $code) use (
        $routeName,
        $routeParameters,
        $routeQuery,
        $headerAlternates,
        $headerPrivatePage
    ): string {
        if (isset($headerAlternates[$code])) {
            return $headerAlternates[$code];
        }

        if ($headerPrivatePage) {
            return route('home', ['locale' => $code]);
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
                return route('home', ['locale' => $code]);
            }

            $parameters = array_merge(
                $routeParameters,
                ['locale' => $code]
            );

            if ($routeName === 'articles.index') {
                $parameters = array_merge($parameters, $routeQuery);
            }

            return route($routeName, $parameters);
        } catch (\Throwable) {
            return url('/' . $code);
        }
    };

    $accountUrl = auth()->check()
        ? route('account', ['locale' => $locale])
        : route('login', ['locale' => $locale]);

    $currencyService = app(\App\Services\CurrencyService::class);
    $headerCurrencies = $currencyService->selectableCurrencies();
    $selectedCurrencyCode = $currencyService->selectedCode();

    $articleSection = request()->routeIs('articles.index')
        ? request()->query('section')
        : null;
    $isGeneralArticles = request()->routeIs('articles.*') && ! $articleSection;
    $isHistorySection = request()->routeIs('archive.*')
        || $articleSection === \App\Enums\ArticlePortalSection::HistoryCuriosities->value;
    $isTechniquesSection = $articleSection === \App\Enums\ArticlePortalSection::Techniques->value;
    $isLabSection = request()->routeIs('lab.*');
    $isPowerLabSection = request()->routeIs('lab.lenticular.studio', 'lab.lenticular.ai.*');
    $isMarketplace = request()->routeIs('marketplace.*');
    $headerUser = auth()->user();
    $headerPlan = $headerUser ? app(\App\Services\LenticularAccessService::class)->plan($headerUser) : null;
    $headerTokenBalance = $headerUser ? app(\App\Services\TokenLensWalletService::class)->balance($headerUser) : 0;
    $headerTokenExpiry = $headerUser ? app(\App\Services\TokenLensWalletService::class)->expiresAt($headerUser) : null;
@endphp

<style>
    .nav-dropdown {
        position: relative;
    }
    .nav-dropdown > summary {
        list-style: none;
        cursor: pointer;
    }
    .nav-dropdown > summary::-webkit-details-marker {
        display: none;
    }
    .nav-dropdown-caret {
        margin-left: 5px;
        font-size: .72em;
        transition: transform 160ms ease;
    }
    .nav-dropdown[open] .nav-dropdown-caret {
        transform: rotate(180deg);
    }
    .nav-dropdown-menu {
        position: absolute;
        z-index: 1100;
        top: calc(100% - 8px);
        left: 50%;
        min-width: 235px;
        display: none;
        padding: 8px;
        border: 1px solid #e8edf4;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 16px 38px rgba(16, 24, 44, .14);
        transform: translateX(-50%);
    }
    .nav-dropdown[open] .nav-dropdown-menu {
        display: grid;
    }
    .nav-dropdown-item {
        display: block;
        padding: 10px 12px;
        border-radius: 9px;
        color: #344054;
        font-size: .82rem;
        font-weight: 650;
        white-space: nowrap;
    }
    .nav-dropdown-item:hover,
    .nav-dropdown-item.is-active {
        background: #f4f7fb;
        color: var(--color-red);
    }
    .nav-lab-menu {
        min-width: 300px;
    }
    .nav-power-studio {
        margin-top: 3px;
        background: linear-gradient(105deg, #fff1f4, #edfaff);
        color: #e52d52;
        font-weight: 850;
        letter-spacing: .01em;
    }
    .nav-power-studio::before {
        content: '✦';
        margin-right: 7px;
        color: #00a4dc;
    }
    @media (max-width: 1180px) {
        .nav-dropdown {
            width: 100%;
        }
        .nav-dropdown > summary {
            width: 100%;
        }
        .nav-dropdown-menu {
            position: static;
            min-width: 0;
            margin: -6px 12px 8px;
            padding: 5px;
            border: 0;
            border-left: 2px solid #e8edf4;
            border-radius: 0;
            box-shadow: none;
            transform: none;
        }
        .nav-dropdown:not([open]) .nav-dropdown-menu {
            display: none;
        }
        .nav-dropdown[open] .nav-dropdown-menu {
            display: grid;
        }
        .nav-dropdown:hover:not([open]) .nav-dropdown-menu {
            display: none;
        }
        .nav-dropdown-item {
            white-space: normal;
        }
    }
</style>

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
                class="nav-link {{ $isGeneralArticles ? 'is-active' : '' }}"
                href="{{ route('articles.index', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.articles') }}
            </a>

            <details class="nav-dropdown">
                <summary class="nav-link {{ $isHistorySection ? 'is-active' : '' }}">
                    {{ __('site.nav.history') }}
                    <span class="nav-dropdown-caret" aria-hidden="true">▾</span>
                </summary>
                <div class="nav-dropdown-menu">
                    <a
                        class="nav-dropdown-item {{ $articleSection === \App\Enums\ArticlePortalSection::HistoryCuriosities->value ? 'is-active' : '' }}"
                        href="{{ route('articles.index', [
                            'locale' => $locale,
                            'section' => \App\Enums\ArticlePortalSection::HistoryCuriosities->value,
                        ]) }}"
                    >
                        {{ __('article_sections.history_menu.curiosities') }}
                    </a>
                    <a
                        class="nav-dropdown-item {{ request()->routeIs('archive.*') ? 'is-active' : '' }}"
                        href="{{ route('archive.index', ['locale' => $locale]) }}"
                    >
                        {{ __('article_sections.history_menu.archive') }}
                    </a>
                </div>
            </details>

            <a
                class="nav-link {{ $isTechniquesSection ? 'is-active' : '' }}"
                href="{{ route('articles.index', [
                    'locale' => $locale,
                    'section' => \App\Enums\ArticlePortalSection::Techniques->value,
                ]) }}"
            >
                {{ __('site.nav.techniques') }}
            </a>

            <details class="nav-dropdown">
                <summary class="nav-link {{ $isLabSection || $isMarketplace ? 'is-active' : '' }}">
                    {{ __('site.nav.lab') }}
                    <span class="nav-dropdown-caret" aria-hidden="true">▾</span>
                </summary>
                <div class="nav-dropdown-menu nav-lab-menu">
                    <a
                        class="nav-dropdown-item {{ $isLabSection && ! $isPowerLabSection ? 'is-active' : '' }}"
                        href="{{ route('lab.index', ['locale' => $locale]) }}"
                    >
                        {{ __('site.nav.lab_standard') }}
                    </a>
                    <a
                        class="nav-dropdown-item nav-power-studio {{ $isPowerLabSection ? 'is-active' : '' }}"
                        href="{{ route('lab.lenticular.studio', ['locale' => $locale]) }}"
                    >
                        {{ __('site.nav.lab_power') }}
                    </a>
                    <a
                        class="nav-dropdown-item {{ $isMarketplace ? 'is-active' : '' }}"
                        href="{{ route('marketplace.index', ['locale' => $locale]) }}"
                    >
                        {{ __('site.nav.lab_marketplace') }}
                    </a>
                </div>
            </details>

            <a
                class="nav-link {{ request()->routeIs('gallery.*') || request()->routeIs('account.gallery.*') ? 'is-active' : '' }}"
                href="{{ route('gallery.index', ['locale' => $locale]) }}"
            >
                {{ __('site.nav.gallery') }}
            </a>

            <a
                class="nav-link {{ request()->routeIs('partners.*') ? 'is-active' : '' }}"
                href="{{ route('partners.create', ['locale' => $locale]) }}"
            >
                {{ __('partners.nav') }}
            </a>

            <details class="nav-dropdown">
                <summary class="nav-link {{ request()->routeIs('shop.*') || $isMarketplace ? 'is-active' : '' }}">{{ __('site.nav.shop') }}<span class="nav-dropdown-caret" aria-hidden="true">▾</span></summary>
                <div class="nav-dropdown-menu">
                    <a class="nav-dropdown-item {{ request()->routeIs('shop.*') ? 'is-active' : '' }}" href="{{ route('shop.index', ['locale' => $locale]) }}">{{ __('site.nav.shop_accessories') }}</a>
                    <a class="nav-dropdown-item {{ $isMarketplace ? 'is-active' : '' }}" href="{{ route('marketplace.index', ['locale' => $locale]) }}">{{ __('site.nav.shop_marketplace') }}</a>
                </div>
            </details>

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
                        action="{{ route('currency.update', ['locale' => $locale]) }}"
                    >
                        @csrf
                        <label for="mobile-currency-select" class="sr-only">
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
                                    @selected($selectedCurrencyCode === $currency->code)
                                >
                                    {{ $currency->code }} · {{ $currency->symbol }}
                                </option>
                            @endforeach
                        </select>
                        <noscript>
                            <button type="submit">{{ __('currency.apply') }}</button>
                        </noscript>
                    </form>
                @endif

                <div class="mobile-utility-row">
                    <button class="mobile-utility-button" type="button">
                        <span aria-hidden="true">⌕</span>
                        {{ __('site.search') }}
                    </button>
                    <a class="mobile-utility-button" href="{{ $accountUrl }}">
                        <span aria-hidden="true">○</span>
                        {{ auth()->check()
                            ? __('portal_auth.common.my_account')
                            : __('site.account') }}
                    </a>
                    @auth
                        <a
                            class="mobile-utility-button"
                            href="{{ route('account.orders.index', ['locale' => $locale]) }}"
                        >
                            <span aria-hidden="true">▤</span>
                            {{ __('cart.header.orders') }}
                        </a>
                    @endauth
                    <a
                        class="mobile-utility-button"
                        href="{{ route('cart.index', ['locale' => $locale]) }}"
                    >
                        <span aria-hidden="true">🛒</span>
                        {{ __('site.cart') }}
                        ({{ app(\App\Services\CartService::class)->count() }})
                    </a>
                </div>
                @auth
                    <a class="mobile-account-summary" href="{{ $accountUrl }}"><strong>{{ $headerUser->name }} · {{ strtoupper($headerPlan) }}</strong><span>{{ __('portal_auth.wallet.header_balance', ['count' => $headerTokenBalance]) }} · {{ $headerTokenExpiry ? __('portal_auth.wallet.valid_until', ['date' => $headerTokenExpiry->format('d.m.Y')]) : __('portal_auth.wallet.no_expiry') }}</span></a>
                @endauth
            </div>
        </nav>

        <div class="header-actions">
            @auth
                <a class="header-account-summary" href="{{ $accountUrl }}"><strong>{{ $headerUser->name }} <span>{{ strtoupper($headerPlan) }}</span></strong><small>{{ __('portal_auth.wallet.header_balance', ['count' => $headerTokenBalance]) }}</small><small>{{ $headerTokenExpiry ? __('portal_auth.wallet.valid_until', ['date' => $headerTokenExpiry->format('d.m.Y')]) : __('portal_auth.wallet.no_expiry') }}</small></a>
            @endauth
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
                        <span class="language-separator" aria-hidden="true">/</span>
                    @endif
                @endforeach
            </div>

            @if ($headerCurrencies->isNotEmpty())
                <form
                    class="currency-switcher"
                    method="post"
                    action="{{ route('currency.update', ['locale' => $locale]) }}"
                >
                    @csrf
                    <label for="desktop-currency-select" class="sr-only">
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
                                @selected($selectedCurrencyCode === $currency->code)
                            >
                                {{ $currency->code }}
                            </option>
                        @endforeach
                    </select>
                    <noscript>
                        <button type="submit">{{ __('currency.apply') }}</button>
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
                    href="{{ route('account.orders.index', ['locale' => $locale]) }}"
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
                href="{{ route('cart.index', ['locale' => $locale]) }}"
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

    <div class="mobile-nav-backdrop" data-menu-backdrop></div>
</header>
