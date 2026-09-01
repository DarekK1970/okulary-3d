@extends('admin.layout')

@section(
    'title',
    __('static_pages.admin.edit_title')
        . ' — '
        . __('admin.title')
)

@section(
    'page_heading',
    __('static_pages.admin.edit_title')
)

@section('content')
<section class="cms-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">
                {{ __('static_pages.groups.' . $page->group) }}
            </span>

            <h1>
                {{ $page->sourceTranslation()?->title
                    ?? $page->key }}
            </h1>

            <p>
                {{ __('static_pages.admin.edit_description') }}
            </p>
        </div>

        <div class="cms-heading-actions">
            <a
                class="cms-secondary-button"
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

            <a
                class="cms-secondary-button"
                href="{{ route(
                    'admin.static-pages.index'
                ) }}"
            >
                ← {{ __('static_pages.actions.back') }}
            </a>
        </div>
    </div>

    <form
        method="post"
        action="{{ route(
            'admin.static-pages.update',
            $page
        ) }}"
    >
        @csrf
        @method('PUT')

        <div class="cms-editor-grid">
            <div class="cms-editor-main">
                <section class="cms-panel">
                    <div class="multilang-header">
                        <div>
                            <span class="admin-eyebrow">
                                {{ __('static_pages.editor.languages') }}
                            </span>

                            <h2>
                                {{ __('static_pages.editor.content') }}
                            </h2>
                        </div>

                        <div
                            class="translation-tabs"
                            role="tablist"
                        >
                            @foreach ($supportedLocales as $locale => $language)
                                @php
                                    $translation =
                                        $page->translation(
                                            $locale
                                        );
                                @endphp

                                <button
                                    type="button"
                                    class="translation-tab {{ $loop->first ? 'is-active' : '' }}"
                                    data-translation-tab="{{ $locale }}"
                                >
                                    {{ strtoupper($locale) }}

                                    <span
                                        class="translation-tab-dot translation-tab-dot-{{ $translation ? ($translation->isComplete() ? ($locale === $page->source_locale ? 'source' : 'ready') : 'draft') : 'missing' }}"
                                    ></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($supportedLocales as $locale => $language)
                        @php
                            $translation =
                                $page->translation(
                                    $locale
                                );

                            $prefix =
                                "translations.{$locale}";
                        @endphp

                        <div
                            class="translation-pane {{ $loop->first ? 'is-active' : '' }}"
                            data-translation-pane="{{ $locale }}"
                        >
                            <div class="translation-pane-heading">
                                <div>
                                    <strong>
                                        {{ $language['native'] }}
                                    </strong>
                                    <span>
                                        {{ strtoupper($locale) }}
                                    </span>
                                </div>

                                @if ($locale === $page->source_locale)
                                    <div class="translation-source-note">
                                        {{ __('static_pages.editor.source') }}
                                    </div>
                                @endif
                            </div>

                            <div class="cms-field">
                                <label for="page_title_{{ $locale }}">
                                    {{ __('static_pages.editor.title') }}
                                </label>

                                <input
                                    id="page_title_{{ $locale }}"
                                    name="translations[{{ $locale }}][title]"
                                    type="text"
                                    maxlength="220"
                                    value="{{ old(
                                        "{$prefix}.title",
                                        $translation?->title
                                    ) }}"
                                >
                            </div>

                            <div class="cms-field">
                                <label>
                                    {{ __('static_pages.editor.body') }}
                                </label>

                                <div
                                    class="wysiwyg"
                                    data-wysiwyg
                                >
                                    <div
                                        class="wysiwyg-toolbar"
                                        role="toolbar"
                                    >
                                        <button
                                            type="button"
                                            data-command="formatBlock"
                                            data-value="p"
                                        >
                                            P
                                        </button>

                                        <button
                                            type="button"
                                            data-command="formatBlock"
                                            data-value="h2"
                                        >
                                            H2
                                        </button>

                                        <button
                                            type="button"
                                            data-command="formatBlock"
                                            data-value="h3"
                                        >
                                            H3
                                        </button>

                                        <button
                                            type="button"
                                            data-command="bold"
                                        >
                                            <strong>B</strong>
                                        </button>

                                        <button
                                            type="button"
                                            data-command="italic"
                                        >
                                            <em>I</em>
                                        </button>

                                        <button
                                            type="button"
                                            data-command="insertUnorderedList"
                                        >
                                            •
                                        </button>

                                        <button
                                            type="button"
                                            data-command="insertOrderedList"
                                        >
                                            1.
                                        </button>

                                        <button
                                            type="button"
                                            data-command="formatBlock"
                                            data-value="blockquote"
                                        >
                                            ❝
                                        </button>

                                        <button
                                            type="button"
                                            data-link
                                        >
                                            🔗
                                        </button>
                                    </div>

                                    <div
                                        class="wysiwyg-editor"
                                        contenteditable="true"
                                        data-editor
                                    >{!! old(
                                        "{$prefix}.body_html",
                                        $translation?->body_html
                                    ) !!}</div>

                                    <textarea
                                        name="translations[{{ $locale }}][body_html]"
                                        data-editor-output
                                        hidden
                                    >{{ old(
                                        "{$prefix}.body_html",
                                        $translation?->body_html
                                    ) }}</textarea>
                                </div>
                            </div>

                            <div class="seo-panel">
                                <div class="seo-panel-heading">
                                    <span>SEO</span>

                                    <strong>
                                        {{ __('static_pages.editor.seo') }}
                                    </strong>
                                </div>

                                <div class="cms-field">
                                    <label for="page_seo_title_{{ $locale }}">
                                        {{ __('static_pages.editor.seo_title') }}
                                    </label>

                                    <input
                                        id="page_seo_title_{{ $locale }}"
                                        name="translations[{{ $locale }}][seo_title]"
                                        type="text"
                                        maxlength="180"
                                        value="{{ old(
                                            "{$prefix}.seo_title",
                                            $translation?->seo_title
                                        ) }}"
                                    >
                                </div>

                                <div class="cms-field">
                                    <label for="page_seo_description_{{ $locale }}">
                                        {{ __('static_pages.editor.seo_description') }}
                                    </label>

                                    <textarea
                                        id="page_seo_description_{{ $locale }}"
                                        name="translations[{{ $locale }}][seo_description]"
                                        rows="4"
                                        maxlength="320"
                                    >{{ old(
                                        "{$prefix}.seo_description",
                                        $translation?->seo_description
                                    ) }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </section>
            </div>

            <aside class="cms-editor-sidebar">
                <section class="cms-panel">
                    <h2>
                        {{ __('static_pages.editor.page_info') }}
                    </h2>

                    <div class="cms-field">
                        <label>
                            {{ __('static_pages.editor.key') }}
                        </label>

                        <input
                            type="text"
                            value="{{ $page->key }}"
                            readonly
                        >
                    </div>

                    <div class="cms-field">
                        <label>
                            {{ __('static_pages.editor.group') }}
                        </label>

                        <input
                            type="text"
                            value="{{ __('static_pages.groups.' . $page->group) }}"
                            readonly
                        >
                    </div>

                    <div class="cms-field">
                        <label>
                            {{ __('static_pages.editor.public_url') }}
                        </label>

                        <input
                            type="text"
                            value="{{ route(
                                'static-pages.show',
                                [
                                    'locale' =>
                                        $page->source_locale,
                                    'key' =>
                                        $page->key,
                                ]
                            ) }}"
                            readonly
                        >
                    </div>
                </section>

                <div class="cms-form-actions">
                    <button
                        class="cms-primary-button cms-submit-button"
                        type="submit"
                    >
                        {{ __('static_pages.actions.save') }}
                    </button>

                    <a
                        class="cms-secondary-button"
                        href="{{ route(
                            'admin.static-pages.index'
                        ) }}"
                    >
                        {{ __('static_pages.actions.cancel') }}
                    </a>
                </div>
            </aside>
        </div>
    </form>
</section>
@endsection
