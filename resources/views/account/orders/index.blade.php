@extends('layouts.app')

@section('title', __('cart.account.title') . ' — ' . __('site.title'))

@push('head')
    @vite('resources/css/cart.css')
@endpush

@section('content')
<section class="cart-page">
    <div class="site-container cart-container">
        <div class="cart-heading">
            <div>
                <span class="cart-kicker">{{ __('cart.account.kicker') }}</span>
                <h1>{{ __('cart.account.title') }}</h1>
                <p>{{ __('cart.account.description') }}</p>
            </div>

            <a class="cart-back-link" href="{{ route('account', ['locale' => app()->getLocale()]) }}">
                ← {{ __('cart.account.back_to_account') }}
            </a>
        </div>

        <div class="account-orders-list">
            @forelse ($orders as $order)
                <a
                    class="account-order-card"
                    href="{{ route('account.orders.show', [
                        'locale' => app()->getLocale(),
                        'order' => $order
                    ]) }}"
                >
                    <div>
                        <span>{{ __('cart.account.order_number') }}</span>
                        <strong>{{ $order->number }}</strong>
                    </div>

                    <div>
                        <span>{{ __('cart.account.date') }}</span>
                        <strong>{{ $order->placed_at?->format('d.m.Y H:i') }}</strong>
                    </div>

                    <div>
                        <span>{{ __('cart.account.status') }}</span>
                        <strong>{{ __('cart.statuses.' . $order->status->value) }}</strong>
                    </div>

                    <div>
                        <span>{{ __('cart.account.items') }}</span>
                        <strong>{{ $order->items_count }}</strong>
                    </div>

                    <div>
                        <span>{{ __('cart.account.total') }}</span>
                        <strong>
                            {{ number_format((float) $order->total_gross, 2, ',', ' ') }}
                            {{ $order->currency }}
                        </strong>
                    </div>

                    <span class="account-order-arrow">→</span>
                </a>
            @empty
                <div class="cart-empty">
                    <h2>{{ __('cart.account.empty_title') }}</h2>
                    <p>{{ __('cart.account.empty_description') }}</p>
                    <a class="cart-primary-button" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                        {{ __('cart.cart.go_to_shop') }}
                    </a>
                </div>
            @endforelse
        </div>

        @if ($orders->hasPages())
            <div class="shop-pagination">{{ $orders->links() }}</div>
        @endif
    </div>
</section>
@endsection
