@php
    $isEdit = $archiveItem !== null;
    $pl = $isEdit ? $archiveItem->translation('pl') : null;
    $en = $isEdit ? $archiveItem->translation('en') : null;
@endphp

<div class="admin-archive-form-grid">
    <div>
        <section class="cms-panel">
            <h2>{{ __('archive.admin.metadata') }}</h2>

            <div class="admin-archive-field-grid">
                <div class="cms-field">
                    <label for="source-locale">{{ __('archive.admin.source_locale') }}</label>
                    <select id="source-locale" name="source_locale" data-archive-source-locale required>
                        @foreach (['pl', 'en'] as $locale)
                            <option
                                value="{{ $locale }}"
                                @selected(old('source_locale', $archiveItem?->source_locale ?? 'pl') === $locale)
                            >
                                {{ strtoupper($locale) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="cms-field">
                    <label for="technique">{{ __('archive.admin.technique') }}</label>
                    <select id="technique" name="technique" required>
                        @foreach ($techniques as $technique)
                            <option
                                value="{{ $technique }}"
                                @selected(old('technique', $archiveItem?->technique ?? 'stereocard') === $technique)
                            >
                                {{ __('archive.techniques.' . $technique) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="cms-field">
                    <label for="year-from">{{ __('archive.admin.year_from') }}</label>
                    <input
                        id="year-from"
                        type="number"
                        name="year_from"
                        min="1800"
                        max="2100"
                        value="{{ old('year_from', $archiveItem?->year_from ?? 1900) }}"
                        required
                    >
                </div>

                <div class="cms-field">
                    <label for="year-to">{{ __('archive.admin.year_to') }}</label>
                    <input
                        id="year-to"
                        type="number"
                        name="year_to"
                        min="1800"
                        max="2100"
                        value="{{ old('year_to', $archiveItem?->year_to) }}"
                    >
                </div>

                <label class="admin-archive-checkbox">
                    <input
                        type="checkbox"
                        name="circa"
                        value="1"
                        @checked(old('circa', $archiveItem?->circa ?? false))
                    >
                    <span>{{ __('archive.admin.circa') }}</span>
                </label>

                <label class="admin-archive-checkbox">
                    <input
                        type="checkbox"
                        name="is_published"
                        value="1"
                        @checked(old('is_published', $archiveItem?->is_published ?? false))
                    >
                    <span>{{ __('archive.admin.publish') }}</span>
                </label>
            </div>

            <div class="admin-archive-field-grid admin-archive-field-grid-wide">
                <div class="cms-field">
                    <label for="creator">{{ __('archive.admin.creator') }}</label>
                    <input
                        id="creator"
                        type="text"
                        name="creator"
                        maxlength="190"
                        value="{{ old('creator', $archiveItem?->creator) }}"
                    >
                </div>

                <div class="cms-field">
                    <label for="publisher">{{ __('archive.admin.publisher') }}</label>
                    <input
                        id="publisher"
                        type="text"
                        name="publisher"
                        maxlength="190"
                        value="{{ old('publisher', $archiveItem?->publisher) }}"
                    >
                </div>

                <div class="cms-field">
                    <label for="country">{{ __('archive.admin.country') }}</label>
                    <input
                        id="country"
                        type="text"
                        name="country"
                        maxlength="120"
                        value="{{ old('country', $archiveItem?->country) }}"
                    >
                </div>

                <div class="cms-field">
                    <label for="collection-name">{{ __('archive.admin.collection') }}</label>
                    <input
                        id="collection-name"
                        type="text"
                        name="collection_name"
                        maxlength="190"
                        value="{{ old('collection_name', $archiveItem?->collection_name) }}"
                    >
                </div>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('archive.admin.source_and_rights') }}</h2>

            <div class="admin-archive-field-grid">
                <div class="cms-field">
                    <label for="source-name">{{ __('archive.admin.source_name') }}</label>
                    <input
                        id="source-name"
                        type="text"
                        name="source_name"
                        maxlength="190"
                        value="{{ old('source_name', $archiveItem?->source_name) }}"
                        required
                    >
                </div>

                <div class="cms-field">
                    <label for="rights-status">{{ __('archive.admin.rights_status') }}</label>
                    <select id="rights-status" name="rights_status" required>
                        @foreach ($rightsStatuses as $rightsStatus)
                            <option
                                value="{{ $rightsStatus }}"
                                @selected(old('rights_status', $archiveItem?->rights_status ?? 'public_domain') === $rightsStatus)
                            >
                                {{ __('archive.rights.' . $rightsStatus) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="cms-field admin-archive-full">
                    <label for="source-url">{{ __('archive.admin.source_url') }}</label>
                    <input
                        id="source-url"
                        type="url"
                        name="source_url"
                        maxlength="1000"
                        value="{{ old('source_url', $archiveItem?->source_url) }}"
                        placeholder="https://..."
                    >
                </div>

                <div class="cms-field admin-archive-full">
                    <label for="rights-note">{{ __('archive.admin.rights_note') }}</label>
                    <textarea
                        id="rights-note"
                        name="rights_note"
                        rows="4"
                        maxlength="2000"
                    >{{ old('rights_note', $archiveItem?->rights_note) }}</textarea>
                </div>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('archive.admin.translations') }}</h2>
            <p class="admin-archive-panel-note">{{ __('archive.admin.translations_help') }}</p>

            @foreach (['pl' => $pl, 'en' => $en] as $locale => $translation)
                <div class="admin-archive-translation" data-archive-translation="{{ $locale }}">
                    <div class="admin-archive-translation-heading">
                        <strong>{{ strtoupper($locale) }}</strong>
                        <span data-archive-source-badge="{{ $locale }}">
                            {{ __('archive.admin.source_badge') }}
                        </span>
                    </div>

                    <div class="admin-archive-field-grid">
                        <div class="cms-field">
                            <label for="title-{{ $locale }}">{{ __('archive.admin.title_field') }}</label>
                            <input
                                id="title-{{ $locale }}"
                                type="text"
                                name="translations[{{ $locale }}][title]"
                                maxlength="220"
                                value="{{ old("translations.$locale.title", $translation?->title) }}"
                                data-archive-title="{{ $locale }}"
                            >
                        </div>

                        <div class="cms-field">
                            <label for="slug-{{ $locale }}">Slug</label>
                            <input
                                id="slug-{{ $locale }}"
                                type="text"
                                name="translations[{{ $locale }}][slug]"
                                maxlength="220"
                                value="{{ old("translations.$locale.slug", $translation?->slug) }}"
                                data-archive-slug="{{ $locale }}"
                            >
                        </div>

                        <div class="cms-field">
                            <label for="status-{{ $locale }}">{{ __('archive.admin.translation_status') }}</label>
                            <select
                                id="status-{{ $locale }}"
                                name="translations[{{ $locale }}][translation_status]"
                                data-archive-status="{{ $locale }}"
                            >
                                @foreach ($translationStatuses as $translationStatus)
                                    <option
                                        value="{{ $translationStatus->value }}"
                                        @selected(old("translations.$locale.translation_status", $translation?->translation_status?->value ?? 'draft') === $translationStatus->value)
                                    >
                                        {{ __('archive.translation_statuses.' . $translationStatus->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cms-field admin-archive-full">
                            <label for="description-{{ $locale }}">{{ __('archive.admin.description_field') }}</label>
                            <textarea
                                id="description-{{ $locale }}"
                                name="translations[{{ $locale }}][description]"
                                rows="4"
                                maxlength="4000"
                            >{{ old("translations.$locale.description", $translation?->description) }}</textarea>
                        </div>

                        <div class="cms-field admin-archive-full">
                            <label for="history-{{ $locale }}">{{ __('archive.admin.historical_note') }}</label>
                            <textarea
                                id="history-{{ $locale }}"
                                name="translations[{{ $locale }}][historical_note]"
                                rows="7"
                                maxlength="20000"
                            >{{ old("translations.$locale.historical_note", $translation?->historical_note) }}</textarea>
                        </div>

                        <div class="cms-field">
                            <label for="seo-title-{{ $locale }}">SEO title</label>
                            <input
                                id="seo-title-{{ $locale }}"
                                type="text"
                                name="translations[{{ $locale }}][seo_title]"
                                maxlength="255"
                                value="{{ old("translations.$locale.seo_title", $translation?->seo_title) }}"
                            >
                        </div>

                        <div class="cms-field">
                            <label for="seo-description-{{ $locale }}">SEO description</label>
                            <textarea
                                id="seo-description-{{ $locale }}"
                                name="translations[{{ $locale }}][seo_description]"
                                rows="3"
                                maxlength="500"
                            >{{ old("translations.$locale.seo_description", $translation?->seo_description) }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>
    </div>

    <aside>
        <section class="cms-panel">
            <h2>{{ __('archive.admin.images') }}</h2>

            <div class="cms-field">
                <label for="original-image">{{ __('archive.admin.original_image') }}</label>

                @if ($archiveItem)
                    <img
                        class="admin-archive-current-image"
                        src="{{ $archiveItem->originalImageUrl() }}"
                        alt=""
                    >
                @endif

                <input
                    id="original-image"
                    type="file"
                    name="original_image"
                    accept="image/jpeg,image/png,image/webp"
                    @required(!$archiveItem)
                >
                <small>{{ __('archive.admin.original_image_help') }}</small>
            </div>
        </section>

        <section class="cms-panel">
            <h2>{{ __('archive.admin.stereo_pair') }}</h2>
            <p class="admin-archive-panel-note">{{ __('archive.admin.stereo_pair_help') }}</p>

            @if ($archiveItem?->hasStereoPair())
                <div class="admin-archive-existing-pair">
                    <img src="{{ $archiveItem->leftImageUrl() }}" alt="L">
                    <img src="{{ $archiveItem->rightImageUrl() }}" alt="R">
                </div>

                <label class="admin-archive-checkbox">
                    <input
                        type="checkbox"
                        name="remove_stereo_pair"
                        value="1"
                    >
                    <span>{{ __('archive.admin.remove_stereo_pair') }}</span>
                </label>
            @endif

            <div class="cms-field">
                <label for="left-image">{{ __('archive.admin.left_image') }}</label>
                <input
                    id="left-image"
                    type="file"
                    name="left_image"
                    accept="image/jpeg,image/png,image/webp"
                >
            </div>

            <div class="cms-field">
                <label for="right-image">{{ __('archive.admin.right_image') }}</label>
                <input
                    id="right-image"
                    type="file"
                    name="right_image"
                    accept="image/jpeg,image/png,image/webp"
                >
            </div>
        </section>
    </aside>
</div>
