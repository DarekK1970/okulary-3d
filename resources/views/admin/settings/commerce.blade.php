@extends('admin.layout')

@section('title', __('commerce_settings.title') . ' — ' . __('admin.title'))
@section('page_heading', __('commerce_settings.title'))

@section('content')
<section class="commerce-settings-page">
    <div class="cms-page-heading">
        <div>
            <span class="admin-eyebrow">{{ __('commerce_settings.kicker') }}</span>
            <h1>{{ __('commerce_settings.title') }}</h1>
            <p>{{ __('commerce_settings.description') }}</p>
        </div>
    </div>

    <form
        method="post"
        action="{{ route('admin.settings.update') }}"
        class="commerce-settings-form"
    >
        @csrf
        @method('PUT')

        <section class="cms-panel commerce-settings-panel">
            <div class="commerce-settings-heading">
                <div>
                    <span class="admin-eyebrow">PAYNOW</span>
                    <h2>{{ __('commerce_settings.paynow.title') }}</h2>
                    <p>{{ __('commerce_settings.paynow.description') }}</p>
                </div>

                <span class="commerce-config-status {{ $settings->payNowEnabled() ? 'is-ready' : '' }}">
                    {{ $settings->payNowEnabled()
                        ? __('commerce_settings.paynow.ready')
                        : __('commerce_settings.paynow.not_ready') }}
                </span>
            </div>

            <div class="commerce-settings-grid">
                <label class="cms-checkbox commerce-toggle">
                    <input
                        type="checkbox"
                        name="paynow_enabled"
                        value="1"
                        @checked(old('paynow_enabled', $settings->bool('paynow.enabled', false)))
                    >
                    <span>{{ __('commerce_settings.paynow.enabled') }}</span>
                </label>

                <label class="cms-checkbox commerce-toggle">
                    <input
                        type="checkbox"
                        name="paynow_sandbox"
                        value="1"
                        @checked(old('paynow_sandbox', $settings->payNowSandbox()))
                    >
                    <span>{{ __('commerce_settings.paynow.sandbox') }}</span>
                </label>

                <div class="cms-field">
                    <label>{{ __('commerce_settings.paynow.api_key') }}</label>

                    @if ($payNowApiKeyMasked)
                        <div class="commerce-secret-state">
                            {{ __('commerce_settings.secret_saved') }}
                            <strong>{{ $payNowApiKeyMasked }}</strong>
                        </div>
                    @endif

                    <input
                        type="password"
                        name="paynow_api_key"
                        value=""
                        autocomplete="new-password"
                        placeholder="{{ __('commerce_settings.secret_placeholder') }}"
                    >

                    <label class="commerce-clear-secret">
                        <input
                            type="checkbox"
                            name="clear_paynow_api_key"
                            value="1"
                        >
                        <span>{{ __('commerce_settings.clear_secret') }}</span>
                    </label>
                </div>

                <div class="cms-field">
                    <label>{{ __('commerce_settings.paynow.signature_key') }}</label>

                    @if ($payNowSignatureKeyMasked)
                        <div class="commerce-secret-state">
                            {{ __('commerce_settings.secret_saved') }}
                            <strong>{{ $payNowSignatureKeyMasked }}</strong>
                        </div>
                    @endif

                    <input
                        type="password"
                        name="paynow_signature_key"
                        value=""
                        autocomplete="new-password"
                        placeholder="{{ __('commerce_settings.secret_placeholder') }}"
                    >

                    <label class="commerce-clear-secret">
                        <input
                            type="checkbox"
                            name="clear_paynow_signature_key"
                            value="1"
                        >
                        <span>{{ __('commerce_settings.clear_secret') }}</span>
                    </label>
                </div>

                <div class="cms-field">
                    <label>{{ __('commerce_settings.paynow.timeout') }}</label>
                    <input
                        type="number"
                        name="paynow_timeout"
                        min="3"
                        max="60"
                        value="{{ old('paynow_timeout', $settings->payNowTimeout()) }}"
                        required
                    >
                </div>
            </div>

            <div class="commerce-webhook-box">
                <span>{{ __('commerce_settings.paynow.notification_url') }}</span>
                <code>{{ route('payments.paynow.notification') }}</code>
                <small>{{ __('commerce_settings.paynow.notification_help') }}</small>
            </div>

            <div class="commerce-security-note">
                <strong>{{ __('commerce_settings.security.title') }}</strong>
                <p>{{ __('commerce_settings.security.description') }}</p>
            </div>
        </section>

        <section class="cms-panel commerce-settings-panel">
            <div class="commerce-settings-heading">
                <div>
                    <span class="admin-eyebrow">{{ __('commerce_settings.bank.kicker') }}</span>
                    <h2>{{ __('commerce_settings.bank.title') }}</h2>
                    <p>{{ __('commerce_settings.bank.description') }}</p>
                </div>
            </div>

            <div class="commerce-settings-grid">
                <div class="cms-field">
                    <label>{{ __('commerce_settings.bank.recipient') }}</label>
                    <input
                        type="text"
                        name="bank_recipient"
                        value="{{ old('bank_recipient', $bank['recipient']) }}"
                        maxlength="255"
                    >
                </div>

                <div class="cms-field">
                    <label>{{ __('commerce_settings.bank.bank_name') }}</label>
                    <input
                        type="text"
                        name="bank_name"
                        value="{{ old('bank_name', $bank['bank_name']) }}"
                        maxlength="255"
                    >
                </div>

                <div class="cms-field">
                    <label>{{ __('commerce_settings.bank.account') }}</label>
                    <input
                        type="text"
                        name="bank_account"
                        value="{{ old('bank_account', $bank['account']) }}"
                        maxlength="100"
                    >
                </div>

                <div class="cms-field">
                    <label>SWIFT / BIC</label>
                    <input
                        type="text"
                        name="bank_swift"
                        value="{{ old('bank_swift', $bank['swift']) }}"
                        maxlength="40"
                    >
                </div>
            </div>
        </section>

        <section class="cms-panel commerce-settings-panel">
            <div class="commerce-settings-heading">
                <div>
                    <span class="admin-eyebrow">{{ __('commerce_settings.seller.kicker') }}</span>
                    <h2>{{ __('commerce_settings.seller.title') }}</h2>
                    <p>{{ __('commerce_settings.seller.description') }}</p>
                </div>
            </div>

            <div class="commerce-settings-grid">
                <div class="cms-field">
                    <label>{{ __('commerce_settings.seller.name') }}</label>
                    <input
                        type="text"
                        name="seller_name"
                        value="{{ old('seller_name', $seller['name']) }}"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="cms-field">
                    <label>{{ __('commerce_settings.seller.tax_id') }}</label>
                    <input
                        type="text"
                        name="seller_tax_id"
                        value="{{ old('seller_tax_id', $seller['tax_id']) }}"
                        maxlength="50"
                    >
                </div>

                <div class="cms-field commerce-full-width">
                    <label>{{ __('commerce_settings.seller.address') }}</label>
                    <textarea
                        name="seller_address"
                        rows="4"
                        maxlength="1000"
                    >{{ old('seller_address', $seller['address']) }}</textarea>
                </div>

                <div class="cms-field">
                    <label>{{ __('commerce_settings.seller.email') }}</label>
                    <input
                        type="email"
                        name="seller_email"
                        value="{{ old('seller_email', $seller['email']) }}"
                        maxlength="255"
                    >
                </div>
            </div>
        </section>

        <div class="commerce-settings-actions">
            <button class="cms-primary-button" type="submit">
                {{ __('commerce_settings.save') }}
            </button>
        </div>
    </form>
</section>
@endsection
