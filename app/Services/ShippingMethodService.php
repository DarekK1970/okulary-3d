<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class ShippingMethodService
{
    public function __construct(
        private readonly CurrencyService $currencies
    ) {
    }

    /**
     * @param array<string, mixed>|null $pricingSnapshot
     * @return array<string, array<string, mixed>>
     */
    public function available(
        string $locale,
        string $currency,
        ?array $pricingSnapshot = null
    ): array {
        $pricingSnapshot ??=
            $this->currencies->pricingSnapshotForCode($currency);

        $methods = [];

        foreach (config('shop.shipping.methods', []) as $key => $method) {
            if (! ($method['active'] ?? false)) {
                continue;
            }

            $basePrice = $method['base_price_pln']
                ?? data_get($method, 'prices.PLN');

            if (! is_numeric($basePrice)) {
                continue;
            }

            $basePriceCents = (int) round(
                ((float) $basePrice) * 100,
                0,
                PHP_ROUND_HALF_UP
            );

            $priceCents = $this->currencies->convertBaseCentsWithSnapshot(
                $basePriceCents,
                $pricingSnapshot
            );

            $methods[$key] = [
                'key' => $key,
                'name' => $method['name'][$locale]
                    ?? $method['name']['pl']
                    ?? $key,
                'base_price_cents' => $basePriceCents,
                'price_cents' => $priceCents,
                'base_currency' => CurrencySettingsService::BASE_CURRENCY,
                'currency' => $pricingSnapshot['currency'],
                'requires_point' => (bool) (
                    $method['requires_point'] ?? false
                ),
            ];
        }

        return $methods;
    }

    /**
     * @param array<string, mixed>|null $pricingSnapshot
     * @return array<string, mixed>
     */
    public function resolve(
        string $key,
        string $locale,
        string $currency,
        ?array $pricingSnapshot = null
    ): array {
        $methods = $this->available(
            $locale,
            $currency,
            $pricingSnapshot
        );

        if (! isset($methods[$key])) {
            throw ValidationException::withMessages([
                'shipping_method' => __(
                    'checkout71.validation.shipping_unavailable'
                ),
            ]);
        }

        return $methods[$key];
    }
}
