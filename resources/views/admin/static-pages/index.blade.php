@extends('admin.layout')

@section(
    'title',
    __('static_pages.admin.title')
        . ' — '
        . __('admin.title')
)

@section(
    'page_heading',
    __('static_pages.admin.title')
)

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">
                {{ __('static_pages.admin.kicker') }}
            </span>

            <h1>
                {{ __('static_pages.admin.title') }}
            </h1>

            <p>
                {{ __('static_pages.admin.description') }}
            </p>
        </div>
    </div>

    @foreach ([
        'content' => $contentPages,
        'shop' => $shopPages,
    ] as $group => $pages)
        <section class="cms-panel">
            <div class="multilang-header">
                <div>
                    <span class="admin-eyebrow">
                        {{ __('static_pages.groups.' . $group) }}
                    </span>

                    <h2>
                        {{ __('static_pages.groups.' . $group) }}
                    </h2>
                </div>
            </div>

            <div class="cms-table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>
                                {{ __('static_pages.table.page') }}
                            </th>
                            <th>
                                {{ __('static_pages.table.languages') }}
                            </th>
                            <th>
                                {{ __('static_pages.table.updated') }}
                            </th>
                            <th class="cms-actions-cell">
                                {{ __('static_pages.table.actions') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($pages as $page)
                            @php
                                $source =
                                    $page->sourceTranslation();
                            @endphp

                            <tr>
                                <td>
                                    <div class="cms-title-cell">
                                        <span class="cms-image-placeholder">
                                            §
                                        </span>

                                        <div>
                                            <strong>
                                                {{ $source?->title ?? $page->key }}
                                            </strong>

                                            <span>
                                                {{ $page->key }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="translation-status-list">
                                        @foreach ($supportedLocales as $locale => $language)
                                            @php
                                                $translation =
                                                    $page->translation(
                                                        $locale
                                                    );

                                                $state =
                                                    ! $translation
                                                        ? 'missing'
                                                        : (
                                                            $translation
                                                                ->isComplete()
                                                            ? (
                                                                $locale
                                                                === $page->source_locale
                                                                    ? 'source'
                                                                    : 'ready'
                                                            )
                                                            : 'draft'
                                                        );
                                            @endphp

                                            <span
                                                class="translation-chip translation-chip-{{ $state }}"
                                                title="{{ $language['native'] }}"
                                            >
                                                {{ strtoupper($locale) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                <td>
                                    {{ $page->updated_at?->format('d.m.Y H:i') }}
                                </td>

                                <td class="cms-actions-cell">
                                    <a
                                        class="cms-action-button"
                                        href="{{ route(
                                            'admin.static-pages.edit',
                                            $page
                                        ) }}"
                                    >
                                        {{ __('static_pages.actions.edit') }}
                                    </a>

                                    <form
                                        method="post"
                                        action="{{ route(
                                            'admin.static-pages.translate',
                                            $page
                                        ) }}"
                                    >
                                        @csrf

                                        <button
                                            class="cms-action-button"
                                            type="submit"
                                        >
                                            {{ __('static_pages.actions.auto_translate') }}
                                        </button>
                                    </form>

                                    <a
                                        class="cms-action-button cms-action-preview"
                                        href="{{ route(
                                            'static-pages.show',
                                            [
                                                'locale' =>
                                                    $page->source_locale,
                                                'key' =>
                                                    $page->key,
                                            ]
                                        ) }}"
                                        target="_blank"
                                    >
                                        {{ __('static_pages.actions.preview') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</section>
@endsection
