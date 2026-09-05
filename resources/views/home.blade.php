@extends('layouts.app')

@section('title', __('home.meta.title'))
@section('meta_description', __('home.meta.description'))

@push('head')
    @vite('resources/js/community-gallery.js')
@endpush

@section('content')
    <section class="home-hero">
        <div class="site-container hero-grid">
            <div class="hero-copy">
                <span class="hero-badge">
                    <span aria-hidden="true">✦</span>
                    {{ __('home.hero.badge') }}
                </span>

                <h1>{!! __('home.hero.title_html') !!}</h1>
                <p class="hero-lead">{{ __('home.hero.lead') }}</p>

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
                    src="{{ asset('images/home/hero-3d.png') }}"
                    alt=""
                    width="760"
                    height="620"
                >
            </div>
        </div>
    </section>

    <section class="home-section home-latest-publications" id="articles">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ __('home.articles.kicker') }}</span>
                    <h2>{{ __('home.articles.title') }}</h2>
                </div>
                <a
                    class="section-link"
                    href="{{ route('articles.index', ['locale' => app()->getLocale()]) }}"
                >
                    {{ __('home.articles.all') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="home-publications-grid">
                @forelse ($latestArticles as $index => $article)
                    <x-publication-card :article="$article" :index="$index" />
                @empty
                    <div class="article-home-empty">
                        <strong>{{ __('articles_public.empty_title') }}</strong>
                        <p>{{ __('articles_public.empty_description') }}</p>
                    </div>
                @endforelse
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
                        <div class="lab-icon" aria-hidden="true">{!! $tool['icon'] !!}</div>

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

    @if ($techniqueArticles->isNotEmpty())
        <section class="home-section section-tinted home-latest-publications" id="techniques">
            <div class="site-container">
                <div class="section-heading-row">
                    <div>
                        <span class="section-kicker">
                            {{ \App\Enums\ArticlePortalSection::Techniques->kicker() }}
                        </span>
                        <h2>{{ \App\Enums\ArticlePortalSection::Techniques->title() }}</h2>
                        <p class="section-intro">
                            {{ \App\Enums\ArticlePortalSection::Techniques->description() }}
                        </p>
                    </div>
                    <a
                        class="section-link"
                        href="{{ route('articles.index', [
                            'locale' => app()->getLocale(),
                            'section' => \App\Enums\ArticlePortalSection::Techniques->value,
                        ]) }}"
                    >
                        {{ __('article_sections.all') }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="home-publications-grid">
                    @foreach ($techniqueArticles as $index => $article)
                        <x-publication-card :article="$article" :index="$index" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

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
                @forelse ($homeGalleryItems as $item)
                    <article
                        class="gallery-card"
                        data-community-viewer
                        data-left-url="{{ $item->leftImageUrl() }}"
                        data-right-url="{{ $item->rightImageUrl() }}"
                        data-loading="{{ __('gallery.viewer.loading') }}"
                        data-ready="{{ __('gallery.viewer.ready') }}"
                        data-error="{{ __('gallery.viewer.error') }}"
                    >
                        <div class="gallery-image">
                            <canvas data-gallery-canvas></canvas>
                            <span class="gallery-mode" data-gallery-status>
                                {{ __('gallery.viewer.loading') }}
                            </span>
                        </div>

                        <div class="gallery-view-switch">
                            <label>
                                <span class="sr-only">{{ __('gallery.viewer.mode') }}</span>
                                <select data-gallery-mode>
                                    <option value="anaglyph">{{ __('gallery.viewer.anaglyph') }}</option>
                                    <option value="wiggle">{{ __('gallery.viewer.wiggle') }}</option>
                                </select>
                            </label>
                        </div>

                        <div class="gallery-card-footer">
                            <a
                                class="gallery-user"
                                href="{{ route('gallery.show', [
                                    'locale' => app()->getLocale(),
                                    'galleryItem' => $item,
                                ]) }}"
                            >
                                {{ $item->title }}
                            </a>
                        </div>
                    </article>
                @empty
                    <a
                        class="gallery-card"
                        href="{{ route('gallery.index', ['locale' => app()->getLocale()]) }}"
                    >
                        <div class="gallery-image">
                            <img
                                src="{{ asset('images/home/gallery-1.svg') }}"
                                alt=""
                                width="480"
                                height="360"
                                loading="lazy"
                            >
                            <span class="gallery-mode">{{ __('gallery.home.open') }}</span>
                        </div>
                        <div class="gallery-card-footer">
                            <span class="gallery-user">{{ __('gallery.index.empty_title') }}</span>
                        </div>
                    </a>
                @endforelse
            </div>
        </div>
    </section>

    @if ($archiveItems->isNotEmpty())
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
                    @foreach ($archiveItems as $item)
                        @php
                            $archiveTranslation = $item->publicTranslation(app()->getLocale());
                            $archiveUrl = $archiveTranslation
                                ? route('archive.show', [
                                    'locale' => app()->getLocale(),
                                    'slug' => $archiveTranslation->slug,
                                ])
                                : route('archive.index', ['locale' => app()->getLocale()]);
                        @endphp
                        @if ($archiveTranslation)
                            <a class="archive-card" href="{{ $archiveUrl }}">
                                <div class="archive-image">
                                    <img
                                        src="{{ $item->originalImageUrl() }}"
                                        alt="{{ $archiveTranslation->title }}"
                                        width="520"
                                        height="340"
                                        loading="lazy"
                                    >
                                </div>
                                <div class="archive-copy">
                                    <span>{{ \Illuminate\Support\Str::headline($item->technique) }}</span>
                                    <h3>{{ $archiveTranslation->title }}</h3>
                                    <p>{{ $item->yearLabel() }}</p>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
