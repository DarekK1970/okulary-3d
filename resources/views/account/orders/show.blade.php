@extends('layouts.app')

@section('title', $order->number . ' — ' . __('cart.account.title'))

@push('head')
    @vite('resources/css/cart.css')
@endpush

@section('content')
<section class="cart-page">
    <div class="site-container cart-container">
        <div class="cart-heading">
            <div>
                <span class="cart-kicker">{{ __('cart.account.order_details') }}</span>
                <h1>{{ $order->number }}</h1>
                <p>
                    {{ $order->placed_at?->format('d.m.Y H:i') }}
                    · {{ __('cart.statuses.' . $order->status->value) }}
                </p>
            </div>

            <a class="cart-back-link" href="{{ route('account.orders.index', ['locale' => app()->getLocale()]) }}">
                ← {{ __('cart.account.back_to_orders') }}
            </a>
        </div>

        <div class="order-detail-layout">
            <section class="checkout-panel">
                <h2>{{ __('cart.account.ordered_items') }}</h2>

                <div class="order-detail-items">
                    @foreach ($order->items as $item)
                        <div class="order-detail-item">
                            <div>
                                <strong>{{ $item->product_name_snapshot }}</strong>
                                <span>
                                    {{ $item->variant_name_snapshot ?: $item->sku_snapshot }}
                                    · SKU {{ $item->sku_snapshot }}
                                </span>
                            </div>

                            <div>
                                <span>{{ $item->quantity }} × {{ number_format((float) $item->unit_price_gross, 2, ',', ' ') }} {{ $item->currency }}</span>
                                <strong>{{ number_format((float) $item->line_total_gross, 2, ',', ' ') }} {{ $item->currency }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="cart-summary">
                <h2>{{ __('cart.account.summary') }}</h2>

                <div class="cart-summary-row">
                    <span>{{ __('cart.summary.products') }}</span>
                    <strong>{{ number_format((float) $order->subtotal_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                </div>

                <div class="cart-summary-row">
                    <span>{{ __('cart.summary.shipping') }}</span>
                    <strong>{{ number_format((float) $order->shipping_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                </div>

                <div class="cart-summary-total">
                    <span>{{ __('cart.account.total') }}</span>
                    <strong>{{ number_format((float) $order->total_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                </div>

                <div class="order-address">
                    <span>{{ __('cart.account.shipping_address') }}</span>
                    <strong>{{ $order->shipping_first_name }} {{ $order->shipping_last_name }}</strong>
                    @if ($order->shipping_company)
                        <span>{{ $order->shipping_company }}</span>
                    @endif
                    <span>{{ $order->shipping_address_line1 }}</span>
                    @if ($order->shipping_address_line2)
                        <span>{{ $order->shipping_address_line2 }}</span>
                    @endif
                    <span>{{ $order->shipping_postal_code }} {{ $order->shipping_city }}</span>
                    <span>{{ $order->shipping_country_code }}</span>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
