@extends('admin.layout')

@section(
    'title',
    __('furgonetka.settings.title')
        . ' — '
        . __('admin.title')
)

@section(
    'page_heading',
    __('furgonetka.settings.title')
)

@section('content')
<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">
                {{ __('furgonetka.settings.kicker') }}
            </span>

            <h1>
                {{ __('furgonetka.settings.title') }}
            </h1>

            <p>
                {{ __('furgonetka.settings.description') }}
            </p>
        </div>

        <div class="catalog-heading-actions">
            <a
                class="cms-secondary-button"
                href="{{ route(
                    'admin.shipping.index'
                ) }}"
            >
                ← {{ __('shipping.admin.title') }}
            </a>
        </div>
    </div>

    <section class="cms-panel">
        <div class="catalog-section-title">
            <div>
                <h2>
                    {{ __('furgonetka.universal.title') }}
                </h2>

                <p>
                    {{ __('furgonetka.universal.description') }}
                </p>
            </div>
        </div>

        <div class="shipping-info">
            <strong>
                {{ __('furgonetka.universal.furgonetka_form') }}
            </strong>

            <ol class="furgonetka-instructions">
                <li>
                    <strong>{{ __('furgonetka.universal.display_name') }}:</strong>
                    Okulary3D
                </li>
                <li>
                    <strong>{{ __('furgonetka.universal.shop_url') }}:</strong>
                    <code>{{ $settings->integrationBaseUrl() }}</code>
                </li>
                <li>
                    <strong>{{ __('furgonetka.universal.token') }}:</strong>
                    {{ __('furgonetka.universal.copy_token_below') }}
                </li>
                <li>
                    ✅ {{ __('furgonetka.universal.enable_order_sync') }}
                </li>
                <li>
                    ✅ {{ __('furgonetka.universal.enable_tracking_callback') }}
                </li>
            </ol>
        </div>

        <div class="checkout-fields">
            <label class="full-width">
                <span>
                    {{ __('furgonetka.universal.orders_endpoint') }}
                </span>

                <input
                    type="text"
                    value="{{ $settings->ordersUrl() }}"
                    readonly
                >
            </label>

            <label class="full-width">
                <span>
                    {{ __('furgonetka.universal.tracking_endpoint') }}
                </span>

                <input
                    type="text"
                    value="{{ $settings->trackingUrlTemplate() }}"
                    readonly
                >
            </label>

            <label class="full-width">
                <span>
                    {{ __('furgonetka.universal.token') }}
                </span>

                <input
                    type="text"
                    value="{{ $universalToken ?? '' }}"
                    readonly
                    autocomplete="off"
                    spellcheck="false"
                >

                <small>
                    {{ __('furgonetka.universal.token_help') }}
                </small>
            </label>
        </div>

        <form
            method="post"
            action="{{ route(
                'admin.shipping.furgonetka.token.generate'
            ) }}"
        >
            @csrf

            <button
                class="cms-primary-button"
                type="submit"
            >
                {{ $universalToken
                    ? __('furgonetka.universal.regenerate_token')
                    : __('furgonetka.universal.generate_token') }}
            </button>
        </form>
    </section>

    <form
        class="cms-panel"
        method="post"
        action="{{ route(
            'admin.shipping.furgonetka.update'
        ) }}"
    >
        @csrf
        @method('PUT')

        <div class="catalog-section-title">
            <div>
                <h2>
                    {{ __('furgonetka.settings.runtime') }}
                </h2>
            </div>
        </div>

        <div class="cms-field">
            <label class="cms-checkbox">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    @checked(
                        $settings->enabled()
                    )
                >

                <span>
                    {{ __('furgonetka.settings.enabled') }}
                </span>
            </label>

            <small>
                {{ __('furgonetka.settings.enabled_help') }}
            </small>
        </div>

        <div class="catalog-section-title">
            <div>
                <h2>
                    {{ __('furgonetka.map.title') }}
                </h2>

                <p>
                    {{ __('furgonetka.map.help') }}
                </p>
            </div>
        </div>

        <div class="checkout-fields">
            <label class="full-width">
                <span>
                    {{ __('furgonetka.map.key') }}
                </span>

                <input
                    type="password"
                    name="map_api_key"
                    value=""
                    placeholder="{{ $settings->mapApiKey()
                        ? '••••••••••••••••'
                        : __('furgonetka.map.key_placeholder') }}"
                    autocomplete="new-password"
                >

                <small>
                    {{ __('furgonetka.map.key_help') }}
                </small>
            </label>
        </div>

        <div class="cms-form-actions">
            <button
                class="cms-primary-button"
                type="submit"
            >
                {{ __('furgonetka.settings.save') }}
            </button>
        </div>
    </form>

    <section class="cms-panel">
        <div class="shipping-info">
            <strong>
                {{ __('furgonetka.universal.security_title') }}
            </strong>

            <p>
                {{ __('furgonetka.universal.security_text') }}
            </p>
        </div>
    </section>
</section>
@endsection
