@extends('layouts.app')

@section('title', __('home.meta.title'))
@section('meta_description', __('home.meta.description'))

@section('content')
    <section class="home-hero">
        <div class="site-container hero-grid">
            <div class="hero-copy">
                <span class="hero-badge">
                    <span aria-hidden="true">✦</span>
                    {{ __('home.hero.badge') }}
                </span>

                <h1>{!! __('home.hero.title_html') !!}</h1>

                <p class="hero-lead">
                    {{ __('home.hero.lead') }}
                </p>

                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('lab.anaglyph', ['locale' => app()->getLocale()]) }}">
                        <span class="button-icon" aria-hidden="true">◉</span>
                        {{ __('home.hero.cta_anaglyph') }}
                    </a>

                    <a class="button button-secondary" href="{{ route('lab.lenticular', ['locale' => app()->getLocale()]) }}">
                        <span class="button-icon" aria-hidden="true">▥</span>
                        {{ __('home.hero.cta_lenticular') }}
                    </a>

                    <a class="button button-light" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                        <span class="button-icon" aria-hidden="true">🛒</span>
                        {{ __('home.hero.cta_shop') }}
                    </a>
                </div>

                <div class="hero-trust">
                    <div class="trust-item">
                        <strong>180+</strong>
                        <span>{{ __('home.hero.history_years') }}</span>
                    </div>
                    <div class="trust-divider" aria-hidden="true"></div>
                    <div class="trust-item">
                        <strong>3D LAB</strong>
                        <span>{{ __('home.hero.tools_online') }}</span>
                    </div>
                    <div class="trust-divider" aria-hidden="true"></div>
                    <div class="trust-item">
                        <strong>PL / EN</strong>
                        <span>{{ __('home.hero.languages') }}</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <img
                    src="{{ asset('images/home/hero-3d.svg') }}"
                    alt=""
                    width="760"
                    height="620"
                >
            </div>
        </div>
    </section>

    <section class="home-section" id="articles">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ __('home.articles.kicker') }}</span>
                    <h2>{{ __('home.articles.title') }}</h2>
                </div>

                <a class="section-link" href="#">
                    {{ __('home.articles.all') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="article-grid">
                @foreach (__('home.articles.items') as $index => $article)
                    <article class="article-card {{ $index === 0 ? 'featured-card' : '' }}">
                        <div class="article-image">
                            <img
                                src="{{ asset('images/home/' . $article['image']) }}"
                                alt=""
                                width="640"
                                height="390"
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            >
                        </div>

                        <div class="article-content">
                            <div class="article-meta-top">
                                <span class="article-tag">{{ $article['tag'] }}</span>
                                <span class="article-date">{{ $article['date'] }}</span>
                            </div>

                            <h3>{{ $article['title'] }}</h3>
                            <p>{{ $article['description'] }}</p>

                            <div class="article-footer">
                                <span>{{ $article['reading_time'] }}</span>
                                <a href="#" aria-label="{{ $article['title'] }}">→</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-section section-tinted" id="lab">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ __('home.lab.kicker') }}</span>
                    <h2>{{ __('home.lab.title') }}</h2>
                    <p class="section-intro">{{ __('home.lab.description') }}</p>
                </div>
            </div>

            @php
                $labToolRoutes = [
                    route('lab.anaglyph', ['locale' => app()->getLocale()]),
                    route('lab.lenticular', ['locale' => app()->getLocale()]),
                    route('lab.stereo-alignment', ['locale' => app()->getLocale()]),
                    route('lab.wigglegram', ['locale' => app()->getLocale()]),
                    null,
                    route('lab.mpo', ['locale' => app()->getLocale()]),
                ];
            @endphp

            <div class="lab-grid">
                @foreach (__('home.lab.tools') as $index => $tool)
                    <article class="lab-card">
                        <div class="lab-icon" aria-hidden="true">
                            {!! $tool['icon'] !!}
                        </div>

                        <div class="lab-copy">
                            <h3>{{ $tool['title'] }}</h3>
                            <p>{{ $tool['description'] }}</p>
                        </div>

                        @if ($labToolRoutes[$index] ?? null)
                            <a class="lab-action" href="{{ $labToolRoutes[$index] }}">
                                {{ __('home.lab.run') }}
                                <span aria-hidden="true">→</span>
                            </a>
                        @else
                            <span class="lab-action is-disabled" aria-disabled="true">
                                {{ __('home.lab.soon') }}
                            </span>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-section" id="shop">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ __('home.shop.kicker') }}</span>
                    <h2>{{ __('home.shop.title') }}</h2>
                </div>

                <a class="section-link" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                    {{ __('home.shop.all') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="shop-grid">
                @foreach (__('home.shop.categories') as $category)
                    <article class="shop-card">
                        <div class="shop-image">
                            <img
                                src="{{ asset('images/home/' . $category['image']) }}"
                                alt=""
                                width="520"
                                height="330"
                                loading="lazy"
                            >
                        </div>

                        <div class="shop-card-body">
                            <h3>{{ $category['title'] }}</h3>

                            @if (! empty($category['chips']))
                                <div class="shop-chips">
                                    @foreach ($category['chips'] as $chip)
                                        <span>{{ $chip }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="shop-card-footer">
                                <span class="shop-price">{{ $category['price'] }}</span>
                                <a class="shop-link" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                                    {{ __('home.shop.products') }}
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-section section-tinted today-section" id="techniques">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ __('home.today.kicker') }}</span>
                    <h2>{{ __('home.today.title') }}</h2>
                </div>
            </div>

            <div class="today-grid">
                @foreach (__('home.today.items') as $item)
                    <article class="today-card">
                        <div class="today-visual {{ $item['class'] }}" aria-hidden="true">
                            <span class="today-orbit orbit-a"></span>
                            <span class="today-orbit orbit-b"></span>
                            <span class="today-symbol">{{ $item['symbol'] }}</span>
                        </div>

                        <div class="today-content">
                            <span class="today-label">{{ $item['label'] }}</span>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                            <a href="#" aria-label="{{ $item['title'] }}">→</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-section" id="gallery">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ __('home.gallery.kicker') }}</span>
                    <h2>{{ __('home.gallery.title') }}</h2>
                </div>

                <div class="home-gallery-actions">
                    <a
                        class="gallery-tab is-active"
                        href="{{ route('gallery.index', ['locale' => app()->getLocale()]) }}"
                    >
                        {{ __('gallery.home.open') }}
                    </a>

                    @auth
                        <a
                            class="gallery-tab"
                            href="{{ route('gallery.create', ['locale' => app()->getLocale()]) }}"
                        >
                            {{ __('gallery.home.submit') }}
                        </a>
                    @else
                        <a
                            class="gallery-tab"
                            href="{{ route('login', ['locale' => app()->getLocale()]) }}"
                        >
                            {{ __('gallery.home.login_to_submit') }}
                        </a>
                    @endauth
                </div>
            </div>

            <div class="gallery-grid">
                @foreach (__('home.gallery.items') as $index => $item)
                    <a
                        class="gallery-card"
                        href="{{ route('gallery.index', ['locale' => app()->getLocale()]) }}"
                    >
                        <div class="gallery-image">
                            <img
                                src="{{ asset('images/home/gallery-' . ($index + 1) . '.svg') }}"
                                alt=""
                                width="480"
                                height="360"
                                loading="lazy"
                            >
                            <span class="gallery-mode">{{ $item['mode'] }}</span>
                        </div>

                        <div class="gallery-card-footer">
                            <span class="gallery-user">{{ $item['user'] }}</span>
                            <span class="gallery-likes">♡ {{ $item['likes'] }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-section archive-section" id="history">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ __('home.archive.kicker') }}</span>
                    <h2>{{ __('home.archive.title') }}</h2>
                    <p class="section-intro">{{ __('home.archive.description') }}</p>
                </div>

                <a
                    class="section-link"
                    href="{{ route('archive.index', ['locale' => app()->getLocale()]) }}"
                >
                    {{ __('home.archive.all') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="archive-grid">
                @foreach (__('home.archive.items') as $index => $item)
                    <a
                        class="archive-card"
                        href="{{ route('archive.index', ['locale' => app()->getLocale()]) }}"
                    >
                        <div class="archive-image">
                            <img
                                src="{{ asset('images/home/archive-' . ($index + 1) . '.svg') }}"
                                alt=""
                                width="520"
                                height="340"
                                loading="lazy"
                            >
                        </div>
                        <div class="archive-copy">
                            <span>{{ $item['type'] }}</span>
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['year'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection
