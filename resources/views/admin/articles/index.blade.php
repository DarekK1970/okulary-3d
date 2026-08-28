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
                                @if ($article->hero_image_path)
                                    <img src="{{ Storage::url($article->hero_image_path) }}" alt="">
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
                            @foreach ($article->translations as $translation)
                                @if ($article->status->value === 'published' && $translation->isPubliclyReady())
                                    <a
                                        class="cms-action-button cms-action-preview"
                                        href="{{ route('articles.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}"
                                        target="_blank"
                                    >
                                        {{ strtoupper($translation->locale) }}
                                    </a>
                                @endif
                            @endforeach

                            <a class="cms-action-button" href="{{ route('admin.articles.edit', $article) }}">
                                {{ __('cms.articles.actions.edit') }}
                            </a>

                            <form
                                method="post"
                                action="{{ route('admin.articles.destroy', $article) }}"
                                onsubmit="return confirm('{{ __('cms.articles.actions.delete_confirm') }}')"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="cms-action-button cms-action-danger" type="submit">
                                    {{ __('cms.articles.actions.delete') }}
                                </button>
                            </form>
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
