@extends('admin.layout')

@section('title', $order->number . ' — ' . __('cart.admin.orders'))
@section('page_heading', $order->number)

@section('content')
<section class="admin-orders-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('cart.admin.order_details') }}</span>
            <h1>{{ $order->number }}</h1>
            <p>{{ $order->placed_at?->format('d.m.Y H:i') }} · {{ $order->customer_email }}</p>
        </div>

        <div class="catalog-heading-actions">
            @foreach ($order->salesDocuments as $document)
                <a
                    class="cms-secondary-button"
                    target="_blank"
                    href="{{ route('admin.orders.documents.show', [
                        'order' => $order,
                        'document' => $document
                    ]) }}"
                >
                    {{ __('checkout71.admin.print_document') }}
                </a>
            @endforeach

            <a class="cms-secondary-button" href="{{ route('admin.orders.index') }}">
                ← {{ __('cart.admin.back') }}
            </a>
        </div>
    </div>

    <div class="admin-order-grid">
        <div class="admin-order-main">
            <section class="cms-panel">
                <h2>{{ __('cart.admin.items') }}</h2>

                <div class="admin-order-items">
                    @foreach ($order->items as $item)
                        <div class="admin-order-item">
                            <div>
                                <strong>{{ $item->product_name_snapshot }}</strong>
                                <span>{{ $item->variant_name_snapshot ?: '—' }} · SKU {{ $item->sku_snapshot }}</span>
                            </div>

                            <div>
                                <span>{{ $item->quantity }} × {{ number_format((float) $item->unit_price_gross, 2, ',', ' ') }} {{ $item->currency }}</span>
                                <strong>{{ number_format((float) $item->line_total_gross, 2, ',', ' ') }} {{ $item->currency }}</strong>
                            </div>
                        </div>
                    @endforeach

                    <div class="admin-order-item admin-order-shipping-line">
                        <div>
                            <strong>{{ $order->shipping_name_snapshot ?: __('cart.summary.shipping') }}</strong>
                            @if ($order->shipping_point)
                                <span>{{ $order->shipping_point }}</span>
                            @endif
                        </div>
                        <div>
                            <strong>{{ number_format((float) $order->shipping_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cms-panel">
                <h2>{{ __('cart.admin.customer_data') }}</h2>

                <div class="admin-order-data-grid">
                    <div>
                        <span>{{ __('cart.admin.customer') }}</span>
                        <strong>{{ $order->customerName() }}</strong>
                        <small>{{ $order->customer_email }}</small>
                        @if ($order->customer_phone)<small>{{ $order->customer_phone }}</small>@endif
                    </div>

                    <div>
                        <span>{{ __('cart.admin.billing_address') }}</span>
                        <strong>{{ $order->billing_company ?: $order->customerName() }}</strong>
                        @if ($order->billing_tax_id)<small>NIP: {{ $order->billing_tax_id }}</small>@endif
                        <small>{{ $order->billing_address_line1 }}</small>
                        <small>{{ $order->billing_postal_code }} {{ $order->billing_city }}, {{ $order->billing_country_code }}</small>
                    </div>

                    <div>
                        <span>{{ __('cart.admin.shipping_address') }}</span>
                        <strong>{{ $order->shipping_first_name }} {{ $order->shipping_last_name }}</strong>
                        @if ($order->shipping_company)<small>{{ $order->shipping_company }}</small>@endif
                        <small>{{ $order->shipping_address_line1 }}</small>
                        <small>{{ $order->shipping_postal_code }} {{ $order->shipping_city }}, {{ $order->shipping_country_code }}</small>
                        @if ($order->shipping_point)<small>{{ __('checkout71.admin.point') }}: {{ $order->shipping_point }}</small>@endif

                        @if ($order->shipping_weight_grams)
                            <small>
                                {{ __('shipping.admin_order.weight') }}:
                                {{ number_format(
                                    $order->shipping_weight_grams / 1000,
                                    3,
                                    ',',
                                    ' '
                                ) }} kg
                            </small>
                        @endif
                    </div>
                </div>
            </section>
        </div>

        <aside class="admin-order-sidebar">
            <section class="cms-panel">
                <h2>{{ __('checkout71.admin.payment') }}</h2>

                <div class="admin-payment-box">
                    <span class="payment-status payment-status-{{ $order->payment_status->value }}">
                        {{ __('checkout71.payment_statuses.' . $order->payment_status->value) }}
                    </span>
                    <strong>{{ __('checkout71.payment_methods.' . $order->payment_method) }}</strong>

                    @if ($order->paid_at)
                        <small>{{ __('checkout71.admin.paid_at') }}: {{ $order->paid_at->format('d.m.Y H:i') }}</small>
                    @endif

                    @if ($order->payment_external_id)
                        <small>PayNow ID: {{ $order->payment_external_id }}</small>
                    @endif
                </div>

                @if ($order->payment_method === 'bank_transfer')
                    <form method="post" action="{{ route('admin.orders.payment.update', $order) }}">
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="payment_action"
                            value="{{ $order->isPaid() ? 'unpaid' : 'paid' }}"
                        >

                        <button class="cms-secondary-button" type="submit">
                            {{ $order->isPaid()
                                ? __('checkout71.admin.mark_unpaid')
                                : __('checkout71.admin.mark_paid') }}
                        </button>
                    </form>
                @endif
            </section>

            <section class="cms-panel">
                <h2>{{ __('cart.admin.status') }}</h2>

                <div class="admin-current-status">
                    <span class="order-status order-status-{{ $order->status->value }}">
                        {{ __('cart.statuses.' . $order->status->value) }}
                    </span>
                </div>

                @php $allowed = $order->status->allowedTransitions(); @endphp

                @if ($allowed !== [])
                    <form method="post" action="{{ route('admin.orders.status.update', $order) }}">
                        @csrf
                        @method('PATCH')

                        <div class="cms-field">
                            <label>{{ __('cart.admin.change_status') }}</label>
                            <select name="status" required>
                                @foreach ($allowed as $status)
                                    <option value="{{ $status->value }}">
                                        {{ __('cart.statuses.' . $status->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button class="cms-primary-button" type="submit">
                            {{ __('cart.admin.save_status') }}
                        </button>
                    </form>
                @else
                    <p class="admin-order-terminal">{{ __('cart.admin.terminal_status') }}</p>
                @endif
            </section>

            <section class="cms-panel">
                <h2>{{ __('cart.admin.totals') }}</h2>

                <div class="admin-order-total-row">
                    <span>{{ __('cart.summary.products') }}</span>
                    <strong>{{ number_format((float) $order->subtotal_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                </div>

                <div class="admin-order-total-row">
                    <span>{{ $order->shipping_name_snapshot ?: __('cart.summary.shipping') }}</span>
                    <strong>{{ number_format((float) $order->shipping_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                </div>

                <div class="admin-order-grand-total">
                    <span>{{ __('cart.admin.total') }}</span>
                    <strong>{{ number_format((float) $order->total_gross, 2, ',', ' ') }} {{ $order->currency }}</strong>
                </div>


                @if ($order->shipping_tracking_number)
                    <div class="admin-payment-box">
                        <strong>{{ __('furgonetka.tracking.title') }}</strong>

                        @if ($order->shipping_carrier)
                            <small>{{ __('furgonetka.tracking.carrier') }}: {{ $order->shipping_carrier }}</small>
                        @endif

                        <small>{{ __('furgonetka.tracking.number') }}: {{ $order->shipping_tracking_number }}</small>

                        @if ($order->shipping_tracking_updated_at)
                            <small>
                                {{ __('furgonetka.tracking.updated_at') }}:
                                {{ $order->shipping_tracking_updated_at->format('Y-m-d H:i') }}
                            </small>
                        @endif

                        @if ($order->shipping_tracking_url)
                            <a
                                class="cms-secondary-button"
                                href="{{ $order->shipping_tracking_url }}"
                                target="_blank"
                                rel="noopener"
                            >
                                {{ __('furgonetka.tracking.open') }}
                            </a>
                        @endif
                    </div>
                @endif

                @if ($order->shipping_base_before_margin !== null)
                    <div class="admin-payment-box">
                        <strong>
                            {{ __('shipping.admin_order.snapshot') }}
                        </strong>

                        <small>
                            {{ __('shipping.admin_order.country') }}:
                            {{ $order->shipping_country_name_snapshot
                                ?: $order->shipping_country_code }}
                            ({{ $order->shipping_country_code }})
                        </small>

                        <small>
                            {{ __('shipping.admin_order.weight') }}:
                            {{ number_format(
                                ((int) $order->shipping_weight_grams) / 1000,
                                3,
                                ',',
                                ' '
                            ) }} kg
                        </small>

                        <small>
                            {{ __('shipping.admin_order.base_before_margin') }}:
                            {{ number_format(
                                (float) $order->shipping_base_before_margin,
                                2,
                                ',',
                                ' '
                            ) }} PLN
                        </small>

                        <small>
                            {{ __('shipping.admin_order.logistics_margin') }}:
                            {{ number_format(
                                (float) $order->shipping_logistics_margin_percent,
                                2,
                                ',',
                                ' '
                            ) }}%
                        </small>

                        <small>
                            {{ __('shipping.admin_order.base_after_margin') }}:
                            {{ number_format(
                                (float) $order->shipping_base_gross,
                                2,
                                ',',
                                ' '
                            ) }} PLN
                        </small>
                    </div>
                @endif

                @if (
                    $order->currency
                    !== $order->base_currency
                )
                    <div class="admin-order-total-row">
                        <span>{{ __('checkout71.admin.base_total') }}</span>
                        <strong>
                            {{ number_format(
                                (float) $order->total_base_gross,
                                2,
                                ',',
                                ' '
                            ) }}
                            {{ $order->base_currency }}
                        </strong>
                    </div>

                    <div class="admin-payment-box">
                        <strong>
                            {{ __('checkout71.admin.currency_snapshot') }}
                        </strong>

                        <small>
                            {{ __('checkout71.admin.exchange_rate') }}:
                            1 {{ $order->currency }}
                            =
                            {{ number_format(
                                (float) $order->exchange_rate,
                                8,
                                ',',
                                ' '
                            ) }}
                            {{ $order->base_currency }}
                        </small>

                        <small>
                            {{ __('checkout71.admin.conversion_margin') }}:
                            {{ number_format(
                                (float) $order->currency_markup_percent,
                                2,
                                ',',
                                ' '
                            ) }}%
                        </small>

                        @if ($order->exchange_rate_source)
                            <small>
                                {{ __('checkout71.admin.rate_source') }}:
                                {{ strtoupper($order->exchange_rate_source) }}
                                @if ($order->exchange_rate_effective_date)
                                    · {{ $order->exchange_rate_effective_date->format('Y-m-d') }}
                                @endif
                            </small>
                        @endif
                    </div>
                @endif

            </section>
        </aside>
    </div>
</section>
@endsection
