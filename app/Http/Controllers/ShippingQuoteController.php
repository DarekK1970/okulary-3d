<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\CurrencyService;
use App\Services\ShippingMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShippingQuoteController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        CartService $cart,
        CurrencyService $currencies,
        ShippingMethodService $shippingMethods
    ): JsonResponse {
        $validated = $request->validate([
            'country' => [
                'required',
                'string',
                'size:2',
                Rule::exists(
                    'shipping_countries',
                    'code'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'is_enabled',
                            true
                        )
                ),
            ],
        ]);

        $countryCode = strtoupper(
            $validated['country']
        );

        $weightGrams =
            $cart->shippingWeightGrams(
                $locale
            );

        $currency =
            $currencies->selectedCode(
                $request
            );

        $methods = $shippingMethods
            ->available(
                $locale,
                $currency,
                $countryCode,
                $weightGrams
            );

        return response()->json([
            'country' => $countryCode,
            'weight_grams' => $weightGrams,
            'currency' => $currency,
            'methods' => array_values(
                array_map(
                    function (
                        array $method
                    ) use (
                        $currencies,
                        $locale
                    ): array {
                        return [
                            'key' =>
                                $method['key'],
                            'name' =>
                                $method['name'],
                            'price_cents' =>
                                $method[
                                    'price_cents'
                                ],
                            'formatted_price' =>
                                $currencies
                                    ->formatCents(
                                        $method[
                                            'price_cents'
                                        ],
                                        $method[
                                            'currency'
                                        ],
                                        $locale
                                    ),
                            'requires_point' =>
                                $method[
                                    'requires_point'
                                ],
                        ];
                    },
                    $methods
                )
            ),
        ]);
    }
}
