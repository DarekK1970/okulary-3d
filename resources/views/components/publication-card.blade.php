@props([
    'article',
    'index' => 0,
])

@php
    $locale = app()->getLocale();
    $translation = $article->publicTranslation($locale);
    $imageUrl = $article->heroMedia?->url();

    if (! $imageUrl && filled($article->hero_image_path)) {
        $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')
            ->url($article->hero_image_path);
    }

    $articleUrl = $translation
        ? route('articles.show', [
            'locale' => $locale,
            'slug' => $translation->slug,
        ])
        : null;

    $intro = trim((string) (
        $translation?->excerpt
        ?: \Illuminate\Support\Str::limit(
            trim(preg_replace(
                '/\s+/u',
                ' ',
                strip_tags((string) $translation?->body_html)
            ) ?? ''),
            165
        )
    ));
@endphp

@if ($translation && $articleUrl)
    <article class="home-publication-card">
        <a
            class="home-publication-image"
            href="{{ $articleUrl }}"
            aria-label="{{ $translation->title }}"
        >
            <img
                src="{{ $imageUrl ?: asset('images/home/article-history.svg') }}"
                alt="{{ $translation->title }}"
                width="260"
                height="390"
                loading="{{ (int) $index === 0 ? 'eager' : 'lazy' }}"
            >
        </a>
        <div class="home-publication-copy">
            <div class="home-publication-meta">
                @if ($article->category)
                    <a
                        class="home-publication-category"
                        href="{{ $article->category->publicIndexUrl($locale) }}"
                    >
                        {{ $article->category->name }}
                    </a>
                @endif
                <time datetime="{{ $article->published_at?->toAtomString() }}">
                    {{ $article->published_at?->format('d.m.Y') }}
                </time>
            </div>
            <h3>
                <a href="{{ $articleUrl }}">{{ $translation->title }}</a>
            </h3>

            @if ($intro !== '')
                <p>{{ $intro }}</p>
            @endif

            <a class="home-publication-cta" href="{{ $articleUrl }}">
                {{ __('articles_public.read_more') }}
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </article>
@endif
