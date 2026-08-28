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

        <footer class="article-footer">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}#articles">
                ← {{ __('articles_public.back') }}
            </a>
        </footer>
    </div>
</article>
@endsection
