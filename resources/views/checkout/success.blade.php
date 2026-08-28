@extends('layouts.app')

@section('title', __('cart.success.title') . ' — ' . __('site.title'))

@push('head')
    @vite('resources/css/cart.css')
@endpush

@section('content')
<section class="cart-page">
    <div class="site-container cart-container">
        <div class="order-success">
            <div class="order-success-icon">✓</div>

            <span class="cart-kicker">{{ __('cart.success.kicker') }}</span>
            <h1>{{ __('cart.success.title') }}</h1>
            <p>{{ __('cart.success.description') }}</p>

            <div class="order-success-number">
                <span>{{ __('cart.success.number') }}</span>
                <strong>{{ $order->number }}</strong>
            </div>

            <div class="order-success-grid">
                <div>
                    <span>{{ __('cart.success.status') }}</span>
                    <strong>{{ __('cart.statuses.' . $order->status->value) }}</strong>
                </div>

                <div>
                    <span>{{ __('cart.success.email') }}</span>
                    <strong>{{ $order->customer_email }}</strong>
                </div>

                <div>
                    <span>{{ __('cart.success.total') }}</span>
                    <strong>
                        {{ number_format((float) $order->total_gross, 2, ',', ' ') }}
                        {{ $order->currency }}
                    </strong>
                </div>
            </div>

            <div class="order-success-items">
                @foreach ($order->items as $item)
                    <div>
                        <span>
                            {{ $item->product_name_snapshot }}
                            @if ($item->variant_name_snapshot)
                                — {{ $item->variant_name_snapshot }}
                            @endif
                            × {{ $item->quantity }}
                        </span>

                        <strong>
                            {{ number_format((float) $item->line_total_gross, 2, ',', ' ') }}
                            {{ $item->currency }}
                        </strong>
                    </div>
                @endforeach
            </div>

            <div class="order-success-actions">
                <a class="cart-primary-button" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                    {{ __('cart.success.back_to_shop') }}
                </a>

                @auth
                    <a class="cart-secondary-button" href="{{ route('account.orders.index', ['locale' => app()->getLocale()]) }}">
                        {{ __('cart.success.my_orders') }}
                    </a>
                @else
                    <a class="cart-secondary-button" href="{{ route('login', ['locale' => app()->getLocale()]) }}">
                        {{ __('cart.success.login') }}
                    </a>
                @endauth
            </div>

            <p class="order-success-step71">
                {{ __('cart.success.step71_note') }}
            </p>
        </div>
    </div>
</section>
@endsection
