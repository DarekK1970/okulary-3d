@extends('admin.layout')
@section('title', __('marketplace.admin.categories.title'))
@push('head') @vite('resources/css/admin-marketplace-categories.css') @endpush

@section('content')
<section class="market-admin">
    <header class="market-head"><div><span class="admin-eyebrow">MARKETPLACE</span><h1>{{ __('marketplace.admin.categories.title') }}</h1><p>{{ __('marketplace.admin.categories.description') }}</p></div></header>

    <article class="market-card market-category-create">
        <h2>{{ __('marketplace.admin.categories.new') }}</h2>
        <form class="market-form market-category-create-form" method="post" action="{{ route('admin.marketplace.categories.store') }}">
            @csrf
            <label>{{ __('marketplace.admin.categories.name') }}<input name="name" required maxlength="150" value="{{ old('name') }}"></label>
            <label>{{ __('marketplace.admin.categories.slug') }}<input name="slug" maxlength="170" value="{{ old('slug') }}"></label>
            <label class="market-category-description">{{ __('marketplace.admin.categories.description_field') }}<textarea name="description" maxlength="2000">{{ old('description') }}</textarea></label>
            <label>{{ __('marketplace.admin.categories.sort_order') }}<input type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', 0) }}"></label>
            <label class="market-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1'))>{{ __('marketplace.admin.common.active') }}</label>
            <button class="market-button" type="submit">{{ __('marketplace.admin.categories.create') }}</button>
        </form>
    </article>

    <section><h2>{{ __('marketplace.admin.categories.existing') }}</h2><div class="market-table-wrap">
        <table class="market-table market-category-table"><thead><tr>
            <th>{{ __('marketplace.admin.categories.name') }}</th><th>{{ __('marketplace.admin.categories.languages') }}</th><th>{{ __('marketplace.admin.categories.products') }}</th><th>{{ __('marketplace.admin.categories.sort_order') }}</th><th>{{ __('marketplace.admin.categories.status') }}</th><th>{{ __('marketplace.admin.common.actions') }}</th>
        </tr></thead><tbody>
        @forelse($categories as $category)
            @php($source = $category->sourceTranslation())
            <tr>
                <td><strong>{{ $source?->name ?? $category->name }}</strong><small class="market-table-subtitle">{{ $source?->slug ?? $category->slug }}</small></td>
                <td><div class="market-language-badges">@foreach(config('locales.supported', []) as $locale => $language) @php($translation = $category->translation($locale)) <span class="market-language-badge {{ $translation ? 'is-present' : '' }}">{{ strtoupper($locale) }} · {{ $translation ? __('ai_translator.target_statuses.'.$translation->translation_status->value) : __('ai_translator.target_statuses.missing') }}</span>@endforeach</div></td>
                <td>{{ $category->products_count }}</td><td>{{ $category->sort_order }}</td>
                <td><span class="market-status {{ $category->is_active ? 'is-active' : '' }}">{{ $category->is_active ? __('marketplace.admin.common.active') : __('marketplace.admin.common.inactive') }}</span></td>
                <td>@include('admin.marketplace._action-icons', ['item' => $category, 'editUrl' => route('admin.marketplace.categories.edit', $category), 'translationType' => \App\Services\AiTranslationService::TYPE_MARKETPLACE_CATEGORY, 'canDelete' => $category->products_count === 0, 'deleteUrl' => route('admin.marketplace.categories.destroy', $category)])</td>
            </tr>
        @empty
            <tr><td colspan="6">{{ __('marketplace.admin.categories.empty') }}</td></tr>
        @endforelse
        </tbody></table>
    </div></section>
</section>
@endsection
