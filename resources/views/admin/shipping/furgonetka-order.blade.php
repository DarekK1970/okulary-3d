@extends('admin.layout')

@section('title', 'Furgonetka — ' . $order->number)
@section('page_heading', 'Furgonetka — ' . $order->number)

@section('content')
<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('furgonetka.order.kicker') }}</span>
            <h1>{{ __('furgonetka.order.title') }}</h1>
            <p>{{ $order->number }} · {{ $order->shipping_country_code }} · {{ number_format(((int) $order->shipping_weight_grams) / 1000, 3, ',', ' ') }} kg</p>
        </div>

        <div class="catalog-heading-actions">
            <a
                class="cms-secondary-button"
                href="{{ route('admin.orders.show', $order) }}"
            >
                ← {{ __('furgonetka.order.back') }}
            </a>
        </div>
    </div>

    @if (! $settings->enabled() || ! $settings->connected())
        <div class="admin-flash admin-flash-error">
            {{ __('furgonetka.order.not_ready') }}
            <a href="{{ route('admin.shipping.furgonetka.settings') }}">
                {{ __('furgonetka.order.open_settings') }}
            </a>
        </div>
    @endif

    @if ($order->shipping_method === 'parcel_locker')
        <div class="shipping-info shipping-warning">
            <strong>{{ __('furgonetka.order.point_title') }}</strong><br>
            {{ __('furgonetka.order.point_guard') }}<br>
            {{ $order->shipping_point_name ?: $order->shipping_point }}
            @if ($order->shipping_point_type)
                · {{ strtoupper($order->shipping_point_type) }}
            @endif
        </div>
    @endif

    @if ($order->shipping_method === 'pickup')
        <div class="shipping-info shipping-warning">
            {{ __('furgonetka.order.local_pickup') }}
        </div>
    @endif

    @if (
        $settings->enabled()
        && $settings->connected()
        && $order->shipping_method === 'courier'
    )
        <form
            class="cms-panel"
            method="post"
            action="{{ route(
                'admin.shipping.furgonetka.shipments.create',
                $order
            ) }}"
        >
            @csrf

            <div class="catalog-section-title">
                <div>
                    <h2>{{ __('furgonetka.order.create') }}</h2>
                    <p>{{ __('furgonetka.order.create_help') }}</p>
                </div>
            </div>

            <div class="cms-field">
                <label>{{ __('furgonetka.order.service') }}</label>
                <select name="service_id" required>
                    <option value="">{{ __('furgonetka.order.choose_service') }}</option>
                    @foreach ($services as $service)
                        <option value="{{ $service['id'] }}">
                            {{ $service['service'] ?? 'service' }}
                            — {{ $service['name'] ?? ('#' . $service['id']) }}
                            ({{ $service['owner'] ?? '?' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button class="cms-primary-button" type="submit">
                {{ __('furgonetka.order.create_button') }}
            </button>
        </form>
    @endif

    <section class="cms-panel">
        <div class="catalog-section-title">
            <div>
                <h2>{{ __('furgonetka.order.shipments') }}</h2>
            </div>
        </div>

        @forelse ($order->shipments as $shipment)
            <div class="admin-payment-box">
                <strong>
                    {{ strtoupper($shipment->carrier ?: 'Furgonetka') }}
                    · {{ $shipment->external_package_id }}
                </strong>

                <small>
                    {{ __('furgonetka.order.state') }}:
                    {{ $shipment->state ?: '—' }}
                </small>

                @if ($shipment->tracking_number)
                    <small>
                        {{ __('furgonetka.order.tracking_number') }}:
                        {{ $shipment->tracking_number }}
                    </small>
                @endif

                @if ($shipment->last_tracking_status)
                    <small>
                        {{ $shipment->last_tracking_status }}
                        @if ($shipment->last_tracking_at)
                            · {{ $shipment->last_tracking_at->format('Y-m-d H:i') }}
                        @endif
                    </small>
                @endif

                <div class="shipping-actions">
                    @if (in_array($shipment->state, ['waiting', null], true))
                        <form
                            method="post"
                            action="{{ route(
                                'admin.shipping.furgonetka.shipments.order',
                                [$order, $shipment]
                            ) }}"
                        >
                            @csrf
                            <button class="cms-primary-button" type="submit">
                                {{ __('furgonetka.order.order_shipment') }}
                            </button>
                        </form>
                    @endif

                    <form
                        method="post"
                        action="{{ route(
                            'admin.shipping.furgonetka.shipments.tracking',
                            [$order, $shipment]
                        ) }}"
                    >
                        @csrf
                        <button class="cms-secondary-button" type="submit">
                            {{ __('furgonetka.order.refresh_tracking') }}
                        </button>
                    </form>

                    @if ($shipment->ordered_at)
                        <a
                            class="cms-secondary-button"
                            href="{{ route(
                                'admin.shipping.furgonetka.shipments.label',
                                [$order, $shipment]
                            ) }}"
                        >
                            {{ __('furgonetka.order.label') }}
                        </a>
                    @endif

                    @if ($shipment->tracking_url)
                        <a
                            class="cms-secondary-button"
                            href="{{ $shipment->tracking_url }}"
                            target="_blank"
                            rel="noopener"
                        >
                            {{ __('furgonetka.order.track_external') }}
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <p class="cms-empty">{{ __('furgonetka.order.empty') }}</p>
        @endforelse
    </section>
</section>
@endsection
