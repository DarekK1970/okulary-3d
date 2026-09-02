@extends('layouts.app')

@section(
    'title',
    __('articles_public.index.meta_title')
        . ' — '
        . __('site.title')
)

@section(
    'meta_description',
    __('articles_public.index.meta_description')
)

@push('head')
    @vite('resources/css/article.css')
@endpush

@section('content')
<section class="articles-index-page">
    <div class="articles-index-hero">
        <div class="site-container">
            <span class="articles-index-kicker">
                {{ __('articles_public.index.kicker') }}
            </span>

            <h1>
                {{ $selectedCategory?->name
                    ?? __('articles_public.index.title') }}
            </h1>

            <p>
                {{ $selectedCategory?->description
                    ?: __('articles_public.index.description') }}
            </p>
        </div>
    </div>

    <div class="site-container articles-index-layout">
        <aside class="articles-index-sidebar">
            <h2>
                {{ __('articles_public.index.categories') }}
            </h2>

            <a
                class="{{ ! $selectedCategory ? 'is-active' : '' }}"
                href="{{ route(
                    'articles.index',
                    [
                        'locale' =>
                            app()->getLocale(),
                    ]
                ) }}"
            >
                {{ __('articles_public.index.all_categories') }}
            </a>

            @foreach ($categories as $category)
                <a
                    class="{{ $selectedCategory?->id === $category->id ? 'is-active' : '' }}"
                    href="{{ route(
                        'articles.index',
                        [
                            'locale' =>
                                app()->getLocale(),
                            'category' =>
                                $category->slug,
                        ]
                    ) }}"
                >
                    {{ $category->name }}
                </a>
            @endforeach
        </aside>

        <main class="articles-index-results">
            <div class="articles-index-tools">
                <form
                    method="get"
                    action="{{ route(
                        'articles.index',
                        [
                            'locale' =>
                                app()->getLocale(),
                        ]
                    ) }}"
                >
                    @if ($selectedCategory)
                        <input
                            type="hidden"
                            name="category"
                            value="{{ $selectedCategory->slug }}"
                        >
                    @endif

                    <label class="sr-only" for="articles-search">
                        {{ __('articles_public.index.search') }}
                    </label>

                    <input
                        id="articles-search"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="{{ __('articles_public.index.search_placeholder') }}"
                    >

                    <button type="submit">
                        {{ __('articles_public.index.search_button') }}
                    </button>

                    @if (request()->filled('q'))
                        <a
                            href="{{ route(
                                'articles.index',
                                array_filter([
                                    'locale' =>
                                        app()->getLocale(),
                                    'category' =>
                                        $selectedCategory
                                            ?->slug,
                                ])
                            ) }}"
                        >
                            {{ __('articles_public.index.clear_search') }}
                        </a>
                    @endif
                </form>

                <strong>
                    {{ trans_choice(
                        'articles_public.index.count',
                        $articles->total(),
                        [
                            'count' =>
                                $articles->total(),
                        ]
                    ) }}
                </strong>
            </div>

            <div class="articles-public-grid">
                @forelse ($articles as $article)
                    @php
                        $translation =
                            $article->publicTranslation(
                                app()->getLocale()
                            );

                        $imageUrl =
                            $article
                                ->heroMedia
                                ?->url();

                        if (
                            ! $imageUrl
                            && filled(
                                $article
                                    ->hero_image_path
                            )
                        ) {
                            $imageUrl =
                                \Illuminate\Support\Facades\Storage
                                    ::disk('public')
                                    ->url(
                                        $article
                                            ->hero_image_path
                                    );
                        }

                        $plainBody = trim(
                            preg_replace(
                                '/\s+/u',
                                ' ',
                                strip_tags(
                                    (string)
                                    $translation
                                        ?->body_html
                                )
                            ) ?? ''
                        );

                        $wordCount =
                            $plainBody === ''
                                ? 0
                                : count(
                                    preg_split(
                                        '/\s+/u',
                                        $plainBody,
                                        -1,
                                        PREG_SPLIT_NO_EMPTY
                                    )
                                );

                        $readingMinutes =
                            max(
                                1,
                                (int) ceil(
                                    $wordCount / 220
                                )
                            );
                    @endphp

                    @if ($translation)
                        <article class="articles-public-card">
                            <a
                                class="articles-public-image"
                                href="{{ route(
                                    'articles.show',
                                    [
                                        'locale' =>
                                            app()->getLocale(),
                                        'slug' =>
                                            $translation
                                                ->slug,
                                    ]
                                ) }}"
                            >
                                <img
                                    src="{{ $imageUrl ?: asset(
                                        'images/home/article-history.svg'
                                    ) }}"
                                    alt="{{ $translation->title }}"
                                    loading="lazy"
                                >
                            </a>

                            <div class="articles-public-card-body">
                                <div class="articles-public-meta">
                                    @if ($article->category)
                                        <a
                                            href="{{ route(
                                                'articles.index',
                                                [
                                                    'locale' =>
                                                        app()->getLocale(),
                                                    'category' =>
                                                        $article
                                                            ->category
                                                            ->slug,
                                                ]
                                            ) }}"
                                        >
                                            {{ $article->category->name }}
                                        </a>
                                    @endif

                                    <time
                                        datetime="{{ $article
                                            ->published_at
                                            ?->toAtomString() }}"
                                    >
                                        {{ $article
                                            ->published_at
                                            ?->format('d.m.Y') }}
                                    </time>
                                </div>

                                <h2>
                                    <a
                                        href="{{ route(
                                            'articles.show',
                                            [
                                                'locale' =>
                                                    app()->getLocale(),
                                                'slug' =>
                                                    $translation
                                                        ->slug,
                                            ]
                                        ) }}"
                                    >
                                        {{ $translation->title }}
                                    </a>
                                </h2>

                                @if ($translation->excerpt)
                                    <p>
                                        {{ \Illuminate\Support\Str::limit(
                                            $translation->excerpt,
                                            190
                                        ) }}
                                    </p>
                                @endif

                                <div class="articles-public-card-footer">
                                    <span>
                                        {{ __(
                                            'articles_public.reading_minutes',
                                            [
                                                'minutes' =>
                                                    $readingMinutes,
                                            ]
                                        ) }}
                                    </span>

                                    <a
                                        href="{{ route(
                                            'articles.show',
                                            [
                                                'locale' =>
                                                    app()->getLocale(),
                                                'slug' =>
                                                    $translation
                                                        ->slug,
                                            ]
                                        ) }}"
                                    >
                                        {{ __('articles_public.index.read') }}
                                        →
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endif
                @empty
                    <div class="articles-index-empty">
                        <strong>
                            {{ __('articles_public.empty_title') }}
                        </strong>

                        <p>
                            {{ __('articles_public.empty_description') }}
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($articles->hasPages())
                <div class="articles-index-pagination">
                    {{ $articles->links() }}
                </div>
            @endif
        </main>
    </div>
</section>
@endsection
