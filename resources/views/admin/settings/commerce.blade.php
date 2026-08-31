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
                    <span class="admin-eyebrow">
                        {{ __('commerce_settings.currencies.kicker') }}
                    </span>
                    <h2>
                        {{ __('commerce_settings.currencies.title') }}
                    </h2>
                    <p>
                        {{ __('commerce_settings.currencies.description') }}
                    </p>
                </div>

                <div class="currency-heading-actions">
                    <span class="commerce-config-status is-ready">
                        {{ __('commerce_settings.currencies.base_badge', [
                            'code' => $currencySettings->baseCode(),
                        ]) }}
                    </span>

                    <button
                        class="cms-secondary-button currency-refresh-button"
                        type="submit"
                        name="currency_refresh_now"
                        value="1"
                        title="{{ __('commerce_settings.currencies.refresh_help') }}"
                    >
                        ↻ {{ __('commerce_settings.currencies.refresh_now') }}
                    </button>
                </div>
            </div>

            @error('currency_rates')
                <div class="commerce-currency-error">
                    {{ $message }}
                </div>
            @enderror

            <input
                type="hidden"
                name="currency_settings_present"
                value="1"
            >

            <div class="commerce-settings-grid">
                <div class="cms-field">
                    <label>
                        {{ __('commerce_settings.currencies.base') }}
                    </label>
                    <input
                        type="text"
                        value="{{ $currencySettings->baseCode() }}"
                        readonly
                    >
                    <small>
                        {{ __('commerce_settings.currencies.base_help') }}
                    </small>
                </div>

                <div class="cms-field">
                    <label>
                        {{ __('commerce_settings.currencies.default') }}
                    </label>
                    <select
                        name="default_currency"
                        required
                    >
                        @foreach ($currencies as $currency)
                            <option
                                value="{{ $currency->code }}"
                                @selected(
                                    old(
                                        'default_currency',
                                        $currencySettings->defaultCode()
                                    ) === $currency->code
                                )
                            >
                                {{ $currency->code }}
                                — {{ $currency->localizedName('pl') }}
                                ({{ $currency->symbol }})
                            </option>
                        @endforeach
                    </select>
                    <small>
                        {{ __('commerce_settings.currencies.default_help') }}
                    </small>
                </div>

                <label class="cms-checkbox commerce-toggle">
                    <input
                        type="checkbox"
                        name="currency_auto_update"
                        value="1"
                        @checked(
                            old(
                                'currency_auto_update',
                                $currencySettings->autoUpdateEnabled()
                            )
                        )
                    >
                    <span>
                        {{ __('commerce_settings.currencies.auto_update') }}
                    </span>
                </label>

                <div class="cms-field">
                    <label>
                        {{ __('commerce_settings.currencies.update_time') }}
                    </label>
                    <input
                        type="time"
                        name="currency_update_time"
                        value="{{ old(
                            'currency_update_time',
                            $currencySettings->updateTime()
                        ) }}"
                        required
                    >
                    <small>
                        {{ __('commerce_settings.currencies.update_time_help') }}
                    </small>
                </div>

                <div class="cms-field">
                    <label>
                        {{ __('commerce_settings.currencies.provider') }}
                    </label>
                    <input
                        type="text"
                        value="NBP"
                        readonly
                    >
                    <small>
                        {{ __('commerce_settings.currencies.provider_help') }}
                    </small>
                </div>

                <div class="cms-field">
                    <label>
                        {{ __('commerce_settings.currencies.markup') }}
                    </label>
                    <div class="currency-markup-input">
                        <input
                            type="number"
                            name="currency_markup_percent"
                            min="0"
                            max="20"
                            step="0.01"
                            value="{{ old(
                                'currency_markup_percent',
                                $currencySettings->markupPercent()
                            ) }}"
                            required
                        >
                        <span>%</span>
                    </div>
                    <small>
                        {{ __('commerce_settings.currencies.markup_help') }}
                    </small>
                </div>
            </div>

            <div class="currency-table-wrap">
                <table class="currency-settings-table">
                    <thead>
                        <tr>
                            <th>
                                {{ __('commerce_settings.currencies.table.enabled') }}
                            </th>
                            <th>
                                {{ __('commerce_settings.currencies.table.currency') }}
                            </th>
                            <th>
                                {{ __('commerce_settings.currencies.table.symbol') }}
                            </th>
                            <th>
                                {{ __('commerce_settings.currencies.table.rate') }}
                            </th>
                            <th>
                                {{ __('commerce_settings.currencies.table.source') }}
                            </th>
                            <th>
                                {{ __('commerce_settings.currencies.table.date') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($currencies as $currency)
                            @php
                                $rate = $currencyRates->get(
                                    $currency->id
                                );
                                $isBase =
                                    $currency->code
                                    === $currencySettings->baseCode();
                            @endphp

                            <tr>
                                <td>
                                    <label class="currency-enable-toggle">
                                        <input
                                            type="checkbox"
                                            name="enabled_currencies[]"
                                            value="{{ $currency->code }}"
                                            @checked(
                                                $isBase
                                                || old(
                                                    'enabled_currencies',
                                                    $currencies
                                                        ->where('is_enabled', true)
                                                        ->pluck('code')
                                                        ->all()
                                                )
                                                    && in_array(
                                                        $currency->code,
                                                        old(
                                                            'enabled_currencies',
                                                            $currencies
                                                                ->where('is_enabled', true)
                                                                ->pluck('code')
                                                                ->all()
                                                        ),
                                                        true
                                                    )
                                            )
                                            @disabled($isBase)
                                        >
                                        @if ($isBase)
                                            <input
                                                type="hidden"
                                                name="enabled_currencies[]"
                                                value="{{ $currency->code }}"
                                            >
                                        @endif
                                    </label>
                                </td>

                                <td>
                                    <strong>{{ $currency->code }}</strong>
                                    <span>
                                        {{ $currency->localizedName('pl') }}
                                    </span>
                                </td>

                                <td>
                                    {{ $currency->symbol }}
                                </td>

                                <td>
                                    @if ($isBase)
                                        <div class="currency-base-rate">
                                            1.00000000
                                        </div>
                                    @else
                                        <input
                                            class="currency-rate-input"
                                            type="number"
                                            name="manual_rates[{{ $currency->code }}]"
                                            min="0.00000001"
                                            max="1000000"
                                            step="0.00000001"
                                            value="{{ old(
                                                'manual_rates.' . $currency->code,
                                                $rate?->rate_to_base
                                            ) }}"
                                            placeholder="—"
                                        >
                                    @endif
                                </td>

                                <td>
                                    @if ($isBase)
                                        {{ __('commerce_settings.currencies.base_source') }}
                                    @elseif ($rate)
                                        {{ strtoupper($rate->source) }}
                                        @if ($rate->is_manual)
                                            <span class="currency-rate-badge">
                                                {{ __('commerce_settings.currencies.manual') }}
                                            </span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    @if ($isBase)
                                        —
                                    @elseif ($rate)
                                        {{ $rate->effective_date?->format('Y-m-d') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="commerce-security-note">
                <strong>
                    {{ __('commerce_settings.currencies.stage_note_title') }}
                </strong>
                <p>
                    {{ __('commerce_settings.currencies.stage_note') }}
                </p>
            </div>
        </section>

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

            <div class="paynow-currency-capabilities">
                <div class="paynow-currency-capabilities__heading">
                    <strong>
                        {{ __('commerce_settings.paynow.foreign_title') }}
                    </strong>
                    <p>
                        {{ __('commerce_settings.paynow.foreign_description') }}
                    </p>
                </div>

                <div class="paynow-currency-grid">
                    @foreach (
                        \App\Services\CommerceSettingsService::PAYNOW_FOREIGN_CURRENCIES
                        as $payNowCurrency
                    )
                        <label class="cms-checkbox commerce-toggle">
                            <input
                                type="checkbox"
                                name="paynow_foreign_currencies[]"
                                value="{{ $payNowCurrency }}"
                                @checked(
                                    in_array(
                                        $payNowCurrency,
                                        old(
                                            'paynow_foreign_currencies',
                                            $settings->payNowEnabledCurrencies()
                                        ),
                                        true
                                    )
                                )
                            >
                            <span>
                                {{ $payNowCurrency }}
                                — {{ __('commerce_settings.paynow.card_only') }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="commerce-security-note paynow-foreign-warning">
                    <strong>
                        {{ __('commerce_settings.paynow.foreign_warning_title') }}
                    </strong>
                    <p>
                        {{ __('commerce_settings.paynow.foreign_warning') }}
                    </p>
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
