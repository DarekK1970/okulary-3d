@extends('admin.layout')

@section('title', __('cms.articles.title') . ' — ' . __('admin.title'))
@section('page_heading', __('cms.articles.title'))

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('cms.articles.kicker') }}</span>
            <h1>{{ __('cms.articles.title') }}</h1>
            <p>{{ __('cms.articles.description') }}</p>
        </div>

        <a class="cms-primary-button" href="{{ route('admin.articles.create') }}">
            + {{ __('cms.articles.new') }}
        </a>
    </div>

    <form class="cms-filter-bar" method="get" action="{{ route('admin.articles.index') }}">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('cms.articles.filters.search') }}"
        >

        <select name="status">
            <option value="">{{ __('cms.articles.filters.all_statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                    {{ __('cms.articles.statuses.' . $status->value) }}
                </option>
            @endforeach
        </select>

        <select name="category">
            <option value="">{{ __('cms.articles.filters.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <button type="submit">{{ __('cms.articles.filters.apply') }}</button>

        @if (request()->hasAny(['q', 'status', 'category']))
            <a href="{{ route('admin.articles.index') }}">{{ __('cms.articles.filters.clear') }}</a>
        @endif
    </form>

    <div class="cms-table-wrap">
        <table class="cms-table">
            <thead>
                <tr>
                    <th>{{ __('cms.articles.table.title') }}</th>
                    <th>{{ __('cms.articles.table.category') }}</th>
                    <th>{{ __('cms.articles.table.status') }}</th>
                    <th>{{ __('cms.articles.table.languages') }}</th>
                    <th>{{ __('cms.articles.table.publication') }}</th>
                    <th class="cms-actions-cell">{{ __('cms.articles.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    @php
                        $source = $article->sourceTranslation();
                    @endphp

                    <tr>
                        <td>
                            <div class="cms-title-cell">
                                @if ($article->heroMedia)
                                    <img
                                        src="{{ $article->heroMedia->url() }}"
                                        alt=""
                                    >
                                @elseif ($article->hero_image_path)
                                    <img
                                        src="{{ Storage::disk('public')->url($article->hero_image_path) }}"
                                        alt=""
                                    >
                                @else
                                    <span class="cms-image-placeholder">3D</span>
                                @endif

                                <div>
                                    <strong>{{ $source?->title ?? $article->title }}</strong>
                                    <span>{{ strtoupper($article->source_locale) }} · /{{ $source?->slug ?? $article->slug }}</span>
                                </div>
                            </div>
                        </td>

                        <td>{{ $article->category?->name }}</td>

                        <td>
                            <span class="cms-status cms-status-{{ $article->status->value }}">
                                {{ __('cms.articles.statuses.' . $article->status->value) }}
                            </span>
                        </td>

                        <td>
                            <div class="translation-status-list">
                                @foreach ($supportedLocales as $locale => $language)
                                    @php
                                        $translation = $article->translation($locale);
                                    @endphp

                                    <span
                                        class="translation-chip {{ $translation ? 'translation-chip-' . $translation->translation_status->value : 'translation-chip-missing' }}"
                                        title="{{ $language['native'] }}"
                                    >
                                        {{ strtoupper($locale) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>

                        <td>
                            {{ $article->published_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>

                        <td class="cms-actions-cell">
                            @php
                                $targetLocale = collect(
                                    array_keys(
                                        $supportedLocales
                                    )
                                )->first(
                                    fn ($locale) =>
                                        $locale
                                        !== $article
                                            ->source_locale
                                );

                                $targetTranslation =
                                    $targetLocale
                                        ? $article
                                            ->translation(
                                                $targetLocale
                                            )
                                        : null;

                                $translatorDisabled =
                                    ! $targetLocale
                                    || (
                                        $targetTranslation
                                        && $targetTranslation
                                            ->isPubliclyReady()
                                    );

                                $translatorTitle =
                                    ! $targetLocale
                                        ? __(
                                            'article_ai.tooltips.no_target_language'
                                        )
                                        : (
                                            $translatorDisabled
                                                ? __(
                                                    'article_ai.tooltips.translation_ready'
                                                )
                                                : __(
                                                    'article_ai.actions.translate'
                                                )
                                        );

                                $hasAssociatedImage =
                                    filled(
                                        $article
                                            ->hero_media_id
                                    )
                                    || filled(
                                        $article
                                            ->hero_image_path
                                    );

                                $previewTranslation =
                                    $article
                                        ->sourceTranslation();

                                $canPreview =
                                    $article
                                        ->status
                                        ->value
                                        === 'published'
                                    && $previewTranslation
                                    && $previewTranslation
                                        ->isPubliclyReady();
                            @endphp

                            <div class="article-action-icons">
                                <a
                                    class="article-action-icon"
                                    href="{{ route(
                                        'admin.articles.edit',
                                        $article
                                    ) }}"
                                    title="{{ __('article_ai.actions.edit') }}"
                                    aria-label="{{ __('article_ai.actions.edit') }}"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path d="M4 20h4l10.5-10.5a2.8 2.8 0 0 0-4-4L4 16v4Z"/>
                                        <path d="m13.5 6.5 4 4"/>
                                    </svg>
                                </a>

                                <form
                                    method="post"
                                    action="{{ route(
                                        'admin.translations.translate',
                                        [
                                            'type' =>
                                                \App\Services\AiTranslationService::TYPE_ARTICLE,
                                            'id' =>
                                                $article->id,
                                        ]
                                    ) }}"
                                >
                                    @csrf

                                    <button
                                        class="article-action-icon is-translate"
                                        type="submit"
                                        title="{{ $translatorTitle }}"
                                        aria-label="{{ __('article_ai.actions.translate') }}"
                                        @disabled($translatorDisabled)
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M3.5 12h17"/>
                                            <path d="M12 3c2.2 2.4 3.4 5.4 3.4 9S14.2 18.6 12 21"/>
                                            <path d="M12 3C9.8 5.4 8.6 8.4 8.6 12S9.8 18.6 12 21"/>
                                        </svg>
                                    </button>
                                </form>

                                @unless ($hasAssociatedImage)
                                    <form
                                        method="post"
                                        action="{{ route(
                                            'admin.articles.generate-image',
                                            $article
                                        ) }}"
                                        onsubmit="return confirm('{{ __('article_ai.actions.generate_image_confirm') }}')"
                                    >
                                        @csrf

                                        <button
                                            class="article-action-icon is-image-ai"
                                            type="submit"
                                            title="{{ __('article_ai.actions.generate_image') }}"
                                            aria-label="{{ __('article_ai.actions.generate_image') }}"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                aria-hidden="true"
                                            >
                                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                                <circle cx="8.5" cy="10" r="1.5"/>
                                                <path d="m5 17 4.2-4.2 3.1 3.1 2.2-2.2L19 17"/>
                                                <path d="M18.2 2.5 19 4.7l2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2Z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endunless

                                @if ($canPreview)
                                    <a
                                        class="article-action-icon is-preview"
                                        href="{{ route(
                                            'articles.show',
                                            [
                                                'locale' =>
                                                    $previewTranslation
                                                        ->locale,
                                                'slug' =>
                                                    $previewTranslation
                                                        ->slug,
                                            ]
                                        ) }}"
                                        target="_blank"
                                        rel="noopener"
                                        title="{{ __('article_ai.actions.preview') }}"
                                        aria-label="{{ __('article_ai.actions.preview') }}"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path d="M2.8 12s3.2-6 9.2-6 9.2 6 9.2 6-3.2 6-9.2 6-9.2-6-9.2-6Z"/>
                                            <circle cx="12" cy="12" r="2.7"/>
                                        </svg>
                                    </a>
                                @else
                                    <button
                                        class="article-action-icon is-preview"
                                        type="button"
                                        disabled
                                        title="{{ __('article_ai.tooltips.preview_unavailable') }}"
                                        aria-label="{{ __('article_ai.actions.preview') }}"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path d="M2.8 12s3.2-6 9.2-6 9.2 6 9.2 6-3.2 6-9.2 6-9.2-6-9.2-6Z"/>
                                            <circle cx="12" cy="12" r="2.7"/>
                                        </svg>
                                    </button>
                                @endif

                                <form
                                    method="post"
                                    action="{{ route(
                                        'admin.articles.destroy',
                                        $article
                                    ) }}"
                                    onsubmit="return confirm('{{ __('cms.articles.actions.delete_confirm') }}')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="article-action-icon is-danger"
                                        type="submit"
                                        title="{{ __('article_ai.actions.delete') }}"
                                        aria-label="{{ __('article_ai.actions.delete') }}"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path d="M4 7h16"/>
                                            <path d="M9 7V4h6v3"/>
                                            <path d="M6.5 7 7.5 20h9l1-13"/>
                                            <path d="M10 11v5"/>
                                            <path d="M14 11v5"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="cms-empty">
                            {{ __('cms.articles.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($articles->hasPages())
        <div class="cms-pagination">
            {{ $articles->links() }}
        </div>
    @endif
</section>
@endsection
