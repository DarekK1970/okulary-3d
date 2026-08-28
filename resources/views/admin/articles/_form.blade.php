@php
    $currentStatus = old('status', $article->status?->value ?? 'draft');
    $sourceLocale = old(
        'source_locale',
        $article->source_locale ?? config('locales.default', 'pl')
    );
    $publishedAt = old(
        'published_at',
        $article->published_at?->format('Y-m-d\TH:i')
    );
@endphp

<div class="cms-editor-grid">
    <div class="cms-editor-main">
        <section class="cms-panel">
            <div class="multilang-header">
                <div>
                    <span class="admin-eyebrow">{{ __('cms.articles.form.languages') }}</span>
                    <h2>{{ __('cms.articles.form.localized_content') }}</h2>
                </div>

                <div class="translation-tabs" role="tablist">
                    @foreach ($supportedLocales as $locale => $language)
                        @php
                            $translation = $article->exists
                                ? $article->translation($locale)
                                : null;
                        @endphp

                        <button
                            type="button"
                            class="translation-tab {{ $loop->first ? 'is-active' : '' }}"
                            data-translation-tab="{{ $locale }}"
                        >
                            {{ strtoupper($locale) }}
                            <span
                                class="translation-tab-dot translation-tab-dot-{{ $translation?->translation_status?->value ?? 'missing' }}"
                            ></span>
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach ($supportedLocales as $locale => $language)
                @php
                    $translation = $article->exists
                        ? $article->translation($locale)
                        : null;

                    $oldPrefix = "translations.{$locale}";
                    $translationStatus = old(
                        "{$oldPrefix}.translation_status",
                        $translation?->translation_status?->value ?? 'draft'
                    );
                @endphp

                <div
                    class="translation-pane {{ $loop->first ? 'is-active' : '' }}"
                    data-translation-pane="{{ $locale }}"
                >
                    <div class="translation-pane-heading">
                        <div>
                            <strong>{{ $language['native'] }}</strong>
                            <span>{{ strtoupper($locale) }}</span>
                        </div>

                        <div class="translation-source-note" data-source-note="{{ $locale }}">
                            {{ __('cms.articles.form.source_language_badge') }}
                        </div>
                    </div>

                    <div class="cms-field">
                        <label for="title_{{ $locale }}">
                            {{ __('cms.articles.form.title') }}
                        </label>
                        <input
                            id="title_{{ $locale }}"
                            name="translations[{{ $locale }}][title]"
                            type="text"
                            value="{{ old("{$oldPrefix}.title", $translation?->title) }}"
                            maxlength="220"
                            data-slug-source="{{ $locale }}"
                        >
                    </div>

                    <div class="cms-field">
                        <label for="slug_{{ $locale }}">
                            {{ __('cms.articles.form.slug') }}
                        </label>
                        <div class="cms-slug-row">
                            <span>/{{ $locale }}/articles/</span>
                            <input
                                id="slug_{{ $locale }}"
                                name="translations[{{ $locale }}][slug]"
                                type="text"
                                value="{{ old("{$oldPrefix}.slug", $translation?->slug) }}"
                                maxlength="240"
                                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                data-slug-target="{{ $locale }}"
                            >
                        </div>
                        <small>{{ __('cms.articles.form.slug_help') }}</small>
                    </div>

                    <div class="cms-field">
                        <label for="excerpt_{{ $locale }}">
                            {{ __('cms.articles.form.excerpt') }}
                        </label>
                        <textarea
                            id="excerpt_{{ $locale }}"
                            name="translations[{{ $locale }}][excerpt]"
                            rows="4"
                            maxlength="1000"
                        >{{ old("{$oldPrefix}.excerpt", $translation?->excerpt) }}</textarea>
                    </div>

                    <div class="cms-field">
                        <label>{{ __('cms.articles.form.body') }}</label>

                        <div class="wysiwyg" data-wysiwyg>
                            <div class="wysiwyg-toolbar" role="toolbar">
                                <button type="button" data-command="formatBlock" data-value="p">P</button>
                                <button type="button" data-command="formatBlock" data-value="h2">H2</button>
                                <button type="button" data-command="formatBlock" data-value="h3">H3</button>
                                <button type="button" data-command="bold"><strong>B</strong></button>
                                <button type="button" data-command="italic"><em>I</em></button>
                                <button type="button" data-command="insertUnorderedList">•</button>
                                <button type="button" data-command="insertOrderedList">1.</button>
                                <button type="button" data-command="formatBlock" data-value="blockquote">❝</button>
                                <button type="button" data-link>🔗</button>
                            </div>

                            <div
                                class="wysiwyg-editor"
                                contenteditable="true"
                                data-editor
                            >{!! old("{$oldPrefix}.body_html", $translation?->body_html) !!}</div>

                            <textarea
                                name="translations[{{ $locale }}][body_html]"
                                data-editor-output
                                hidden
                            >{{ old("{$oldPrefix}.body_html", $translation?->body_html) }}</textarea>
                        </div>
                    </div>

                    <div class="seo-panel">
                        <div class="seo-panel-heading">
                            <span>SEO</span>
                            <strong>{{ __('cms.articles.form.seo_heading') }}</strong>
                        </div>

                        <div class="cms-field">
                            <label for="seo_title_{{ $locale }}">
                                {{ __('cms.articles.form.seo_title') }}
                            </label>
                            <input
                                id="seo_title_{{ $locale }}"
                                name="translations[{{ $locale }}][seo_title]"
                                type="text"
                                value="{{ old("{$oldPrefix}.seo_title", $translation?->seo_title) }}"
                                maxlength="70"
                            >
                            <small>{{ __('cms.articles.form.seo_title_help') }}</small>
                        </div>

                        <div class="cms-field">
                            <label for="seo_description_{{ $locale }}">
                                {{ __('cms.articles.form.seo_description') }}
                            </label>
                            <textarea
                                id="seo_description_{{ $locale }}"
                                name="translations[{{ $locale }}][seo_description]"
                                rows="3"
                                maxlength="180"
                            >{{ old("{$oldPrefix}.seo_description", $translation?->seo_description) }}</textarea>
                        </div>
                    </div>

                    <div class="cms-field translation-workflow-field" data-translation-workflow="{{ $locale }}">
                        <label for="translation_status_{{ $locale }}">
                            {{ __('cms.articles.form.translation_status') }}
                        </label>

                        <select
                            id="translation_status_{{ $locale }}"
                            name="translations[{{ $locale }}][translation_status]"
                        >
                            @foreach ($translationStatuses as $status)
                                @if ($status->value !== 'source')
                                    <option
                                        value="{{ $status->value }}"
                                        @selected($translationStatus === $status->value)
                                    >
                                        {{ __('cms.translation_statuses.' . $status->value) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </section>
    </div>

    <aside class="cms-editor-sidebar">
        <section class="cms-panel">
            <h2>{{ __('cms.articles.form.publication') }}</h2>

            <div class="cms-field">
                <label for="category_id">{{ __('cms.articles.form.category') }}</label>
                <select id="category_id" name="category_id" required>
                    <option value="">{{ __('cms.articles.form.choose_category') }}</option>
                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected((string) old('category_id', $article->category_id) === (string) $category->id)
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field">
                <label for="source_locale">{{ __('cms.articles.form.source_locale') }}</label>
                <select id="source_locale" name="source_locale" data-source-locale required>
                    @foreach ($supportedLocales as $locale => $language)
                        <option value="{{ $locale }}" @selected($sourceLocale === $locale)>
                            {{ strtoupper($locale) }} — {{ $language['native'] }}
                        </option>
                    @endforeach
                </select>
                <small>{{ __('cms.articles.form.source_locale_help') }}</small>
            </div>

            <div class="cms-field">
                <label for="status">{{ __('cms.articles.form.status') }}</label>
                <select id="status" name="status" data-status-select required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>
                            {{ __('cms.articles.statuses.' . $status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cms-field" data-publication-date>
                <label for="published_at">{{ __('cms.articles.form.published_at') }}</label>
                <input
                    id="published_at"
                    name="published_at"
                    type="datetime-local"
                    value="{{ $publishedAt }}"
                >
                <small>{{ __('cms.articles.form.published_at_help') }}</small>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('cms.articles.form.hero') }}</h2>

            @if ($article->hero_image_path)
                <div class="cms-current-image">
                    <img src="{{ Storage::url($article->hero_image_path) }}" alt="">
                </div>

                <label class="cms-checkbox">
                    <input type="checkbox" name="remove_hero_image" value="1">
                    <span>{{ __('cms.articles.form.remove_hero') }}</span>
                </label>
            @endif

            <div class="cms-field">
                <label for="hero_image">{{ __('cms.articles.form.hero_upload') }}</label>
                <input
                    id="hero_image"
                    name="hero_image"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    data-image-input
                >
                <small>{{ __('cms.articles.form.hero_help') }}</small>
            </div>

            <div class="cms-image-preview" data-image-preview hidden>
                <img alt="{{ __('cms.articles.form.hero_preview') }}">
            </div>
        </section>

        <div class="cms-form-actions">
            <button class="cms-primary-button cms-submit-button" type="submit">
                {{ $article->exists ? __('cms.articles.form.save') : __('cms.articles.form.create') }}
            </button>

            <a class="cms-secondary-button" href="{{ route('admin.articles.index') }}">
                {{ __('cms.articles.form.cancel') }}
            </a>
        </div>
    </aside>
</div>
