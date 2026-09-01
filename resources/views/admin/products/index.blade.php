@extends('admin.layout')

@section('title', __('catalog.admin.products.title') . ' — ' . __('admin.title'))
@section('page_heading', __('catalog.admin.products.title'))

@section('content')
<style>
.product-action-icons {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    white-space: nowrap;
}

.product-action-icons form {
    margin: 0;
}

.product-action-icon {
    width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    padding: 0;
    border: 1px solid #d7e0ea;
    border-radius: 9px;
    background: #fff;
    color: #445168;
    cursor: pointer;
    text-decoration: none;
    transition:
        border-color .15s ease,
        background .15s ease,
        color .15s ease,
        transform .15s ease;
}

.product-action-icon:hover:not(:disabled) {
    border-color: #99cfe3;
    background: #f3fbfe;
    color: #008dbb;
    transform: translateY(-1px);
}

.product-action-icon:disabled {
    opacity: .38;
    cursor: not-allowed;
}

.product-action-icon svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.product-action-icon.is-ai {
    color: #7a4fc8;
}

.product-action-icon.is-ai:hover:not(:disabled) {
    border-color: #c9b8ef;
    background: #faf7ff;
    color: #6938b6;
}

.product-action-icon.is-translate {
    color: #087aa5;
}

.product-action-icon.is-danger {
    color: #c33d50;
}

.product-action-icon.is-danger:hover:not(:disabled) {
    border-color: #f0b8c0;
    background: #fff5f6;
    color: #a7283a;
}
</style>

<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('catalog.admin.kicker') }}</span>
            <h1>{{ __('catalog.admin.products.title') }}</h1>
            <p>{{ __('catalog.admin.products.description') }}</p>
        </div>

        <div class="catalog-heading-actions">
            <a
                class="cms-secondary-button"
                href="{{ route('admin.shipping.index') }}"
            >
                🚚 {{ __('shipping.admin.menu') }}
            </a>

            <a
                class="cms-secondary-button"
                href="{{ route('admin.product-categories.index') }}"
            >
                {{ __('catalog.admin.categories.title') }}
            </a>

            <a
                class="cms-primary-button"
                href="{{ route('admin.products.create') }}"
            >
                + {{ __('catalog.admin.products.new') }}
            </a>
        </div>
    </div>

    <form
        class="cms-filter-bar"
        method="get"
        action="{{ route('admin.products.index') }}"
    >
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('catalog.admin.products.filters.search') }}"
        >

        <select name="status">
            <option value="">
                {{ __('catalog.admin.products.filters.all_statuses') }}
            </option>

            @foreach ($statuses as $status)
                <option
                    value="{{ $status->value }}"
                    @selected(
                        request('status')
                        === $status->value
                    )
                >
                    {{ __(
                        'catalog.product_statuses.'
                        . $status->value
                    ) }}
                </option>
            @endforeach
        </select>

        <select name="category">
            <option value="">
                {{ __('catalog.admin.products.filters.all_categories') }}
            </option>

            @foreach ($categories as $category)
                <option
                    value="{{ $category->id }}"
                    @selected(
                        (string) request('category')
                        === (string) $category->id
                    )
                >
                    {{ $category->sourceTranslation()?->name
                        ?? ('#' . $category->id) }}
                </option>
            @endforeach
        </select>

        <button type="submit">
            {{ __('catalog.admin.products.filters.apply') }}
        </button>

        @if (request()->hasAny([
            'q',
            'status',
            'category'
        ]))
            <a href="{{ route('admin.products.index') }}">
                {{ __('catalog.admin.products.filters.clear') }}
            </a>
        @endif
    </form>

    <div class="cms-table-wrap">
        <table class="cms-table catalog-product-table">
            <thead>
                <tr>
                    <th>
                        {{ __('catalog.admin.products.table.product') }}
                    </th>
                    <th>
                        {{ __('catalog.admin.products.table.category') }}
                    </th>
                    <th>
                        {{ __('catalog.admin.products.table.variants') }}
                    </th>
                    <th>
                        {{ __('catalog.admin.products.table.price') }}
                    </th>
                    <th>
                        {{ __('catalog.admin.products.table.stock') }}
                    </th>
                    <th>
                        {{ __('catalog.admin.products.table.status') }}
                    </th>
                    <th>
                        {{ __('catalog.admin.products.table.languages') }}
                    </th>
                    <th class="cms-actions-cell">
                        {{ __('catalog.admin.products.table.actions') }}
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($products as $product)
                    @php
                        $source = $product->sourceTranslation();
                        $primary = $product->primaryMedia();

                        $activeVariants = $product
                            ->variants
                            ->where('is_active', true);

                        $minPrice = $activeVariants->min(
                            fn ($variant) =>
                                (float) $variant->price_gross
                        );

                        $currency =
                            $activeVariants->first()?->currency
                            ?? 'PLN';

                        $stock = $activeVariants
                            ->where('track_stock', true)
                            ->sum('stock_quantity');

                        $seoComplete =
                            filled($source?->seo_title)
                            && filled($source?->seo_description);

                        $targetLocale = collect(
                            array_keys($supportedLocales)
                        )->first(
                            fn ($locale) =>
                                $locale
                                !== $product->source_locale
                        );

                        $targetTranslation = $targetLocale
                            ? $product->translation($targetLocale)
                            : null;

                        $translationLocked =
                            $targetTranslation
                            && $targetTranslation
                                ->isPubliclyReady();

                        $translatorDisabled =
                            ! $seoComplete
                            || $translationLocked;

                        $translatorTitle = ! $seoComplete
                            ? __('product_ai.tooltips.translate_needs_seo')
                            : (
                                $translationLocked
                                ? __('product_ai.tooltips.translate_locked')
                                : __('product_ai.actions.translate')
                            );
                    @endphp

                    <tr>
                        <td>
                            <div class="cms-title-cell">
                                @if ($primary)
                                    <img
                                        src="{{ $primary->url() }}"
                                        alt=""
                                    >
                                @else
                                    <span class="cms-image-placeholder">
                                        3D
                                    </span>
                                @endif

                                <div>
                                    <strong>
                                        {{ $source?->name ?? '—' }}
                                    </strong>
                                    <span>
                                        {{ $product->brand
                                            ?: __('catalog.admin.products.no_brand') }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td>
                            {{ $product->category
                                ?->sourceTranslation()
                                ?->name ?? '—' }}
                        </td>

                        <td>{{ $product->variants->count() }}</td>

                        <td>
                            @if ($minPrice !== null)
                                {{ number_format(
                                    $minPrice,
                                    2,
                                    ',',
                                    ' '
                                ) }}
                                {{ $currency }}
                            @else
                                —
                            @endif
                        </td>

                        <td>{{ $stock }}</td>

                        <td>
                            <span
                                class="product-status product-status-{{ $product->status->value }}"
                            >
                                {{ __(
                                    'catalog.product_statuses.'
                                    . $product->status->value
                                ) }}
                            </span>
                        </td>

                        <td>
                            <div class="translation-status-list">
                                @foreach (
                                    $supportedLocales
                                    as $locale => $language
                                )
                                    @php
                                        $translation =
                                            $product->translation(
                                                $locale
                                            );
                                    @endphp

                                    <span
                                        class="translation-chip {{ $translation
                                            ? 'translation-chip-' . $translation->translation_status->value
                                            : 'translation-chip-missing' }}"
                                    >
                                        {{ strtoupper($locale) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>

                        <td class="cms-actions-cell">
                            <div class="product-action-icons">
                                <form
                                    method="post"
                                    action="{{ route(
                                        'admin.translations.translate',
                                        [
                                            'type' => \App\Services\ProductSeoService::TYPE,
                                            'id' => $product->id,
                                        ]
                                    ) }}"
                                >
                                    @csrf

                                    <button
                                        class="product-action-icon is-ai"
                                        type="submit"
                                        title="{{ $seoComplete
                                            ? __('product_ai.tooltips.seo_complete')
                                            : __('product_ai.actions.seo_fill') }}"
                                        aria-label="{{ __('product_ai.actions.seo_fill') }}"
                                        @disabled($seoComplete)
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path d="M12 3l1.1 3.1L16 7.2l-2.9 1.1L12 11.5l-1.1-3.2L8 7.2l2.9-1.1L12 3Z"/>
                                            <path d="M18.2 12.5l.8 2.2 2.1.8-2.1.8-.8 2.2-.8-2.2-2.1-.8 2.1-.8.8-2.2Z"/>
                                            <path d="M7.5 13.5l1.2 3.3 3.3 1.2-3.3 1.2-1.2 3.3-1.2-3.3L3 18l3.3-1.2 1.2-3.3Z"/>
                                        </svg>
                                    </button>
                                </form>

                                <form
                                    method="post"
                                    action="{{ route(
                                        'admin.translations.translate',
                                        [
                                            'type' => \App\Services\AiTranslationService::TYPE_PRODUCT,
                                            'id' => $product->id,
                                        ]
                                    ) }}"
                                >
                                    @csrf

                                    <button
                                        class="product-action-icon is-translate"
                                        type="submit"
                                        title="{{ $translatorTitle }}"
                                        aria-label="{{ __('product_ai.actions.translate') }}"
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

                                <a
                                    class="product-action-icon"
                                    href="{{ route(
                                        'admin.products.edit',
                                        $product
                                    ) }}"
                                    title="{{ __('product_ai.actions.edit') }}"
                                    aria-label="{{ __('product_ai.actions.edit') }}"
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
                                        'admin.products.destroy',
                                        $product
                                    ) }}"
                                    onsubmit="return confirm('{{ __('catalog.admin.products.delete_confirm') }}')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="product-action-icon is-danger"
                                        type="submit"
                                        title="{{ __('product_ai.actions.delete') }}"
                                        aria-label="{{ __('product_ai.actions.delete') }}"
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
                        <td
                            colspan="8"
                            class="cms-empty"
                        >
                            {{ __('catalog.admin.products.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="cms-pagination">
            {{ $products->links() }}
        </div>
    @endif
</section>
@endsection
