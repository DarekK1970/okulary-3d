@extends('layouts.app')

@section('title', ($translation->seo_title ?: $translation->title) . ' — ' . __('site.title'))
@section('meta_description', $translation->seo_description ?: ($translation->excerpt ?: __('site.meta_description')))

@push('head')
    @vite('resources/css/article.css')

    <link
        rel="canonical"
        href="{{ route('articles.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}"
    >

    @foreach ($article->translations as $alternate)
        @if ($alternate->isPubliclyReady())
            <link
                rel="alternate"
                hreflang="{{ $alternate->locale }}"
                href="{{ route('articles.show', ['locale' => $alternate->locale, 'slug' => $alternate->slug]) }}"
            >
        @endif
    @endforeach
@endpush

@section('content')
<article class="public-article">
    <div class="site-container article-container">
        <nav class="article-breadcrumbs" aria-label="{{ __('articles_public.breadcrumbs') }}">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">
                {{ __('articles_public.home') }}
            </a>
            <span>›</span>
            <span>{{ $article->category?->name }}</span>
        </nav>

        <header class="article-header">
            <div class="article-meta">
                <span>{{ $article->category?->name }}</span>
                <span>•</span>
                <time datetime="{{ $article->published_at?->toAtomString() }}">
                    {{ $article->published_at?->format('d.m.Y') }}
                </time>
            </div>

            <h1>{{ $translation->title }}</h1>

            @if ($translation->excerpt)
                <p class="article-lead">{{ $translation->excerpt }}</p>
            @endif

            @if ($article->translations->count() > 1)
                <div class="article-language-links">
                    <span>{{ __('articles_public.other_languages') }}</span>

                    @foreach ($article->translations as $alternate)
                        @if ($alternate->isPubliclyReady())
                            <a
                                class="{{ $alternate->locale === $translation->locale ? 'is-active' : '' }}"
                                href="{{ route('articles.show', ['locale' => $alternate->locale, 'slug' => $alternate->slug]) }}"
                            >
                                {{ strtoupper($alternate->locale) }}
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </header>

        @if ($article->hero_image_path)
            <figure class="article-hero">
                <img
                    src="{{ Storage::url($article->hero_image_path) }}"
                    alt="{{ $translation->title }}"
                >
            </figure>
        @endif

        <div class="article-content">
            {!! $translation->body_html !!}
        </div>

        @if (
            ! empty($contextualRecommendations['tools'])
            || ! empty($contextualRecommendations['products'])
        )
            <section class="contextual-recommendations">
                <div class="contextual-recommendations-heading">
                    <span>{{ __('recommendations.public.kicker') }}</span>
                    <h2>{{ __('recommendations.public.title') }}</h2>
                    <p>{{ __('recommendations.public.description') }}</p>
                </div>

                @if (! empty($contextualRecommendations['tools']))
                    <div class="contextual-tools-block">
                        <div class="contextual-section-heading">
                            <span>3D LAB</span>
                            <h3>{{ __('recommendations.public.tools_title') }}</h3>
                        </div>

                        <div class="contextual-tool-grid">
                            @foreach ($contextualRecommendations['tools'] as $tool)
                                <a
                                    class="contextual-tool-card"
                                    href="{{ route($tool['route'], ['locale' => app()->getLocale()]) }}"
                                >
                                    <div class="contextual-tool-icon">3D</div>

                                    <div>
                                        <strong>{{ $tool['title'] }}</strong>
                                        <p>{{ $tool['description'] }}</p>
                                        <span>
                                            {{ __('recommendations.public.open_tool') }} →
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($contextualRecommendations['products']))
                    <div class="contextual-products-block">
                        <div class="contextual-section-heading">
                            <span>{{ __('recommendations.public.shop_badge') }}</span>
                            <h3>{{ __('recommendations.public.products_title') }}</h3>
                        </div>

                        <div class="contextual-product-grid">
                            @foreach ($contextualRecommendations['products'] as $card)
                                <a
                                    class="contextual-product-card"
                                    href="{{ route('shop.show', [
                                        'locale' => app()->getLocale(),
                                        'slug' => $card['translation']->slug
                                    ]) }}"
                                >
                                    <div class="contextual-product-image">
                                        @if ($card['media'])
                                            <img
                                                src="{{ $card['media']->url() }}"
                                                alt="{{ $card['media']->alt_text ?: $card['translation']->name }}"
                                                loading="lazy"
                                            >
                                        @else
                                            <span>3D</span>
                                        @endif
                                    </div>

                                    <div class="contextual-product-copy">
                                        <strong>{{ $card['translation']->name }}</strong>

                                        @if ($card['translation']->short_description)
                                            <p>
                                                {{ \Illuminate\Support\Str::limit(
                                                    $card['translation']->short_description,
                                                    100
                                                ) }}
                                            </p>
                                        @endif

                                        <div class="contextual-product-bottom">
                                            @if ($card['price'])
                                                <span>
                                                    {{ __('recommendations.public.from') }}
                                                    {{ $card['price'] }}
                                                    {{ $card['currency'] }}
                                                </span>
                                            @endif

                                            <b>{{ __('recommendations.public.open_product') }} →</b>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <footer class="article-footer">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}#articles">
                ← {{ __('articles_public.back') }}
            </a>
        </footer>
    </div>
</article>
@endsection
