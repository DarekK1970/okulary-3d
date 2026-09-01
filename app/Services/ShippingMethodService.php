<?php

namespace App\Services;

use App\Models\ShippingCountry;
use App\Models\ShippingRate;
use Illuminate\Validation\ValidationException;

class ShippingMethodService
{
    public function __construct(
        private readonly CurrencyService $currencies,
        private readonly ShippingSettingsService $shippingSettings
    ) {
    }

    /**
     * @return array<string, array{code:string,name:string}>
     */
    public function countries(
        string $locale
    ): array {
        return ShippingCountry::query()
            ->where('is_enabled', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(
                fn (ShippingCountry $country): array => [
                    $country->code => [
                        'code' => $country->code,
                        'name' => $country->name(
                            $locale
                        ),
                    ],
                ]
            )
            ->all();
    }

    public function defaultCountryCode(): string
    {
        return ShippingCountry::query()
            ->where('is_enabled', true)
            ->where('is_default', true)
            ->value('code')
            ?? ShippingSettingsService
                ::DOMESTIC_COUNTRY;
    }

    /**
     * @param array<string, mixed>|null $pricingSnapshot
     * @return array<string, array<string, mixed>>
     */
    public function available(
        string $locale,
        string $currency,
        string $countryCode = 'PL',
        int $weightGrams = 0,
        ?array $pricingSnapshot = null
    ): array {
        $countryCode = strtoupper(
            trim($countryCode)
        );

        $country = ShippingCountry::query()
            ->where('code', $countryCode)
            ->where('is_enabled', true)
            ->first();

        if (! $country) {
            return [];
        }

        $pricingSnapshot ??=
            $this->currencies
                ->pricingSnapshotForCode(
                    $currency
                );

        $rates = ShippingRate::query()
            ->with('method')
            ->where(
                'shipping_country_id',
                $country->id
            )
            ->where('is_enabled', true)
            ->where(
                'weight_from_grams',
                '<=',
                $weightGrams
            )
            ->where(
                'weight_to_grams',
                '>=',
                $weightGrams
            )
            ->whereHas(
                'method',
                fn ($query) => $query
                    ->where(
                        'is_enabled',
                        true
                    )
            )
            ->get()
            ->sortBy(
                fn (ShippingRate $rate) =>
                    $rate->method?->sort_order
                    ?? 9999
            );

        $methods = [];

        foreach ($rates as $rate) {
            $method = $rate->method;

            if (! $method) {
                continue;
            }

            $baseBeforeMarginCents =
                (int) round(
                    ((float) $rate->price_pln)
                    * 100,
                    0,
                    PHP_ROUND_HALF_UP
                );

            $basePriceCents =
                $this->shippingSettings
                    ->applyLogisticsMarginCents(
                        $baseBeforeMarginCents,
                        $country->code
                    );

            $logisticsMargin =
                $country->code
                    === ShippingSettingsService
                        ::DOMESTIC_COUNTRY
                    ? '0.00'
                    : $this->shippingSettings
                        ->logisticsMarginPercent();

            $priceCents =
                $this->currencies
                    ->convertBaseCentsWithSnapshot(
                        $basePriceCents,
                        $pricingSnapshot
                    );

            $methods[$method->code] = [
                'key' => $method->code,
                'name' => $method->name(
                    $locale
                ),
                'rate_id' => $rate->id,
                'country_code' =>
                    $country->code,
                'country_name' =>
                    $country->name($locale),
                'weight_grams' =>
                    $weightGrams,
                'base_price_before_margin_cents' =>
                    $baseBeforeMarginCents,
                'logistics_margin_percent' =>
                    number_format(
                        (float) $logisticsMargin,
                        2,
                        '.',
                        ''
                    ),
                'base_price_cents' =>
                    $basePriceCents,
                'price_cents' =>
                    $priceCents,
                'base_currency' =>
                    CurrencySettingsService
                        ::BASE_CURRENCY,
                'currency' =>
                    $pricingSnapshot[
                        'currency'
                    ],
                'requires_point' =>
                    $method
                        ->requires_pickup_point,
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
        string $countryCode = 'PL',
        int $weightGrams = 0,
        ?array $pricingSnapshot = null
    ): array {
        $methods = $this->available(
            $locale,
            $currency,
            $countryCode,
            $weightGrams,
            $pricingSnapshot
        );

        if (! isset($methods[$key])) {
            throw ValidationException
                ::withMessages([
                    'shipping_method' => __(
                        'shipping.checkout.method_unavailable'
                    ),
                ]);
        }

        return $methods[$key];
    }
}
