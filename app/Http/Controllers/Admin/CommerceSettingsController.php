<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\CommerceSettingsService;
use App\Services\CurrencySettingsService;
use App\Services\NbpCurrencyRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommerceSettingsController extends Controller
{
    public function index(
        CommerceSettingsService $settings,
        CurrencySettingsService $currencySettings
    ): View {
        $currencies =
            $currencySettings->currencies();

        return view('admin.settings.commerce', [
            'settings' => $settings,
            'payNowApiKeyMasked' =>
                $settings->maskedSecret(
                    'paynow.api_key'
                ),
            'payNowSignatureKeyMasked' =>
                $settings->maskedSecret(
                    'paynow.signature_key'
                ),
            'bank' => $settings->bankTransfer(),
            'seller' => $settings->seller(),
            'currencySettings' =>
                $currencySettings,
            'currencies' => $currencies,
            'currencyRates' =>
                $currencySettings->currentRates(
                    $currencies
                ),
        ]);
    }

    public function update(
        Request $request,
        CommerceSettingsService $settings,
        CurrencySettingsService $currencySettings,
        NbpCurrencyRateService $nbpRates
    ): RedirectResponse {
        $validated = $request->validate([
            'paynow_enabled' => [
                'nullable',
                'boolean',
            ],
            'paynow_sandbox' => [
                'nullable',
                'boolean',
            ],
            'paynow_api_key' => [
                'nullable',
                'string',
                'max:255',
            ],
            'paynow_signature_key' => [
                'nullable',
                'string',
                'max:255',
            ],
            'paynow_timeout' => [
                'required',
                'integer',
                'min:3',
                'max:60',
            ],
            'paynow_foreign_currencies' => [
                'nullable',
                'array',
            ],
            'paynow_foreign_currencies.*' => [
                'string',
                Rule::in(
                    CommerceSettingsService
                        ::PAYNOW_FOREIGN_CURRENCIES
                ),
            ],
            'clear_paynow_api_key' => [
                'nullable',
                'boolean',
            ],
            'clear_paynow_signature_key' => [
                'nullable',
                'boolean',
            ],
            'bank_recipient' => [
                'nullable',
                'string',
                'max:255',
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'bank_account' => [
                'nullable',
                'string',
                'max:100',
            ],
            'bank_swift' => [
                'nullable',
                'string',
                'max:40',
            ],
            'seller_name' => [
                'required',
                'string',
                'max:255',
            ],
            'seller_address' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'seller_tax_id' => [
                'nullable',
                'string',
                'max:50',
            ],
            'seller_email' => [
                'nullable',
                'email:rfc',
                'max:255',
            ],

            // K86.4A — optional for backward-compatible
            // requests/tests that predate currency settings.
            'currency_settings_present' => [
                'nullable',
                'boolean',
            ],
            'currency_refresh_now' => [
                'nullable',
                'boolean',
            ],
            'enabled_currencies' => [
                'nullable',
                'array',
            ],
            'enabled_currencies.*' => [
                'string',
                'size:3',
                Rule::exists(
                    'currencies',
                    'code'
                ),
            ],
            'default_currency' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists(
                    'currencies',
                    'code'
                ),
            ],
            'currency_auto_update' => [
                'nullable',
                'boolean',
            ],
            'currency_update_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'currency_markup_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:20',
            ],
            'manual_rates' => [
                'nullable',
                'array',
            ],
            'manual_rates.*' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:1000000',
            ],
        ]);

        $settings->setMany([
            'paynow.enabled' =>
                $request->boolean(
                    'paynow_enabled'
                )
                    ? '1'
                    : '0',
            'paynow.sandbox' =>
                $request->boolean(
                    'paynow_sandbox'
                )
                    ? '1'
                    : '0',
            'paynow.timeout' =>
                (string) $validated[
                    'paynow_timeout'
                ],
            'paynow.currency.EUR.enabled' =>
                in_array(
                    'EUR',
                    $validated[
                        'paynow_foreign_currencies'
                    ] ?? [],
                    true
                ) ? '1' : '0',
            'paynow.currency.GBP.enabled' =>
                in_array(
                    'GBP',
                    $validated[
                        'paynow_foreign_currencies'
                    ] ?? [],
                    true
                ) ? '1' : '0',
            'paynow.currency.USD.enabled' =>
                in_array(
                    'USD',
                    $validated[
                        'paynow_foreign_currencies'
                    ] ?? [],
                    true
                ) ? '1' : '0',
            'bank.recipient' =>
                $this->nullable(
                    $validated[
                        'bank_recipient'
                    ] ?? null
                ),
            'bank.name' =>
                $this->nullable(
                    $validated[
                        'bank_name'
                    ] ?? null
                ),
            'bank.account' =>
                $this->nullable(
                    $validated[
                        'bank_account'
                    ] ?? null
                ),
            'bank.swift' =>
                $this->nullable(
                    $validated[
                        'bank_swift'
                    ] ?? null
                ),
            'seller.name' => trim(
                $validated['seller_name']
            ),
            'seller.address' =>
                $this->nullable(
                    $validated[
                        'seller_address'
                    ] ?? null
                ),
            'seller.tax_id' =>
                $this->nullable(
                    $validated[
                        'seller_tax_id'
                    ] ?? null
                ),
            'seller.email' =>
                $this->nullable(
                    $validated[
                        'seller_email'
                    ] ?? null
                ),
        ]);

        if (
            $request->boolean(
                'clear_paynow_api_key'
            )
        ) {
            $settings->set(
                'paynow.api_key',
                null,
                true
            );
        } elseif (
            filled(
                $validated[
                    'paynow_api_key'
                ] ?? null
            )
        ) {
            $settings->set(
                'paynow.api_key',
                trim(
                    $validated[
                        'paynow_api_key'
                    ]
                ),
                true
            );
        }

        if (
            $request->boolean(
                'clear_paynow_signature_key'
            )
        ) {
            $settings->set(
                'paynow.signature_key',
                null,
                true
            );
        } elseif (
            filled(
                $validated[
                    'paynow_signature_key'
                ] ?? null
            )
        ) {
            $settings->set(
                'paynow.signature_key',
                trim(
                    $validated[
                        'paynow_signature_key'
                    ]
                ),
                true
            );
        }

        if (
            $request->boolean(
                'currency_settings_present'
            )
        ) {
            $enabled = array_map(
                'strtoupper',
                $validated[
                    'enabled_currencies'
                ] ?? []
            );

            if (
                ! in_array(
                    CurrencySettingsService
                        ::BASE_CURRENCY,
                    $enabled,
                    true
                )
            ) {
                $enabled[] =
                    CurrencySettingsService
                        ::BASE_CURRENCY;
            }

            $default = strtoupper(
                (string) (
                    $validated[
                        'default_currency'
                    ]
                    ?? CurrencySettingsService
                        ::BASE_CURRENCY
                )
            );

            if (
                ! in_array(
                    $default,
                    $enabled,
                    true
                )
            ) {
                throw ValidationException
                    ::withMessages([
                        'default_currency' => __(
                            'commerce_settings.currencies.errors.default_disabled'
                        ),
                    ]);
            }

            $currencySettings
                ->saveConfiguration(
                    $enabled,
                    $default,
                    $request->boolean(
                        'currency_auto_update'
                    ),
                    $validated[
                        'currency_update_time'
                    ] ?? '06:00',
                    number_format(
                        (float) (
                            $validated[
                                'currency_markup_percent'
                            ] ?? 0
                        ),
                        2,
                        '.',
                        ''
                    )
                );

            foreach (
                $validated[
                    'manual_rates'
                ] ?? []
                as $code => $rate
            ) {
                if (
                    blank($rate)
                    || strtoupper($code)
                        === CurrencySettingsService
                            ::BASE_CURRENCY
                ) {
                    continue;
                }

                $currency = Currency::query()
                    ->where(
                        'code',
                        strtoupper($code)
                    )
                    ->first();

                if (! $currency) {
                    continue;
                }

                $currencySettings
                    ->saveManualRate(
                        $currency,
                        (string) $rate
                    );
            }
        }

        if (
            $request->boolean(
                'currency_refresh_now'
            )
        ) {
            try {
                $result = $nbpRates->refresh();
            } catch (\Throwable $exception) {
                report($exception);

                return back()
                    ->withInput()
                    ->withErrors([
                        'currency_rates' => __(
                            'commerce_settings.currencies.errors.refresh_failed'
                        ),
                    ]);
            }

            return back()->with(
                'status',
                $result['count'] > 0
                    ? __(
                        'commerce_settings.currencies.messages.updated',
                        [
                            'count' => $result['count'],
                            'date' =>
                                $result['effective_date']
                                ?? '—',
                        ]
                    )
                    : __(
                        'commerce_settings.currencies.messages.no_foreign'
                    )
            );
        }

        return back()->with(
            'status',
            __('commerce_settings.messages.saved')
        );
    }

    private function nullable(
        ?string $value
    ): ?string {
        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }
}
