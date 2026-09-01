@extends('layouts.app')

@section('title', __('cart.checkout.title') . ' — ' . __('site.title'))

@push('head')
    @vite([
        'resources/css/cart.css',
        'resources/js/cart.js'
    ])

    @if ($furgonetkaMapEnabled)
        <script
            src="https://furgonetka.pl/js/dist/map/map.js"
            async
        ></script>
    @endif
@endpush

@section('content')
@php
    $selectedShipping = old(
        'shipping_method',
        array_key_first($shippingMethods)
    );

    $selectedPayment = old(
        'payment_method',
        array_key_first($paymentMethods)
    );
@endphp

<section class="cart-page">
    <div class="site-container cart-container">
        <div class="cart-heading">
            <div>
                <span class="cart-kicker">{{ __('cart.checkout.kicker') }}</span>
                <h1>{{ __('cart.checkout.title') }}</h1>
                <p>{{ __('checkout71.checkout.description') }}</p>
            </div>

            <a
                class="cart-back-link"
                href="{{ route('cart.index', [
                    'locale' => app()->getLocale()
                ]) }}"
            >
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
            action="{{ route('checkout.store', [
                'locale' => app()->getLocale()
            ]) }}"
            data-checkout-form
            data-subtotal-cents="{{ $subtotalCents }}"
            data-currency="{{ $currency }}"
            data-locale="{{ app()->getLocale() }}"
            data-shipping-options-url="{{ route(
                'checkout.shipping-options',
                ['locale' => app()->getLocale()]
            ) }}"
            data-loading-label="{{ __('shipping.checkout.loading') }}"
            data-no-methods-label="{{ __('shipping.checkout.no_methods') }}"
            data-error-label="{{ __('shipping.checkout.quote_error') }}"
            data-furgonetka-map-enabled="{{ $furgonetkaMapEnabled ? '1' : '0' }}"
            data-furgonetka-map-api-key="{{ $furgonetkaMapEnabled ? $furgonetkaMapApiKey : '' }}"
            data-furgonetka-map-not-ready="{{ __('furgonetka.map.not_ready') }}"
            data-furgonetka-map-selected="{{ __('furgonetka.map.selected') }}"
        >
            @csrf

            <div class="checkout-main">
                <section class="checkout-panel">
                    <span class="checkout-step">01</span>
                    <h2>{{ __('cart.checkout.contact') }}</h2>

                    <div class="checkout-fields two-columns">
                        <label>
                            <span>{{ __('cart.checkout.email') }} *</span>
                            <input type="email" name="customer_email" value="{{ old('customer_email', $user?->email) }}" required>
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.phone') }}</span>
                            <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="40">
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.first_name') }} *</span>
                            <input type="text" name="customer_first_name" value="{{ old('customer_first_name', $user?->name) }}" required>
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.last_name') }} *</span>
                            <input type="text" name="customer_last_name" value="{{ old('customer_last_name') }}" required>
                        </label>
                    </div>
                </section>

                <section class="checkout-panel">
                    <span class="checkout-step">02</span>
                    <h2>{{ __('cart.checkout.billing') }}</h2>

                    <div class="checkout-fields two-columns">
                        <label>
                            <span>{{ __('cart.checkout.company') }}</span>
                            <input type="text" name="billing_company" value="{{ old('billing_company') }}">
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.tax_id') }}</span>
                            <input type="text" name="billing_tax_id" value="{{ old('billing_tax_id') }}">
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.address1') }} *</span>
                            <input type="text" name="billing_address_line1" value="{{ old('billing_address_line1') }}" required>
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.address2') }}</span>
                            <input type="text" name="billing_address_line2" value="{{ old('billing_address_line2') }}">
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.postal_code') }} *</span>
                            <input type="text" name="billing_postal_code" value="{{ old('billing_postal_code') }}" required>
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.city') }} *</span>
                            <input type="text" name="billing_city" value="{{ old('billing_city') }}" required>
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.country') }} *</span>
                            <input
                                type="text"
                                name="billing_country_code"
                                value="{{ old(
                                    'billing_country_code',
                                    $selectedShippingCountry
                                ) }}"
                                maxlength="2"
                                required
                            >
                        </label>
                    </div>
                </section>

                <section class="checkout-panel">
                    <span class="checkout-step">03</span>
                    <h2>{{ __('checkout71.checkout.shipping_method') }}</h2>

                    <div class="checkout-country-block">
                        <label class="checkout-country-field">
                            <span>{{ __('shipping.checkout.country') }} *</span>

                            <select
                                name="shipping_country_code"
                                data-shipping-country
                                required
                            >
                                @foreach ($shippingCountries as $country)
                                    <option
                                        value="{{ $country['code'] }}"
                                        @selected(
                                            $selectedShippingCountry
                                            === $country['code']
                                        )
                                    >
                                        {{ $country['name'] }}
                                        ({{ $country['code'] }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <p class="checkout-weight-note">
                            {{ __('shipping.checkout.weight', [
                                'weight' => number_format(
                                    $shippingWeightGrams / 1000,
                                    3,
                                    ',',
                                    ' '
                                ),
                            ]) }}
                        </p>
                    </div>

                    <p
                        class="shipping-quote-status"
                        data-shipping-status
                        @if ($shippingMethods !== [])
                            hidden
                        @endif
                    >
                        {{ $shippingMethods === []
                            ? __('shipping.checkout.no_methods')
                            : '' }}
                    </p>

                    <div class="checkout-methods" data-shipping-methods>
                        @foreach ($shippingMethods as $method)
                            <label class="checkout-method">
                                <input
                                    type="radio"
                                    name="shipping_method"
                                    value="{{ $method['key'] }}"
                                    data-shipping-method
                                    data-price-cents="{{ $method['price_cents'] }}"
                                    data-requires-point="{{ $method['requires_point'] ? '1' : '0' }}"
                                    @checked($selectedShipping === $method['key'])
                                    required
                                >

                                <span>
                                    <strong>{{ $method['name'] }}</strong>
                                    <small>
                                        {{ $currencyService->formatCents(
                                            $method['price_cents'],
                                            $currency
                                        ) }}
                                    </small>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="checkout-point-field" data-shipping-point-wrap>
                        <label>
                            <span>{{ __('checkout71.checkout.shipping_point') }}</span>

                            <input
                                type="text"
                                name="shipping_point"
                                value="{{ old('shipping_point') }}"
                                placeholder="{{ __('checkout71.checkout.shipping_point_placeholder') }}"
                                data-shipping-point-code
                                @readonly($furgonetkaMapEnabled)
                            >

                            <input type="hidden" name="shipping_point_name"
                                   value="{{ old('shipping_point_name') }}"
                                   data-shipping-point-name>
                            <input type="hidden" name="shipping_point_type"
                                   value="{{ old('shipping_point_type') }}"
                                   data-shipping-point-type>
                            <input type="hidden" name="shipping_point_original_id"
                                   value="{{ old('shipping_point_original_id') }}"
                                   data-shipping-point-original-id>
                            <input type="hidden" name="shipping_point_country_code"
                                   value="{{ old('shipping_point_country_code') }}"
                                   data-shipping-point-country>

                            @if ($furgonetkaMapEnabled)
                                <button
                                    class="cart-secondary-button"
                                    type="button"
                                    data-furgonetka-map-button
                                >
                                    {{ __('furgonetka.map.choose') }}
                                </button>

                                <small data-furgonetka-map-summary>
                                    {{ old('shipping_point_name') }}
                                </small>
                            @endif
                        </label>
                    </div>

                    <div class="checkout-address-separator">
                        <strong>{{ __('cart.checkout.shipping') }}</strong>
                    </div>

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
                            <input type="text" name="shipping_first_name" value="{{ old('shipping_first_name') }}">
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.last_name') }}</span>
                            <input type="text" name="shipping_last_name" value="{{ old('shipping_last_name') }}">
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.company') }}</span>
                            <input type="text" name="shipping_company" value="{{ old('shipping_company') }}">
                        </label>

                        <label class="full-width">
                            <span>{{ __('cart.checkout.address1') }}</span>
                            <input type="text" name="shipping_address_line1" value="{{ old('shipping_address_line1') }}">
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.postal_code') }}</span>
                            <input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code') }}">
                        </label>

                        <label>
                            <span>{{ __('cart.checkout.city') }}</span>
                            <input type="text" name="shipping_city" value="{{ old('shipping_city') }}">
                        </label>
                    </div>
                </section>

                <section class="checkout-panel">
                    <span class="checkout-step">04</span>
                    <h2>{{ __('checkout71.checkout.payment_method') }}</h2>

                    <div class="checkout-methods">
                        @forelse ($paymentMethods as $method)
                            <label class="checkout-method">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $method['key'] }}"
                                    @checked($selectedPayment === $method['key'])
                                    required
                                >

                                <span>
                                    <strong>{{ $method['name'] }}</strong>
                                    <small>
                                        {{ $method['key'] === 'paynow'
                                            ? __('checkout71.checkout.paynow_hint')
                                            : __('checkout71.checkout.bank_hint') }}
                                    </small>
                                </span>
                            </label>
                        @empty
                            <div class="cart-alert cart-alert-error">
                                {{ __('checkout71.validation.no_payment_methods') }}
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="checkout-panel">
                    <span class="checkout-step">05</span>
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
                                <span>{{ $item['variant']->name ?: $item['variant']->sku }} × {{ $item['quantity'] }}</span>
                            </div>

                            <strong>
                                {{ $currencyService->formatCents(
                                    $item['line_total_cents'],
                                    $item['currency']
                                ) }}
                            </strong>
                        </div>
                    @endforeach
                </div>

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
                    <strong data-checkout-shipping>—</strong>
                </div>

                <div class="cart-summary-total">
                    <span>{{ __('cart.summary.current_total') }}</span>
                    <strong data-checkout-total>
                        {{ $currencyService->formatCents(
                            $subtotalCents,
                            $currency
                        ) }}
                    </strong>
                </div>

                <label class="checkout-checkbox checkout-terms">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    <span>{{ __('cart.checkout.accept_terms') }}</span>
                </label>

                <button
                    class="cart-primary-button"
                    type="submit"
                    data-place-order
                    @disabled(
                        $shippingMethods === []
                        || $paymentMethods === []
                    )
                >
                    {{ __('cart.checkout.place_order') }}
                </button>

                <p class="cart-summary-note">
                    {{ __('checkout71.checkout.final_note') }}
                </p>
            </aside>
        </form>
    </div>
</section>
@endsection
