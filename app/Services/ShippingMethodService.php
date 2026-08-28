<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class ShippingMethodService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function available(
        string $locale,
        string $currency
    ): array {
        $methods = [];

        foreach (config('shop.shipping.methods', []) as $key => $method) {
            if (! ($method['active'] ?? false)) {
                continue;
            }

            if (! array_key_exists($currency, $method['prices'] ?? [])) {
                continue;
            }

            $methods[$key] = [
                'key' => $key,
                'name' => $method['name'][$locale]
                    ?? $method['name']['pl']
                    ?? $key,
                'price_cents' => $this->moneyToCents(
                    (string) $method['prices'][$currency]
                ),
                'currency' => $currency,
                'requires_point' => (bool) (
                    $method['requires_point'] ?? false
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
        $methods = $this->available($locale, $currency);

        if (! isset($methods[$key])) {
            throw ValidationException::withMessages([
                'shipping_method' => __(
                    'checkout71.validation.shipping_unavailable'
                ),
            ]);
        }

        return $methods[$key];
    }

    private function moneyToCents(string $value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
