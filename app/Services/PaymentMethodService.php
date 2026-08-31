<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PaymentMethodService
{
    public function __construct(
        private readonly CommerceSettingsService $settings
    ) {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function available(
        string $locale,
        string $currency
    ): array {
        $currency = strtoupper(
            trim($currency)
        );

        $methods = [];

        foreach (
            config(
                'shop.payments',
                []
            )
            as $key => $method
        ) {
            if (
                ! (
                    $method['active']
                    ?? false
                )
            ) {
                continue;
            }

            if ($key === 'paynow') {
                if (
                    ! $this->settings
                        ->payNowSupportsCurrency(
                            $currency
                        )
                ) {
                    continue;
                }
            } elseif (
                ! in_array(
                    $currency,
                    $method[
                        'currencies'
                    ] ?? [],
                    true
                )
            ) {
                continue;
            }

            $methods[$key] = [
                'key' => $key,
                'name' =>
                    $method['name'][$locale]
                    ?? $method['name']['pl']
                    ?? $key,
                'currencies' =>
                    $key === 'paynow'
                        ? $this->settings
                            ->payNowEnabledCurrencies()
                        : (
                            $method[
                                'currencies'
                            ] ?? []
                        ),
            ];
        }

        return $methods;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(
        string $key,
        string $locale,
        string $currency
    ): array {
        $methods = $this->available(
            $locale,
            $currency
        );

        if (! isset($methods[$key])) {
            throw ValidationException
                ::withMessages([
                    'payment_method' => __(
                        'checkout71.validation.payment_unavailable'
                    ),
                ]);
        }

        return $methods[$key];
    }
}
