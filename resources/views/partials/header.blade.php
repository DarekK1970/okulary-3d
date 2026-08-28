@php
    $locale = app()->getLocale();
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

        <nav
            class="primary-navigation"
            id="primary-navigation"
            aria-label="{{ __('site.main_navigation') }}"
            data-primary-navigation
        >
            <a class="nav-link is-active" href="{{ route('home', ['locale' => $locale]) }}">
                {{ __('site.nav.home') }}
            </a>
            <a class="nav-link" href="{{ route('home', ['locale' => $locale]) }}#articles">
                {{ __('site.nav.articles') }}
            </a>
            <a class="nav-link" href="{{ route('home', ['locale' => $locale]) }}#history">
                {{ __('site.nav.history') }}
            </a>
            <a class="nav-link" href="{{ route('home', ['locale' => $locale]) }}#techniques">
                {{ __('site.nav.techniques') }}
            </a>
            <a class="nav-link" href="{{ route('home', ['locale' => $locale]) }}#lab">
                {{ __('site.nav.lab') }}
            </a>
            <a class="nav-link" href="{{ route('home', ['locale' => $locale]) }}#gallery">
                {{ __('site.nav.gallery') }}
            </a>
            <a class="nav-link" href="{{ route('home', ['locale' => $locale]) }}#shop">
                {{ __('site.nav.shop') }}
            </a>
            <a class="nav-link" href="{{ route('home', ['locale' => $locale]) }}#about">
                {{ __('site.nav.about') }}
            </a>
        </nav>

        <div class="header-actions">
            <div class="language-switcher" aria-label="{{ __('site.language_switcher') }}">
                @foreach (config('locales.supported', []) as $code => $language)
                    <a
                        class="language-link {{ $locale === $code ? 'is-active' : '' }}"
                        href="{{ route('home', ['locale' => $code]) }}"
                        hreflang="{{ $code }}"
                        lang="{{ $code }}"
                        @if ($locale === $code) aria-current="page" @endif
                    >
                        {{ strtoupper($code) }}
                    </a>

                    @if (! $loop->last)
                        <span class="language-separator" aria-hidden="true">/</span>
                    @endif
                @endforeach
            </div>

            <button class="icon-button" type="button" aria-label="{{ __('site.search') }}" title="{{ __('site.search') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m21 21-4.35-4.35m2.35-5.15a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/>
                </svg>
            </button>

            <button class="icon-button" type="button" aria-label="{{ __('site.account') }}" title="{{ __('site.account') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0m12-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
                </svg>
            </button>

            <button class="icon-button cart-button" type="button" aria-label="{{ __('site.cart') }}" title="{{ __('site.cart') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L20 8H6m4 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm8 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/>
                </svg>
                <span class="cart-count" aria-hidden="true">0</span>
            </button>
        </div>
    </div>
</header>
