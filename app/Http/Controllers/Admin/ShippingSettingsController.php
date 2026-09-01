<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\ShippingCountry;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\ShippingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShippingSettingsController extends Controller
{
    public function index(
        ShippingSettingsService $settings
    ): View {
        return view(
            'admin.shipping.index',
            [
                'countries' =>
                    ShippingCountry::query()
                        ->orderBy('sort_order')
                        ->orderBy('code')
                        ->get(),

                'methods' =>
                    ShippingMethod::query()
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get(),

                'rates' =>
                    ShippingRate::query()
                        ->with([
                            'country',
                            'method',
                        ])
                        ->orderBy(
                            'shipping_country_id'
                        )
                        ->orderBy(
                            'shipping_method_id'
                        )
                        ->orderBy(
                            'weight_from_grams'
                        )
                        ->get(),

                'variants' =>
                    ProductVariant::query()
                        ->with([
                            'product.translations',
                        ])
                        ->orderBy('sku')
                        ->get(),

                'missingWeightCount' =>
                    ProductVariant::query()
                        ->where(function ($query) {
                            $query
                                ->whereNull(
                                    'weight_grams'
                                )
                                ->orWhere(
                                    'weight_grams',
                                    '<=',
                                    0
                                );
                        })
                        ->count(),

                'settings' => $settings,
            ]
        );
    }

    public function updateSettings(
        Request $request,
        ShippingSettingsService $settings
    ): RedirectResponse {
        $validated = $request->validate([
            'logistics_margin_percent' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'countries' => [
                'nullable',
                'array',
            ],
            'countries.*' => [
                'string',
                'size:2',
                Rule::exists(
                    'shipping_countries',
                    'code'
                ),
            ],

            'methods' => [
                'nullable',
                'array',
            ],
            'methods.*' => [
                'integer',
                Rule::exists(
                    'shipping_methods',
                    'id'
                ),
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $settings
        ): void {
            ShippingCountry::query()
                ->update([
                    'is_enabled' => false,
                    'is_default' => false,
                ]);

            $enabledCountries = array_values(
                array_unique(
                    array_map(
                        'strtoupper',
                        $validated[
                            'countries'
                        ] ?? []
                    )
                )
            );

            if (
                $enabledCountries !== []
            ) {
                ShippingCountry::query()
                    ->whereIn(
                        'code',
                        $enabledCountries
                    )
                    ->update([
                        'is_enabled' => true,
                    ]);
            }

            // Polska jest zawsze dostępna i domyślna.
            ShippingCountry::query()
                ->where(
                    'code',
                    ShippingSettingsService
                        ::DOMESTIC_COUNTRY
                )
                ->update([
                    'is_enabled' => true,
                    'is_default' => true,
                ]);

            ShippingMethod::query()
                ->update([
                    'is_enabled' => false,
                ]);

            $enabledMethods = array_map(
                'intval',
                $validated['methods'] ?? []
            );

            if ($enabledMethods !== []) {
                ShippingMethod::query()
                    ->whereIn(
                        'id',
                        $enabledMethods
                    )
                    ->update([
                        'is_enabled' => true,
                    ]);
            }

            $settings
                ->saveLogisticsMargin(
                    number_format(
                        (float) $validated[
                            'logistics_margin_percent'
                        ],
                        2,
                        '.',
                        ''
                    )
                );
        });

        return back()->with(
            'status',
            __('shipping.admin.messages.settings_saved')
        );
    }

    public function storeRate(
        Request $request
    ): RedirectResponse {
        $data = $this->validatedRate(
            $request
        );

        $this->assertNoOverlap(
            $data
        );

        ShippingRate::create($data);

        return back()->with(
            'status',
            __('shipping.admin.messages.rate_created')
        );
    }

    public function updateRate(
        Request $request,
        ShippingRate $shippingRate
    ): RedirectResponse {
        $data = $this->validatedRate(
            $request
        );

        $this->assertNoOverlap(
            $data,
            $shippingRate
        );

        $shippingRate->update($data);

        return back()->with(
            'status',
            __('shipping.admin.messages.rate_updated')
        );
    }

    public function destroyRate(
        ShippingRate $shippingRate
    ): RedirectResponse {
        $shippingRate->delete();

        return back()->with(
            'status',
            __('shipping.admin.messages.rate_deleted')
        );
    }

    public function updateWeights(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'weights' => [
                'required',
                'array',
            ],
            'weights.*' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],
        ]);

        $variantIds = array_map(
            'intval',
            array_keys(
                $validated['weights']
            )
        );

        $existingIds =
            ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->pluck('id')
                ->map(
                    fn ($id) => (int) $id
                )
                ->all();

        if (
            count($existingIds)
            !== count($variantIds)
        ) {
            throw ValidationException
                ::withMessages([
                    'weights' => __(
                        'shipping.admin.validation.variant_missing'
                    ),
                ]);
        }

        DB::transaction(function () use (
            $validated
        ): void {
            foreach (
                $validated['weights']
                as $variantId => $weight
            ) {
                ProductVariant::query()
                    ->whereKey(
                        (int) $variantId
                    )
                    ->update([
                        'weight_grams' =>
                            filled($weight)
                                ? (int) $weight
                                : null,
                    ]);
            }
        });

        return back()->with(
            'status',
            __('shipping.admin.messages.weights_saved')
        );
    }

    /**
     * @return array{
     *   shipping_country_id:int,
     *   shipping_method_id:int,
     *   weight_from_grams:int,
     *   weight_to_grams:int,
     *   price_pln:string,
     *   is_enabled:bool
     * }
     */
    private function validatedRate(
        Request $request
    ): array {
        $validated = $request->validate([
            'shipping_country_id' => [
                'required',
                'integer',
                Rule::exists(
                    'shipping_countries',
                    'id'
                ),
            ],
            'shipping_method_id' => [
                'required',
                'integer',
                Rule::exists(
                    'shipping_methods',
                    'id'
                ),
            ],
            'weight_from_kg' => [
                'required',
                'numeric',
                'min:0',
                'max:1000',
            ],
            'weight_to_kg' => [
                'required',
                'numeric',
                'gt:weight_from_kg',
                'max:1000',
            ],
            'price_pln' => [
                'required',
                'numeric',
                'min:0',
                'max:100000',
            ],
            'is_enabled' => [
                'nullable',
                'boolean',
            ],
        ]);

        return [
            'shipping_country_id' =>
                (int) $validated[
                    'shipping_country_id'
                ],
            'shipping_method_id' =>
                (int) $validated[
                    'shipping_method_id'
                ],
            'weight_from_grams' =>
                $this->kgToGrams(
                    $validated[
                        'weight_from_kg'
                    ]
                ),
            'weight_to_grams' =>
                $this->kgToGrams(
                    $validated[
                        'weight_to_kg'
                    ]
                ),
            'price_pln' =>
                number_format(
                    (float) $validated[
                        'price_pln'
                    ],
                    2,
                    '.',
                    ''
                ),
            'is_enabled' =>
                $request->boolean(
                    'is_enabled'
                ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertNoOverlap(
        array $data,
        ?ShippingRate $ignore = null
    ): void {
        $overlap = ShippingRate::query()
            ->where(
                'shipping_country_id',
                $data['shipping_country_id']
            )
            ->where(
                'shipping_method_id',
                $data['shipping_method_id']
            )
            ->when(
                $ignore,
                fn ($query) => $query
                    ->whereKeyNot($ignore->id)
            )
            ->where(
                'weight_from_grams',
                '<=',
                $data['weight_to_grams']
            )
            ->where(
                'weight_to_grams',
                '>=',
                $data['weight_from_grams']
            )
            ->exists();

        if ($overlap) {
            throw ValidationException
                ::withMessages([
                    'weight_from_kg' => __(
                        'shipping.admin.validation.overlap'
                    ),
                ]);
        }
    }

    private function kgToGrams(
        mixed $value
    ): int {
        return (int) round(
            ((float) $value) * 1000,
            0,
            PHP_ROUND_HALF_UP
        );
    }
}
