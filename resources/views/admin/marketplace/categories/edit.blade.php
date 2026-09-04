@extends('admin.layout')
@section('title', __('marketplace.admin.categories.edit_title'))
@push('head') @vite('resources/css/admin-marketplace-categories.css') @endpush

@section('content')
<section class="market-admin">
    <header class="market-head">
        <div><span class="admin-eyebrow">MARKETPLACE</span><h1>{{ __('marketplace.admin.categories.edit_title') }}</h1><p>{{ $category->sourceTranslation()?->name ?? $category->name }}</p></div>
        <a class="market-button is-light" href="{{ route('admin.marketplace.categories.index') }}">{{ __('marketplace.admin.common.back') }}</a>
    </header>
    <form class="market-form market-card" method="post" action="{{ route('admin.marketplace.categories.update', $category) }}">
        @csrf @method('PUT')
        <label>{{ __('marketplace.admin.categories.source_locale') }}<select name="source_locale">@foreach($supportedLocales as $locale => $language)<option value="{{ $locale }}" @selected(old('source_locale', $category->source_locale) === $locale)>{{ strtoupper($locale) }} — {{ $language['native'] }}</option>@endforeach</select></label>
        <div class="market-translation-grid">
            @foreach($supportedLocales as $locale => $language)
                @php($translation = $category->translation($locale))
                <fieldset class="market-language-panel"><legend>{{ strtoupper($locale) }} — {{ $language['native'] }}</legend>
                    <label>{{ __('marketplace.admin.categories.name') }}<input name="translations[{{ $locale }}][name]" maxlength="150" value="{{ old("translations.$locale.name", $translation?->name) }}"></label>
                    <label>{{ __('marketplace.admin.categories.slug') }}<input name="translations[{{ $locale }}][slug]" maxlength="170" value="{{ old("translations.$locale.slug", $translation?->slug) }}"></label>
                    <label>{{ __('marketplace.admin.categories.description_field') }}<textarea name="translations[{{ $locale }}][description]" maxlength="2000">{{ old("translations.$locale.description", $translation?->description) }}</textarea></label>
                    <label>{{ __('marketplace.admin.categories.translation_status') }}<select name="translations[{{ $locale }}][translation_status]">@foreach($translationStatuses as $status)<option value="{{ $status->value }}" @selected(old("translations.$locale.translation_status", $translation?->translation_status?->value ?? 'draft') === $status->value)>{{ __('ai_translator.target_statuses.'.$status->value) }}</option>@endforeach</select></label>
                </fieldset>
            @endforeach
        </div>
        <label>{{ __('marketplace.admin.categories.sort_order') }}<input type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}"></label>
        <label class="market-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))>{{ __('marketplace.admin.common.active') }}</label>
        <div class="market-actions"><button class="market-button" type="submit">{{ __('marketplace.admin.common.save') }}</button></div>
    </form>
</section>
@endsection
