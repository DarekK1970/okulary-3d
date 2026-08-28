@extends('layouts.app')

@section('title', __('cart.checkout.title') . ' — ' . __('site.title'))

@push('head')
    @vite([
        'resources/css/cart.css',
        'resources/js/cart.js'
    ])
@endpush

@section('content')
<section class="cart-page">
    <div class="site-container cart-container">
        <div class="cart-heading">
            <div>
                <span class="cart-kicker">{{ __('cart.checkout.kicker') }}</span>
                <h1>{{ __('cart.checkout.title') }}</h1>
                <p>{{ __('cart.checkout.description') }}</p>
            </div>

            <a class="cart-back-link" href="{{ route('cart.index', ['locale' => app()->getLocale()]) }}">
                ← {{ __('cart.checkout.back_to_cart') }}
            </a>
        </div>

        @if ($errors->any())
            <div class="cart-alert cart-alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            class="checkout-grid"
            method="post"
            action="{{ route('checkout.store', ['locale' => app()->getLocale()]) }}"
        >
            @csrf

            <div class="checkout-main">
                <section class="checkout-panel">
                    <span class="checkout-step">01</span>
                    <h2>{{ __('cart.checkout.contact') }}</h2>

                    <div class="checkout-fields two-columns">
                        <label>
                            <span>{{ __('cart.checkout.email') }} *</span>
                            <input
                                type="email"
                                name="customer_email"
                                value="{{ old('customer_email', $user?->email) }}"
                                required
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.phone') }}</span>
                            <input
                                type="text"
                                name="customer_phone"
                                value="{{ old('customer_phone') }}"
                                maxlength="40"
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.first_name') }} *</span>
                            <input
                                type="text"
                                name="customer_first_name"
                                value="{{ old('customer_first_name', $user?->name) }}"
                                required
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.last_name') }} *</span>
                            <input
                                type="text"
                                name="customer_last_name"
                                value="{{ old('customer_last_name') }}"
                                required
                            >
                        </label>
                    </div>
                </section>

                <section class="checkout-panel">
                    <span class="checkout-step">02</span>
                    <h2>{{ __('cart.checkout.billing') }}</h2>

                    <div class="checkout-fields two-columns">
                        <label>
                            <span>{{ __('cart.checkout.company') }}</span>
                            <input
                                type="text"
                                name="billing_company"
                                value="{{ old('billing_company') }}"
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.tax_id') }}</span>
                            <input
                                type="text"
                                name="billing_tax_id"
                                value="{{ old('billing_tax_id') }}"
                            >
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.address1') }} *</span>
                            <input
                                type="text"
                                name="billing_address_line1"
                                value="{{ old('billing_address_line1') }}"
                                required
                            >
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.address2') }}</span>
                            <input
                                type="text"
                                name="billing_address_line2"
                                value="{{ old('billing_address_line2') }}"
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.postal_code') }} *</span>
                            <input
                                type="text"
                                name="billing_postal_code"
                                value="{{ old('billing_postal_code') }}"
                                required
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.city') }} *</span>
                            <input
                                type="text"
                                name="billing_city"
                                value="{{ old('billing_city') }}"
                                required
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.country') }} *</span>
                            <input
                                type="text"
                                name="billing_country_code"
                                value="{{ old('billing_country_code', 'PL') }}"
                                maxlength="2"
                                required
                            >
                        </label>
                    </div>
                </section>

                <section class="checkout-panel">
                    <span class="checkout-step">03</span>
                    <h2>{{ __('cart.checkout.shipping') }}</h2>

                    <input type="hidden" name="shipping_same_as_billing" value="0">

                    <label class="checkout-checkbox">
                        <input
                            type="checkbox"
                            name="shipping_same_as_billing"
                            value="1"
                            @checked(old('shipping_same_as_billing', '1') === '1')
                        >
                        <span>{{ __('cart.checkout.same_address') }}</span>
                    </label>

                    <div class="checkout-fields two-columns checkout-shipping-fields">
                        <label>
                            <span>{{ __('cart.checkout.first_name') }}</span>
                            <input
                                type="text"
                                name="shipping_first_name"
                                value="{{ old('shipping_first_name') }}"
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.last_name') }}</span>
                            <input
                                type="text"
                                name="shipping_last_name"
                                value="{{ old('shipping_last_name') }}"
                            >
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.company') }}</span>
                            <input
                                type="text"
                                name="shipping_company"
                                value="{{ old('shipping_company') }}"
                            >
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.address1') }}</span>
                            <input
                                type="text"
                                name="shipping_address_line1"
                                value="{{ old('shipping_address_line1') }}"
                            >
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.address2') }}</span>
                            <input
                                type="text"
                                name="shipping_address_line2"
                                value="{{ old('shipping_address_line2') }}"
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.postal_code') }}</span>
                            <input
                                type="text"
                                name="shipping_postal_code"
                                value="{{ old('shipping_postal_code') }}"
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.city') }}</span>
                            <input
                                type="text"
                                name="shipping_city"
                                value="{{ old('shipping_city') }}"
                            >
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.country') }}</span>
                            <input
                                type="text"
                                name="shipping_country_code"
                                value="{{ old('shipping_country_code', 'PL') }}"
                                maxlength="2"
                            >
                        </label>
                    </div>
                </section>

                <section class="checkout-panel">
                    <span class="checkout-step">04</span>
                    <h2>{{ __('cart.checkout.note') }}</h2>

                    <label class="checkout-textarea">
                        <textarea
                            name="customer_note"
                            rows="5"
                            maxlength="2000"
                            placeholder="{{ __('cart.checkout.note_placeholder') }}"
                        >{{ old('customer_note') }}</textarea>
                    </label>
                </section>
            </div>

            <aside class="cart-summary checkout-summary">
                <span class="cart-kicker">{{ __('cart.checkout.order_summary') }}</span>
                <h2>{{ __('cart.summary.title') }}</h2>

                <div class="checkout-summary-items">
                    @foreach ($items as $item)
                        <div class="checkout-summary-item">
                            <div>
                                <strong>{{ $item['translation']->name }}</strong>
                                <span>
                                    {{ $item['variant']->name ?: $item['variant']->sku }}
                                    × {{ $item['quantity'] }}
                                </span>
                            </div>

                            <strong>
                                {{ number_format($item['line_total_cents'] / 100, 2, ',', ' ') }}
                                {{ $item['currency'] }}
                            </strong>
                        </div>
                    @endforeach
                </div>

                <div class="cart-summary-row">
                    <span>{{ __('cart.summary.products') }}</span>
                    <strong>
                        {{ number_format($subtotalCents / 100, 2, ',', ' ') }}
                        {{ $currency }}
                    </strong>
                </div>

                <div class="cart-summary-row">
                    <span>{{ __('cart.summary.shipping') }}</span>
                    <strong>{{ __('cart.summary.step71') }}</strong>
                </div>

                <div class="cart-summary-total">
                    <span>{{ __('cart.summary.current_total') }}</span>
                    <strong>
                        {{ number_format($subtotalCents / 100, 2, ',', ' ') }}
                        {{ $currency }}
                    </strong>
                </div>

                <label class="checkout-checkbox checkout-terms">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    <span>{{ __('cart.checkout.accept_terms') }}</span>
                </label>

                <button class="cart-primary-button" type="submit">
                    {{ __('cart.checkout.place_order') }}
                </button>

                <p class="cart-summary-note">
                    {{ __('cart.checkout.step71_note') }}
                </p>
            </aside>
        </form>
    </div>
</section>
@endsection
