@extends('layouts.app')

@section('title', ($translation->seo_title ?: $translation->name) . ' — ' . __('site.title'))
@section('meta_description', $translation->seo_description ?: ($translation->short_description ?: __('catalog.public.shop_description')))

@push('head')
    @vite('resources/css/shop.css')

    <link rel="canonical" href="{{ route('shop.show', ['locale' => $translation->locale, 'slug' => $translation->slug]) }}">

    @foreach ($product->translations as $alternate)
        @if ($alternate->isPubliclyReady())
            <link
                rel="alternate"
                hreflang="{{ $alternate->locale }}"
                href="{{ route('shop.show', ['locale' => $alternate->locale, 'slug' => $alternate->slug]) }}"
            >
        @endif
    @endforeach
@endpush

@section('content')
<section class="product-page">
    <div class="site-container">
        <nav class="shop-breadcrumbs">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">{{ __('catalog.public.home') }}</a>
            <span>›</span>
            <a href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">{{ __('catalog.public.shop_title') }}</a>
            <span>›</span>
            <span>{{ $translation->name }}</span>
        </nav>

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
                            <button type="button" data-product-thumbnail="{{ $media->url() }}">
                                <img src="{{ $media->url() }}" alt="{{ $media->alt_text ?: $translation->name }}">
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
                    <p class="product-short-description">{{ $translation->short_description }}</p>
                @endif

                <div class="product-variants">
                    <h2>{{ __('catalog.public.variants') }}</h2>

                    @foreach ($product->activeVariants as $variant)
                        <label class="product-variant-option {{ $variant->inStock() ? '' : 'is-out' }}">
                            <input
                                type="radio"
                                name="display_variant"
                                value="{{ $variant->id }}"
                                @checked($loop->first)
                                @disabled(! $variant->inStock())
                            >

                            <span class="product-variant-copy">
                                <strong>{{ $variant->name ?: $variant->sku }}</strong>
                                <small>SKU: {{ $variant->sku }}</small>
                            </span>

                            <span class="product-variant-price">
                                {{ number_format((float) $variant->price_gross, 2, ',', ' ') }} {{ $variant->currency }}
                            </span>

                            <span class="product-variant-stock">
                                {{ $variant->inStock() ? __('catalog.public.in_stock') : __('catalog.public.out_of_stock') }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="product-cart-placeholder">
                    <button type="button" disabled>
                        {{ __('catalog.public.cart_step70') }}
                    </button>
                    <p>{{ __('catalog.public.cart_step70_note') }}</p>
                </div>

                @if ($product->translations->count() > 1)
                    <div class="product-language-links">
                        <span>{{ __('catalog.public.other_languages') }}</span>

                        @foreach ($product->translations as $alternate)
                            @if ($alternate->isPubliclyReady())
                                <a
                                    class="{{ $alternate->locale === $translation->locale ? 'is-active' : '' }}"
                                    href="{{ route('shop.show', ['locale' => $alternate->locale, 'slug' => $alternate->slug]) }}"
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
