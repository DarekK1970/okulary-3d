@extends('layouts.app')

@section(
    'title',
    ($selectedCategory?->seo_title
        ?: $selectedCategory?->name
        ?: __('catalog.public.shop_title'))
    . ' — ' . __('site.title')
)
@section(
    'meta_description',
    $selectedCategory?->seo_description
        ?: $selectedCategory?->description
        ?: __('catalog.public.shop_description')
)

@push('head')
    @vite('resources/css/shop.css')
@endpush

@section('content')
<section class="shop-page">
    <div class="shop-hero">
        <div class="site-container">
            <span class="shop-kicker">{{ __('catalog.public.kicker') }}</span>
            <h1>
                {{ $selectedCategory?->name ?? __('catalog.public.shop_title') }}
            </h1>
            <p>
                {{ $selectedCategory?->description
                    ?: __('catalog.public.shop_description') }}
            </p>
        </div>
    </div>

    <div class="site-container shop-layout">
        <aside class="shop-categories">
            <h2>{{ __('catalog.public.categories') }}</h2>

            <a
                class="{{ ! $selectedCategory ? 'is-active' : '' }}"
                href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}"
            >
                {{ __('catalog.public.all_products') }}
            </a>

            @foreach ($categoryTree as $row)
                @php
                    $category = $row['category'];
                    $depth = $row['depth'];
                    $categoryTranslation =
                        $category->publicTranslation(app()->getLocale());
                    $categoryUrl = $category->publicUrlFrom(
                        $categories,
                        app()->getLocale()
                    );
                @endphp

                @if ($categoryTranslation && $categoryUrl)
                    <a
                        class="{{ $selectedCategory?->product_category_id === $category->id ? 'is-active' : '' }}"
                        href="{{ $categoryUrl }}"
                        style="padding-left: {{ 10 + min($depth, 6) * 14 }}px"
                    >
                        @if ($depth > 0)
                            <span aria-hidden="true">↳&nbsp;</span>
                        @endif
                        {{ $categoryTranslation->name }}
                    </a>
                @endif
            @endforeach
        </aside>

        <main class="shop-results">
            @if ($selectedCategory && $categoryBreadcrumbs->isNotEmpty())
                <nav class="shop-breadcrumbs" aria-label="Breadcrumb">
                    <a href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                        {{ __('catalog.public.shop_title') }}
                    </a>

                    @foreach ($categoryBreadcrumbs as $breadcrumbCategory)
                        @php
                            $breadcrumbTranslation =
                                $breadcrumbCategory->publicTranslation(
                                    app()->getLocale()
                                );
                            $breadcrumbUrl = $breadcrumbCategory->publicUrlFrom(
                                $categories,
                                app()->getLocale()
                            );
                        @endphp

                        @if ($breadcrumbTranslation)
                            <span>›</span>

                            @if ($loop->last || ! $breadcrumbUrl)
                                <span>{{ $breadcrumbTranslation->name }}</span>
                            @else
                                <a href="{{ $breadcrumbUrl }}">
                                    {{ $breadcrumbTranslation->name }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </nav>
            @endif

            <div class="shop-results-heading">
                <div>
                    <span>{{ __('catalog.public.catalog') }}</span>
                    <h2>
                        {{ $selectedCategory?->name ?? __('catalog.public.all_products') }}
                    </h2>
                </div>

                <strong>
                    {{ $products->total() }}
                    {{ __('catalog.public.products_count') }}
                </strong>
            </div>

            <div class="shop-product-grid">
                @forelse ($products as $product)
                    @php
                        $translation =
                            $product->publicTranslation(app()->getLocale());
                        $primary = $product->primaryMedia();
                        $variants = $product->activeVariants;
                        $minPrice = $variants->min(
                            fn ($variant) => (float) $variant->price_gross
                        );
                        $currency =
                            $variants->first()?->currency ?? 'PLN';
                        $hasStock = $variants->contains(
                            fn ($variant) => $variant->inStock()
                        );
                    @endphp

                    @if ($translation)
                        <article class="shop-product-card">
                            <a
                                class="shop-product-image"
                                href="{{ route('shop.show', [
                                    'locale' => app()->getLocale(),
                                    'slug' => $translation->slug,
                                ]) }}"
                            >
                                @if ($primary)
                                    <img
                                        src="{{ $primary->url() }}"
                                        alt="{{ $primary->alt_text ?: $translation->name }}"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="shop-image-placeholder">3D</div>
                                @endif

                                @if ($product->is_featured)
                                    <span class="shop-featured">
                                        {{ __('catalog.public.featured') }}
                                    </span>
                                @endif
                            </a>

                            <div class="shop-product-card-body">
                                <div class="shop-product-category">
                                    {{ $product->category?->publicTranslation(app()->getLocale())?->name }}
                                </div>

                                <h3>
                                    <a href="{{ route('shop.show', [
                                        'locale' => app()->getLocale(),
                                        'slug' => $translation->slug,
                                    ]) }}">
                                        {{ $translation->name }}
                                    </a>
                                </h3>

                                @if ($translation->short_description)
                                    <p>
                                        {{ Str::limit(
                                            $translation->short_description,
                                            125
                                        ) }}
                                    </p>
                                @endif

                                <div class="shop-product-card-footer">
                                    <div class="shop-price">
                                        @if ($variants->count() > 1)
                                            <small>
                                                {{ __('catalog.public.from') }}
                                            </small>
                                        @endif

                                        <strong>{{ number_format($minPrice, 2, ',', ' ') }} {{ $currency }}</strong>
                                    </div>

                                    <span class="shop-stock {{ $hasStock ? 'is-in' : 'is-out' }}">
                                        {{ $hasStock
                                            ? __('catalog.public.in_stock')
                                            : __('catalog.public.out_of_stock') }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    @endif
                @empty
                    <div class="shop-empty">
                        <strong>{{ __('catalog.public.empty_title') }}</strong>
                        <p>{{ __('catalog.public.empty_description') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($products->hasPages())
                <div class="shop-pagination">
                    {{ $products->links() }}
                </div>
            @endif

            @if ($selectedCategory?->content_html)
                <section class="shop-category-content">
                    {!! $selectedCategory->content_html !!}
                </section>
            @endif
        </main>
    </div>
</section>
@endsection
