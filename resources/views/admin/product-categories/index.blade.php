@extends('admin.layout')

@section('title', __('catalog.admin.categories.title') . ' — ' . __('admin.title'))
@section('page_heading', __('catalog.admin.categories.title'))

@section('content')
<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('catalog.admin.kicker') }}</span>
            <h1>{{ __('catalog.admin.categories.title') }}</h1>
            <p>{{ __('catalog.admin.categories.description') }}</p>
        </div>
        <a class="cms-secondary-button" href="{{ route('admin.products.index') }}">
            {{ __('catalog.admin.products.title') }}
        </a>
    </div>

    <div class="catalog-category-grid">
        <section class="cms-panel">
            <h2>{{ __('catalog.admin.categories.new') }}</h2>

            <form method="post" action="{{ route('admin.product-categories.store') }}" class="catalog-category-form">
                @csrf

                <div class="cms-field">
                    <label>{{ __('catalog.admin.categories.form.parent') }}</label>
                    <select name="parent_id">
                        <option value="">{{ __('catalog.admin.categories.form.root') }}</option>
                        @foreach ($categoryTree as $row)
                            @php
                                $parentOption = $row['category'];
                                $prefix = str_repeat('— ', min($row['depth'], 6));
                            @endphp
                            <option
                                value="{{ $parentOption->id }}"
                                @selected((string) old('parent_id') === (string) $parentOption->id)
                            >
                                {{ $prefix }}{{ $parentOption->sourceTranslation()?->name ?? ('#' . $parentOption->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="cms-field">
                    <label>{{ __('catalog.admin.common.source_locale') }}</label>
                    <select name="source_locale">
                        @foreach ($supportedLocales as $locale => $language)
                            <option
                                value="{{ $locale }}"
                                @selected(old('source_locale', config('locales.default', 'pl')) === $locale)
                            >
                                {{ strtoupper($locale) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @foreach ($supportedLocales as $locale => $language)
                    <fieldset class="catalog-language-fieldset">
                        <legend>{{ strtoupper($locale) }} — {{ $language['native'] }}</legend>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.categories.form.name') }}</label>
                            <input
                                name="translations[{{ $locale }}][name]"
                                type="text"
                                value="{{ old("translations.{$locale}.name") }}"
                                maxlength="160"
                            >
                        </div>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.categories.form.slug') }}</label>
                            <input
                                name="translations[{{ $locale }}][slug]"
                                type="text"
                                value="{{ old("translations.{$locale}.slug") }}"
                                maxlength="180"
                            >
                        </div>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.categories.form.description') }}</label>
                            <textarea
                                name="translations[{{ $locale }}][description]"
                                rows="3"
                                maxlength="3000"
                            >{{ old("translations.{$locale}.description") }}</textarea>
                        </div>

                        <div class="cms-field">
                            <label>{{ __('catalog.admin.common.translation_status') }}</label>
                            <select name="translations[{{ $locale }}][translation_status]">
                                @foreach (['draft', 'review', 'ready'] as $status)
                                    <option
                                        value="{{ $status }}"
                                        @selected(old("translations.{$locale}.translation_status", 'draft') === $status)
                                    >
                                        {{ __('catalog.translation_statuses.' . $status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </fieldset>
                @endforeach

                <div class="cms-field">
                    <label>{{ __('catalog.admin.categories.form.order') }}</label>
                    <input
                        name="sort_order"
                        type="number"
                        min="0"
                        max="9999"
                        value="{{ old('sort_order', 0) }}"
                    >
                </div>

                <label class="cms-checkbox">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', '1'))
                    >
                    <span>{{ __('catalog.admin.categories.form.active') }}</span>
                </label>

                <button class="cms-primary-button" type="submit">
                    {{ __('catalog.admin.categories.form.create') }}
                </button>
            </form>
        </section>

        <section class="cms-panel">
            <h2>{{ __('catalog.admin.categories.existing') }}</h2>

            <div class="catalog-category-list">
                @forelse ($categoryTree as $row)
                    @php
                        $category = $row['category'];
                        $depth = $row['depth'];
                        $blockedParentIds = $category->descendantIdsFrom(
                            $categories
                        );
                        $path = $category->pathFrom($categories)
                            ->map(
                                fn ($item) =>
                                    $item->sourceTranslation()?->name
                                    ?? ('#' . $item->id)
                            )
                            ->implode(' › ');
                    @endphp

                    <details
                        class="catalog-category-item"
                        style="margin-left: {{ min($depth, 6) * 16 }}px"
                    >
                        <summary>
                            <span>
                                <strong>
                                    @if ($depth > 0)
                                        ↳
                                    @endif
                                    {{ $category->sourceTranslation()?->name ?? ('#' . $category->id) }}
                                </strong>
                                <small>
                                    {{ $path }} ·
                                    {{ strtoupper($category->source_locale) }} ·
                                    {{ $category->products_count }}
                                    {{ __('catalog.admin.categories.products_short') }}
                                    @if ($category->children_count)
                                        · {{ $category->children_count }}
                                        {{ __('catalog.admin.categories.children_short') }}
                                    @endif
                                </small>
                            </span>

                            <span class="catalog-active-badge {{ $category->is_active ? 'is-active' : '' }}">
                                {{ $category->is_active ? __('catalog.admin.common.active') : __('catalog.admin.common.inactive') }}
                            </span>
                        </summary>

                        <div class="catalog-category-item-body">
                            <form
                                method="post"
                                action="{{ route('admin.product-categories.update', $category) }}"
                                class="catalog-category-form"
                            >
                                @csrf
                                @method('PUT')

                                <div class="cms-field">
                                    <label>{{ __('catalog.admin.categories.form.parent') }}</label>
                                    <select name="parent_id">
                                        <option value="">
                                            {{ __('catalog.admin.categories.form.root') }}
                                        </option>

                                        @foreach ($categoryTree as $parentRow)
                                            @php
                                                $parentOption = $parentRow['category'];
                                                $prefix = str_repeat(
                                                    '— ',
                                                    min($parentRow['depth'], 6)
                                                );
                                            @endphp

                                            @continue(
                                                in_array(
                                                    (int) $parentOption->id,
                                                    $blockedParentIds,
                                                    true
                                                )
                                            )

                                            <option
                                                value="{{ $parentOption->id }}"
                                                @selected((string) $category->parent_id === (string) $parentOption->id)
                                            >
                                                {{ $prefix }}{{ $parentOption->sourceTranslation()?->name ?? ('#' . $parentOption->id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="cms-field">
                                    <label>{{ __('catalog.admin.common.source_locale') }}</label>
                                    <select name="source_locale">
                                        @foreach ($supportedLocales as $locale => $language)
                                            <option
                                                value="{{ $locale }}"
                                                @selected($category->source_locale === $locale)
                                            >
                                                {{ strtoupper($locale) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @foreach ($supportedLocales as $locale => $language)
                                    @php
                                        $translation = $category->translation($locale);
                                    @endphp

                                    <fieldset class="catalog-language-fieldset">
                                        <legend>
                                            {{ strtoupper($locale) }} — {{ $language['native'] }}
                                        </legend>

                                        <div class="cms-field">
                                            <label>{{ __('catalog.admin.categories.form.name') }}</label>
                                            <input
                                                name="translations[{{ $locale }}][name]"
                                                type="text"
                                                value="{{ $translation?->name }}"
                                                maxlength="160"
                                            >
                                        </div>

                                        <div class="cms-field">
                                            <label>{{ __('catalog.admin.categories.form.slug') }}</label>
                                            <input
                                                name="translations[{{ $locale }}][slug]"
                                                type="text"
                                                value="{{ $translation?->slug }}"
                                                maxlength="180"
                                            >
                                        </div>

                                        <div class="cms-field">
                                            <label>{{ __('catalog.admin.categories.form.description') }}</label>
                                            <textarea
                                                name="translations[{{ $locale }}][description]"
                                                rows="3"
                                                maxlength="3000"
                                            >{{ $translation?->description }}</textarea>
                                        </div>

                                        <div class="cms-field">
                                            <label>{{ __('catalog.admin.common.translation_status') }}</label>
                                            <select name="translations[{{ $locale }}][translation_status]">
                                                @foreach (['draft', 'review', 'ready'] as $status)
                                                    <option
                                                        value="{{ $status }}"
                                                        @selected(($translation?->translation_status?->value ?? 'draft') === $status)
                                                    >
                                                        {{ __('catalog.translation_statuses.' . $status) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </fieldset>
                                @endforeach

                                <div class="cms-field">
                                    <label>{{ __('catalog.admin.categories.form.order') }}</label>
                                    <input
                                        name="sort_order"
                                        type="number"
                                        min="0"
                                        max="9999"
                                        value="{{ $category->sort_order }}"
                                    >
                                </div>

                                <label class="cms-checkbox">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        @checked($category->is_active)
                                    >
                                    <span>{{ __('catalog.admin.categories.form.active') }}</span>
                                </label>

                                <button class="cms-secondary-button" type="submit">
                                    {{ __('catalog.admin.common.save') }}
                                </button>
                            </form>

                            <form
                                method="post"
                                action="{{ route('admin.product-categories.destroy', $category) }}"
                                onsubmit="return confirm('{{ __('catalog.admin.categories.delete_confirm') }}')"
                            >
                                @csrf
                                @method('DELETE')

                                <button class="cms-danger-button" type="submit">
                                    {{ __('catalog.admin.common.delete') }}
                                </button>
                            </form>
                        </div>
                    </details>
                @empty
                    <p class="cms-empty">
                        {{ __('catalog.admin.categories.empty') }}
                    </p>
                @endforelse
            </div>
        </section>
    </div>
</section>
@endsection
