@extends('admin.layout')

@section('title', __('catalog.admin.products.title') . ' — ' . __('admin.title'))
@section('page_heading', __('catalog.admin.products.title'))

@section('content')
<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('catalog.admin.kicker') }}</span>
            <h1>{{ __('catalog.admin.products.title') }}</h1>
            <p>{{ __('catalog.admin.products.description') }}</p>
        </div>

        <div class="catalog-heading-actions">
            <a class="cms-secondary-button" href="{{ route('admin.product-categories.index') }}">
                {{ __('catalog.admin.categories.title') }}
            </a>
            <a class="cms-primary-button" href="{{ route('admin.products.create') }}">
                + {{ __('catalog.admin.products.new') }}
            </a>
        </div>
    </div>

    <form class="cms-filter-bar" method="get" action="{{ route('admin.products.index') }}">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('catalog.admin.products.filters.search') }}">

        <select name="status">
            <option value="">{{ __('catalog.admin.products.filters.all_statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                    {{ __('catalog.product_statuses.' . $status->value) }}
                </option>
            @endforeach
        </select>

        <select name="category">
            <option value="">{{ __('catalog.admin.products.filters.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                    {{ $category->sourceTranslation()?->name ?? ('#' . $category->id) }}
                </option>
            @endforeach
        </select>

        <button type="submit">{{ __('catalog.admin.products.filters.apply') }}</button>

        @if (request()->hasAny(['q', 'status', 'category']))
            <a href="{{ route('admin.products.index') }}">{{ __('catalog.admin.products.filters.clear') }}</a>
        @endif
    </form>

    <div class="cms-table-wrap">
        <table class="cms-table catalog-product-table">
            <thead>
                <tr>
                    <th>{{ __('catalog.admin.products.table.product') }}</th>
                    <th>{{ __('catalog.admin.products.table.category') }}</th>
                    <th>{{ __('catalog.admin.products.table.variants') }}</th>
                    <th>{{ __('catalog.admin.products.table.price') }}</th>
                    <th>{{ __('catalog.admin.products.table.stock') }}</th>
                    <th>{{ __('catalog.admin.products.table.status') }}</th>
                    <th>{{ __('catalog.admin.products.table.languages') }}</th>
                    <th class="cms-actions-cell">{{ __('catalog.admin.products.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $source = $product->sourceTranslation();
                        $primary = $product->primaryMedia();
                        $activeVariants = $product->variants->where('is_active', true);
                        $minPrice = $activeVariants->min(fn ($variant) => (float) $variant->price_gross);
                        $currency = $activeVariants->first()?->currency ?? 'PLN';
                        $stock = $activeVariants->where('track_stock', true)->sum('stock_quantity');
                    @endphp

                    <tr>
                        <td>
                            <div class="cms-title-cell">
                                @if ($primary)
                                    <img src="{{ $primary->url() }}" alt="">
                                @else
                                    <span class="cms-image-placeholder">3D</span>
                                @endif

                                <div>
                                    <strong>{{ $source?->name ?? '—' }}</strong>
                                    <span>{{ $product->brand ?: __('catalog.admin.products.no_brand') }}</span>
                                </div>
                            </div>
                        </td>

                        <td>{{ $product->category?->sourceTranslation()?->name ?? '—' }}</td>
                        <td>{{ $product->variants->count() }}</td>

                        <td>
                            @if ($minPrice !== null)
                                {{ number_format($minPrice, 2, ',', ' ') }} {{ $currency }}
                            @else
                                —
                            @endif
                        </td>

                        <td>{{ $stock }}</td>

                        <td>
                            <span class="product-status product-status-{{ $product->status->value }}">
                                {{ __('catalog.product_statuses.' . $product->status->value) }}
                            </span>
                        </td>

                        <td>
                            <div class="translation-status-list">
                                @foreach ($supportedLocales as $locale => $language)
                                    @php $translation = $product->translation($locale); @endphp
                                    <span class="translation-chip {{ $translation ? 'translation-chip-' . $translation->translation_status->value : 'translation-chip-missing' }}">
                                        {{ strtoupper($locale) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>

                        <td class="cms-actions-cell">
                            <a class="cms-action-button" href="{{ route('admin.products.edit', $product) }}">
                                {{ __('catalog.admin.common.edit') }}
                            </a>

                            <form
                                method="post"
                                action="{{ route('admin.products.destroy', $product) }}"
                                onsubmit="return confirm('{{ __('catalog.admin.products.delete_confirm') }}')"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="cms-action-button cms-action-danger" type="submit">
                                    {{ __('catalog.admin.common.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="cms-empty">
                            {{ __('catalog.admin.products.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="cms-pagination">{{ $products->links() }}</div>
    @endif
</section>
@endsection
