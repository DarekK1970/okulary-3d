@extends('layouts.app')

@section('title', __('cart.cart.title') . ' — ' . __('site.title'))

@push('head')
    @vite('resources/css/cart.css')
@endpush

@section('content')
<section class="cart-page">
    <div class="site-container cart-container">
        <div class="cart-heading">
            <div>
                <span class="cart-kicker">{{ __('cart.cart.kicker') }}</span>
                <h1>{{ __('cart.cart.title') }}</h1>
                <p>{{ __('cart.cart.description') }}</p>
            </div>

            <a class="cart-back-link" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                ← {{ __('cart.cart.continue_shopping') }}
            </a>
        </div>

        @if (session('status'))
            <div class="cart-alert cart-alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="cart-alert cart-alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($items->isEmpty())
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <h2>{{ __('cart.cart.empty_title') }}</h2>
                <p>{{ __('cart.cart.empty_description') }}</p>
                <a class="cart-primary-button" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                    {{ __('cart.cart.go_to_shop') }}
                </a>
            </div>
        @else
            <div class="cart-grid">
                <div class="cart-items">
                    @foreach ($items as $item)
                        <article class="cart-item">
                            <div class="cart-item-image">
                                @if ($item['media'])
                                    <img
                                        src="{{ $item['media']->url() }}"
                                        alt="{{ $item['media']->alt_text ?: $item['translation']->name }}"
                                    >
                                @else
                                    <div class="cart-image-placeholder">3D</div>
                                @endif
                            </div>

                            <div class="cart-item-copy">
                                <span class="cart-item-sku">SKU: {{ $item['variant']->sku }}</span>

                                <h2>
                                    <a href="{{ route('shop.show', [
                                        'locale' => app()->getLocale(),
                                        'slug' => $item['translation']->slug
                                    ]) }}">
                                        {{ $item['translation']->name }}
                                    </a>
                                </h2>

                                @if ($item['variant']->name)
                                    <p>{{ $item['variant']->name }}</p>
                                @endif

                                <strong class="cart-unit-price">
                                    {{ $currencyService->formatCents(
                                        $item['unit_price_cents'],
                                        $item['currency']
                                    ) }}
                                </strong>
                            </div>

                            <form
                                class="cart-quantity-form"
                                method="post"
                                action="{{ route('cart.items.update', [
                                    'locale' => app()->getLocale(),
                                    'variant' => $item['variant']
                                ]) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <label>
                                    <span>{{ __('cart.cart.quantity') }}</span>
                                    <input
                                        type="number"
                                        name="quantity"
                                        min="0"
                                        max="{{ $item['variant']->track_stock ? $item['variant']->stock_quantity : 999 }}"
                                        value="{{ $item['quantity'] }}"
                                    >
                                </label>

                                <button type="submit">{{ __('cart.cart.update') }}</button>
                            </form>

                            <div class="cart-item-total">
                                <span>{{ __('cart.cart.line_total') }}</span>
                                <strong>
                                    {{ $currencyService->formatCents(
                                        $item['line_total_cents'],
                                        $item['currency']
                                    ) }}
                                </strong>

                                <form
                                    method="post"
                                    action="{{ route('cart.items.destroy', [
                                        'locale' => app()->getLocale(),
                                        'variant' => $item['variant']
                                    ]) }}"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="cart-remove-button" type="submit">
                                        {{ __('cart.cart.remove') }}
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach

                    <form
                        method="post"
                        action="{{ route('cart.clear', ['locale' => app()->getLocale()]) }}"
                        onsubmit="return confirm('{{ __('cart.cart.clear_confirm') }}')"
                    >
                        @csrf
                        @method('DELETE')
                        <button class="cart-clear-button" type="submit">
                            {{ __('cart.cart.clear') }}
                        </button>
                    </form>
                </div>

                <aside class="cart-summary">
                    <span class="cart-kicker">{{ __('cart.summary.kicker') }}</span>
                    <h2>{{ __('cart.summary.title') }}</h2>

                    <div class="cart-summary-row">
                        <span>{{ __('cart.summary.products') }}</span>
                        <strong>
                            {{ $currencyService->formatCents(
                                $subtotalCents,
                                $currency
                            ) }}
                        </strong>
                    </div>

                    <div class="cart-summary-row">
                        <span>{{ __('cart.summary.shipping') }}</span>
                        <strong>{{ __('checkout71.cart.choose_in_checkout') }}</strong>
                    </div>

                    <div class="cart-summary-total">
                        <span>{{ __('cart.summary.current_total') }}</span>
                        <strong>
                            {{ $currencyService->formatCents(
                                $subtotalCents,
                                $currency
                            ) }}
                        </strong>
                    </div>

                    <a
                        class="cart-primary-button"
                        href="{{ route('checkout.create', ['locale' => app()->getLocale()]) }}"
                    >
                        {{ __('cart.cart.checkout') }}
                    </a>

                    <p class="cart-summary-note">
                        {{ __('checkout71.cart.shipping_note') }}
                    </p>
                </aside>
            </div>
        @endif
    </div>
</section>
@endsection
