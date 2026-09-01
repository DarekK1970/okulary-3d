@extends('admin.layout')

@section('title', __('furgonetka.settings.title') . ' — ' . __('admin.title'))
@section('page_heading', __('furgonetka.settings.title'))

@section('content')
<section class="catalog-admin-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('furgonetka.settings.kicker') }}</span>
            <h1>{{ __('furgonetka.settings.title') }}</h1>
            <p>{{ __('furgonetka.settings.description') }}</p>
        </div>

        <div class="catalog-heading-actions">
            <a
                class="cms-secondary-button"
                href="{{ route('admin.shipping.index') }}"
            >
                ← {{ __('shipping.admin.title') }}
            </a>
        </div>
    </div>

    <form
        class="cms-panel"
        method="post"
        action="{{ route('admin.shipping.furgonetka.update') }}"
    >
        @csrf
        @method('PUT')

        <div class="cms-field">
            <label class="cms-checkbox">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    @checked($settings->enabled())
                >
                <span>{{ __('furgonetka.settings.enabled') }}</span>
            </label>
        </div>

        <div class="shipping-info">
            <strong>{{ __('furgonetka.settings.oauth_callback') }}</strong><br>
            <code>{{ $settings->authorizationCallbackUrl() }}</code>
            <br><br>
            {{ __('furgonetka.settings.oauth_help') }}
        </div>

        <div class="checkout-fields two-columns">
            <label>
                <span>Client ID</span>
                <input
                    type="text"
                    name="client_id"
                    value=""
                    placeholder="{{ $clientIdMasked ?: 'Client ID' }}"
                    autocomplete="off"
                >
            </label>

            <label>
                <span>Client Secret</span>
                <input
                    type="password"
                    name="client_secret"
                    value=""
                    placeholder="{{ $clientSecretMasked ?: 'Client Secret' }}"
                    autocomplete="new-password"
                >
            </label>

            <label class="full-width">
                <span>{{ __('furgonetka.settings.map_key') }}</span>
                <input
                    type="password"
                    name="map_api_key"
                    value=""
                    placeholder="{{ $mapApiKeyMasked ?: __('furgonetka.settings.map_key_placeholder') }}"
                    autocomplete="new-password"
                >
                <small>{{ __('furgonetka.settings.map_help') }}</small>
            </label>
        </div>

        <div class="catalog-section-title">
            <div>
                <h2>{{ __('furgonetka.settings.sender') }}</h2>
                <p>{{ __('furgonetka.settings.sender_help') }}</p>
            </div>
        </div>

        @php($sender = $settings->sender())

        <div class="checkout-fields two-columns">
            <label>
                <span>{{ __('furgonetka.fields.name') }} *</span>
                <input type="text" name="sender_name" value="{{ old('sender_name', $sender['name']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.company') }}</span>
                <input type="text" name="sender_company" value="{{ old('sender_company', $sender['company']) }}">
            </label>

            <label>
                <span>E-mail *</span>
                <input type="email" name="sender_email" value="{{ old('sender_email', $sender['email']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.phone') }} *</span>
                <input type="text" name="sender_phone" value="{{ old('sender_phone', $sender['phone']) }}" required>
            </label>

            <label class="full-width">
                <span>{{ __('furgonetka.fields.street') }} *</span>
                <input type="text" name="sender_street" value="{{ old('sender_street', $sender['street']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.postcode') }} *</span>
                <input type="text" name="sender_postcode" value="{{ old('sender_postcode', $sender['postcode']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.city') }} *</span>
                <input type="text" name="sender_city" value="{{ old('sender_city', $sender['city']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.country') }} *</span>
                <input type="text" name="sender_country_code" maxlength="2" value="{{ old('sender_country_code', $sender['country_code']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.county') }}</span>
                <input type="text" name="sender_county" value="{{ old('sender_county', $sender['county']) }}">
            </label>
        </div>

        @php($parcel = $settings->parcelDefaults())

        <div class="catalog-section-title">
            <div>
                <h2>{{ __('furgonetka.settings.parcel') }}</h2>
            </div>
        </div>

        <div class="checkout-fields two-columns">
            <label>
                <span>{{ __('furgonetka.fields.width') }}</span>
                <input type="number" name="parcel_width_cm" min="1" max="300" value="{{ old('parcel_width_cm', $parcel['width']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.height') }}</span>
                <input type="number" name="parcel_height_cm" min="1" max="300" value="{{ old('parcel_height_cm', $parcel['height']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.depth') }}</span>
                <input type="number" name="parcel_depth_cm" min="1" max="300" value="{{ old('parcel_depth_cm', $parcel['depth']) }}" required>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.label_format') }}</span>
                <select name="label_format" required>
                    @foreach (['pdf', 'zpl', 'epl'] as $format)
                        <option value="{{ $format }}" @selected($settings->labelFormat() === $format)>
                            {{ strtoupper($format) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>{{ __('furgonetka.fields.page_format') }}</span>
                <select name="label_page_format" required>
                    @foreach (['a6', 'a4'] as $format)
                        <option value="{{ $format }}" @selected($settings->labelPageFormat() === $format)>
                            {{ strtoupper($format) }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="cms-form-actions">
            <button class="cms-primary-button" type="submit">
                {{ __('furgonetka.settings.save') }}
            </button>
        </div>
    </form>

    <section class="cms-panel">
        <div class="catalog-section-title">
            <div>
                <h2>{{ __('furgonetka.connection.title') }}</h2>
                <p>
                    {{ $settings->connected()
                        ? __('furgonetka.connection.connected')
                        : __('furgonetka.connection.not_connected') }}
                </p>
            </div>
        </div>

        <div class="shipping-actions">
            @if ($settings->connected())
                <form method="post" action="{{ route('admin.shipping.furgonetka.test') }}">
                    @csrf
                    <button class="cms-secondary-button" type="submit">
                        {{ __('furgonetka.connection.test') }}
                    </button>
                </form>

                <form method="post" action="{{ route('admin.shipping.furgonetka.disconnect') }}">
                    @csrf
                    <button class="cms-action-button cms-action-danger" type="submit">
                        {{ __('furgonetka.connection.disconnect') }}
                    </button>
                </form>
            @else
                <a
                    class="cms-primary-button"
                    href="{{ route('admin.shipping.furgonetka.connect') }}"
                >
                    {{ __('furgonetka.connection.connect') }}
                </a>
            @endif
        </div>
    </section>
</section>
@endsection
