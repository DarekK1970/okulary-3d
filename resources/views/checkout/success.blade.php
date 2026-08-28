@extends('layouts.app')

@section('title', __('cart.success.title') . ' — ' . __('site.title'))

@push('head')
    @vite('resources/css/cart.css')
@endpush

@section('content')
<section class="cart-page">
    <div class="site-container cart-container">
        @if ($errors->any())
            <div class="cart-alert cart-alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="order-success">
            <div class="order-success-icon">✓</div>

            <span class="cart-kicker">{{ __('cart.success.kicker') }}</span>
            <h1>{{ __('cart.success.title') }}</h1>
            <p>{{ __('checkout71.success.description') }}</p>

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
                    <span>{{ __('checkout71.success.payment') }}</span>
                    <strong>{{ __('checkout71.payment_statuses.' . $order->payment_status->value) }}</strong>
                </div>

                <div>
                    <span>{{ __('cart.success.total') }}</span>
                    <strong>{{ number_format((float) $order->total_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
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

                        <strong>{{ number_format((float) $item->line_total_gross, 2, ',', ' ') }} {{ $item->currency }}</strong>
                    </div>
                @endforeach

                <div>
                    <span>{{ $order->shipping_name_snapshot }}</span>
                    <strong>{{ number_format((float) $order->shipping_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                </div>
            </div>

            @if ($order->payment_method === 'bank_transfer' && ! $order->isPaid())
                <div class="bank-transfer-box">
                    <strong>{{ __('checkout71.success.bank_transfer_title') }}</strong>
                    <p>{{ __('checkout71.success.bank_transfer_description') }}</p>

                    <dl>
                        <div>
                            <dt>{{ __('checkout71.success.recipient') }}</dt>
                            <dd>{{ $bankTransfer['recipient'] ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('checkout71.success.bank') }}</dt>
                            <dd>{{ $bankTransfer['bank_name'] ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('checkout71.success.account') }}</dt>
                            <dd>{{ $bankTransfer['account'] ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('checkout71.success.transfer_title') }}</dt>
                            <dd>{{ $order->number }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            @if ($order->payment_method === 'paynow' && ! $order->isPaid())
                <form
                    class="paynow-retry"
                    method="post"
                    action="{{ route('payment.paynow.retry', [
                        'locale' => app()->getLocale(),
                        'order' => $order->public_token
                    ]) }}"
                >
                    @csrf
                    <button class="cart-primary-button" type="submit">
                        {{ __('checkout71.success.retry_paynow') }}
                    </button>
                </form>
            @endif

            <div class="order-success-actions">
                <a class="cart-primary-button" href="{{ route('shop.index', ['locale' => app()->getLocale()]) }}">
                    {{ __('cart.success.back_to_shop') }}
                </a>

                @if ($document = $order->salesDocuments->first())
                    <a
                        class="cart-secondary-button"
                        target="_blank"
                        href="{{ route('order.document', [
                            'locale' => app()->getLocale(),
                            'order' => $order->public_token,
                            'document' => $document
                        ]) }}"
                    >
                        {{ __('checkout71.success.print_document') }}
                    </a>
                @endif

                @auth
                    <a class="cart-secondary-button" href="{{ route('account.orders.index', ['locale' => app()->getLocale()]) }}">
                        {{ __('cart.success.my_orders') }}
                    </a>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection
