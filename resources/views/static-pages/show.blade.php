@extends('layouts.app')

@section(
    'title',
    ($translation->seo_title
        ?: $translation->title)
        . ' — '
        . __('site.title')
)

@section(
    'meta_description',
    $translation->seo_description
        ?: __('site.meta_description')
)

@push('head')
    @vite('resources/css/article.css')
@endpush

@section('content')
<article class="public-article">
    <div class="site-container article-container">
        <nav
            class="article-breadcrumbs"
            aria-label="{{ __('static_pages.public.breadcrumbs') }}"
        >
            <a
                href="{{ route(
                    'home',
                    [
                        'locale' =>
                            app()->getLocale(),
                    ]
                ) }}"
            >
                {{ __('static_pages.public.home') }}
            </a>

            <span>›</span>

            <span>
                {{ __('static_pages.groups.' . $page->group) }}
            </span>
        </nav>

        <header class="article-header">
            <div class="article-meta">
                <span>
                    {{ __('static_pages.groups.' . $page->group) }}
                </span>
            </div>

            <h1>
                {{ $translation->title }}
            </h1>

            <div class="article-language-links">
                <span>
                    {{ __('static_pages.public.languages') }}
                </span>

                @foreach (config('locales.supported', []) as $locale => $language)
                    <a
                        class="{{ $locale === app()->getLocale() ? 'is-active' : '' }}"
                        href="{{ route(
                            'static-pages.show',
                            [
                                'locale' =>
                                    $locale,
                                'key' =>
                                    $page->key,
                            ]
                        ) }}"
                    >
                        {{ strtoupper($locale) }}
                    </a>
                @endforeach
            </div>
        </header>

        <div class="article-content">
            @if (filled($translation->body_html))
                {!! $translation->body_html !!}
            @else
                <p>
                    {{ __('static_pages.public.content_pending') }}
                </p>
            @endif
        </div>

        <footer class="article-footer">
            <a
                href="{{ route(
                    'home',
                    [
                        'locale' =>
                            app()->getLocale(),
                    ]
                ) }}"
            >
                ← {{ __('static_pages.public.back') }}
            </a>
        </footer>
    </div>
</article>
@endsection
