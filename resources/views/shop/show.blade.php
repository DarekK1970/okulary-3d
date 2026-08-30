@extends('layouts.app')

@section('title', ($translation->seo_title ?: $translation->name) . ' — ' . __('site.title'))
@section('meta_description', $translation->seo_description ?: ($translation->short_description ?: __('catalog.public.shop_description')))

@push('head')
    @vite([
        'resources/css/shop.css',
        'resources/css/cart.css'
    ])
@endpush

@section('content')
<section class="product-page">
    <div class="site-container">
        <nav class="shop-breadcrumbs" aria-label="Breadcrumb">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">
                {{ __('catalog.public.home') }}
            </a>

            <span>›</span>

            <a href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                {{ __('catalog.public.shop_title') }}
            </a>

            @foreach ($categoryBreadcrumbs as $breadcrumbCategory)
                @php
                    $breadcrumbTranslation =
                        $breadcrumbCategory->publicTranslation(
                            app()->getLocale()
                        );
                @endphp

                @if ($breadcrumbTranslation)
                    @php
                        $breadcrumbUrl = $breadcrumbCategory->publicUrlFrom(
                            $categoryUrlCategories,
                            app()->getLocale()
                        );
                    @endphp
                    <span>›</span>
                    @if ($breadcrumbUrl)
                        <a href="{{ $breadcrumbUrl }}">
                            {{ $breadcrumbTranslation->name }}
                        </a>
                    @else
                        <span>{{ $breadcrumbTranslation->name }}</span>
                    @endif
                @endif
            @endforeach

            <span>›</span>
            <span>{{ $translation->name }}</span>
        </nav>

        @if ($errors->any())
            <div class="cart-alert cart-alert-error product-cart-alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="product-detail-grid">
            <div class="product-gallery">
                @php $primary = $product->primaryMedia(); @endphp

                <div class="product-main-image">
                    @if ($primary)
                        <img
                            src="{{ $primary->url() }}"
                            alt="{{ $primary->alt_text ?: $translation->name }}"
                            data-product-main-image
                        >
                    @else
                        <div class="shop-image-placeholder">3D</div>
                    @endif
                </div>

                @if ($product->media->count() > 1)
                    <div class="product-thumbnails">
                        @foreach ($product->media as $media)
                            <button
                                type="button"
                                data-product-thumbnail="{{ $media->url() }}"
                            >
                                <img
                                    src="{{ $media->url() }}"
                                    alt="{{ $media->alt_text ?: $translation->name }}"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="product-summary">
                <div class="product-category-label">
                    {{ $product->category?->publicTranslation(app()->getLocale())?->name }}
                </div>

                <h1>{{ $translation->name }}</h1>

                @if ($product->brand)
                    <div class="product-brand">
                        {{ __('catalog.public.brand') }}:
                        <strong>{{ $product->brand }}</strong>
                    </div>
                @endif

                @if ($translation->short_description)
                    <p class="product-short-description">
                        {{ $translation->short_description }}
                    </p>
                @endif

                @php
                    $firstAvailableId = $product->activeVariants
                        ->first(
                            fn ($variant) => $variant->inStock()
                        )?->id;
                @endphp

                <form
                    class="product-purchase-form"
                    method="post"
                    action="{{ route('cart.items.store', [
                        'locale' => app()->getLocale(),
                    ]) }}"
                >
                    @csrf

                    <div class="product-variants">
                        <h2>{{ __('catalog.public.variants') }}</h2>

                        @foreach ($product->activeVariants as $variant)
                            <label class="product-variant-option {{ $variant->inStock() ? '' : 'is-out' }}">
                                <input
                                    type="radio"
                                    name="variant_id"
                                    value="{{ $variant->id }}"
                                    @checked($firstAvailableId === $variant->id)
                                    @disabled(! $variant->inStock())
                                    required
                                >

                                <span class="product-variant-copy">
                                    <strong>
                                        {{ $variant->name ?: $variant->sku }}
                                    </strong>
                                    <small>SKU: {{ $variant->sku }}</small>
                                </span>

                                <span class="product-variant-price">
                                    {{ number_format(
                                        (float) $variant->price_gross,
                                        2,
                                        ',',
                                        ' '
                                    ) }}
                                    {{ $variant->currency }}
                                </span>

                                <span class="product-variant-stock">
                                    {{ $variant->inStock()
                                        ? __('catalog.public.in_stock')
                                        : __('catalog.public.out_of_stock') }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="product-cart-actions">
                        <label class="product-quantity">
                            <span>{{ __('cart.cart.quantity') }}</span>
                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                max="999"
                                value="1"
                                required
                            >
                        </label>

                        <button
                            class="product-add-cart-button"
                            type="submit"
                            @disabled(! $firstAvailableId)
                        >
                            {{ $firstAvailableId
                                ? __('cart.product.add_to_cart')
                                : __('catalog.public.out_of_stock') }}
                        </button>
                    </div>
                </form>

                @if ($product->translations->count() > 1)
                    <div class="product-language-links">
                        <span>
                            {{ __('catalog.public.other_languages') }}
                        </span>

                        @foreach ($product->translations as $alternate)
                            @if ($alternate->isPubliclyReady())
                                <a
                                    class="{{ $alternate->locale === $translation->locale ? 'is-active' : '' }}"
                                    href="{{ route('shop.show', [
                                        'locale' => $alternate->locale,
                                        'slug' => $alternate->slug,
                                    ]) }}"
                                >
                                    {{ strtoupper($alternate->locale) }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="product-description">
            {!! $translation->description_html !!}
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-product-thumbnail]').forEach((button) => {
    button.addEventListener('click', () => {
        const image = document.querySelector('[data-product-main-image]');

        if (image) {
            image.src = button.dataset.productThumbnail;
        }
    });
});
</script>
@endpush
