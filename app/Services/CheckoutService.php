<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly ShippingMethodService $shippingMethods,
        private readonly PaymentMethodService $paymentMethods,
        private readonly SalesDocumentService $documents,
        private readonly CurrencyService $currencies
    ) {
    }

    public function place(
        array $data,
        ?User $user,
        string $locale
    ): Order {
        $entries = $this->cart->entries();

        if ($entries === []) {
            throw ValidationException::withMessages([
                'cart' => __('cart.messages.empty'),
            ]);
        }

        $pricingSnapshot = $this->currencies->pricingSnapshot();

        $order = DB::transaction(function () use (
            $entries,
            $data,
            $user,
            $locale,
            $pricingSnapshot
        ) {
            $preparedItems = [];
            $subtotalCents = 0;
            $subtotalBaseCents = 0;
            $shippingWeightGrams = 0;
            $sourceCurrency = null;

            foreach ($entries as $variantId => $quantity) {
                $variant = ProductVariant::query()
                    ->with([
                        'product.translations',
                        'product.category',
                    ])
                    ->lockForUpdate()
                    ->find($variantId);

                if (
                    ! $variant
                    || ! $variant->is_active
                    || ! $variant->product
                    || $variant->product->status !== ProductStatus::Active
                    || ! $variant->product->category
                    || ! $variant->product->category->is_active
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __('cart.messages.changed'),
                    ]);
                }

                $translation =
                    $variant->product->publicTranslation($locale)
                    ?? $variant->product->sourceTranslation();

                if (! $translation) {
                    throw ValidationException::withMessages([
                        'cart' => __('cart.messages.changed'),
                    ]);
                }

                $quantity = (int) $quantity;

                if (
                    $variant->track_stock
                    && $quantity > $variant->stock_quantity
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __(
                            'cart.messages.stock_changed',
                            ['sku' => $variant->sku]
                        ),
                    ]);
                }

                if (
                    empty($variant->weight_grams)
                    || (int) $variant->weight_grams <= 0
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __(
                            'shipping.checkout.weight_missing'
                        ),
                    ]);
                }

                $shippingWeightGrams +=
                    (int) $variant->weight_grams
                    * $quantity;

                if (
                    $sourceCurrency !== null
                    && $sourceCurrency !== $variant->currency
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __('cart.messages.mixed_currency'),
                    ]);
                }

                $sourceCurrency ??= $variant->currency;

                $baseUnitPriceCents = $this->currencies->toBaseCents(
                    (string) $variant->price_gross,
                    $variant->currency
                );

                $unitPriceCents = $this->currencies
                    ->convertBaseCentsWithSnapshot(
                        $baseUnitPriceCents,
                        $pricingSnapshot
                    );

                $baseLineTotalCents = $baseUnitPriceCents * $quantity;
                $lineTotalCents = $unitPriceCents * $quantity;

                $subtotalBaseCents += $baseLineTotalCents;
                $subtotalCents += $lineTotalCents;

                $preparedItems[] = [
                    'variant' => $variant,
                    'translation' => $translation,
                    'quantity' => $quantity,
                    'base_unit_price_cents' => $baseUnitPriceCents,
                    'base_line_total_cents' => $baseLineTotalCents,
                    'unit_price_cents' => $unitPriceCents,
                    'line_total_cents' => $lineTotalCents,
                ];
            }

            if ($preparedItems === []) {
                throw ValidationException::withMessages([
                    'cart' => __('cart.messages.empty'),
                ]);
            }

            $currency = $pricingSnapshot['currency'];

            $shippingCountryCode = strtoupper(
                $data['shipping_country_code']
            );

            if (
                (bool) $data[
                    'shipping_same_as_billing'
                ]
                && strtoupper(
                    $data[
                        'billing_country_code'
                    ]
                ) !== $shippingCountryCode
            ) {
                throw ValidationException::withMessages([
                    'shipping_country_code' => __(
                        'shipping.checkout.same_address_country_mismatch'
                    ),
                ]);
            }

            $shippingMethod =
                $this->shippingMethods
                    ->resolve(
                        $data[
                            'shipping_method'
                        ],
                        $locale,
                        $currency,
                        $shippingCountryCode,
                        $shippingWeightGrams,
                        $pricingSnapshot
                    );

            if (
                $shippingMethod['requires_point']
                && blank($data['shipping_point'] ?? null)
            ) {
                throw ValidationException::withMessages([
                    'shipping_point' => __(
                        'checkout71.validation.shipping_point_required'
                    ),
                ]);
            }

            $paymentMethod = $this->paymentMethods->resolve(
                $data['payment_method'],
                $locale,
                $currency
            );

            $shipping = $this->shippingData($data);
            $shippingGrossCents =
                $shippingMethod[
                    'price_cents'
                ];

            $shippingBaseBeforeMarginCents =
                $shippingMethod[
                    'base_price_before_margin_cents'
                ];

            $shippingBaseGrossCents =
                $shippingMethod[
                    'base_price_cents'
                ];
            $totalCents = $subtotalCents + $shippingGrossCents;
            $totalBaseCents = $subtotalBaseCents + $shippingBaseGrossCents;

            $order = Order::create([
                'number' => $this->generateNumber(),
                'public_token' => (string) Str::uuid(),
                'user_id' => $user?->id,
                'locale' => $locale,
                'status' => OrderStatus::Pending,

                'currency' => $currency,
                'base_currency' => $pricingSnapshot['base_currency'],
                'exchange_rate' => $pricingSnapshot['rate'],
                'exchange_rate_source' => $pricingSnapshot['source'],
                'exchange_rate_effective_date' => $pricingSnapshot['effective_date'],
                'currency_markup_percent' => $pricingSnapshot['markup_percent'],

                'subtotal_gross' => $this->centsToMoney($subtotalCents),
                'subtotal_base_gross' => $this->centsToMoney($subtotalBaseCents),

                'shipping_gross' =>
                    $this->centsToMoney(
                        $shippingGrossCents
                    ),
                'shipping_base_gross' =>
                    $this->centsToMoney(
                        $shippingBaseGrossCents
                    ),
                'shipping_base_before_margin' =>
                    $this->centsToMoney(
                        $shippingBaseBeforeMarginCents
                    ),
                'shipping_logistics_margin_percent' =>
                    $shippingMethod[
                        'logistics_margin_percent'
                    ],
                'shipping_method' =>
                    $shippingMethod['key'],
                'shipping_rate_id' =>
                    $shippingMethod['rate_id'],
                'shipping_name_snapshot' =>
                    $shippingMethod['name'],
                'shipping_weight_grams' =>
                    $shippingWeightGrams,

                'payment_method' => $paymentMethod['key'],
                'payment_status' => PaymentStatus::Unpaid,

                'total_gross' => $this->centsToMoney($totalCents),
                'total_base_gross' => $this->centsToMoney($totalBaseCents),

                'customer_email' => $data['customer_email'],
                'customer_first_name' => $data['customer_first_name'],
                'customer_last_name' => $data['customer_last_name'],
                'customer_phone' => $data['customer_phone'] ?? null,

                'billing_company' => $data['billing_company'] ?? null,
                'billing_tax_id' => $data['billing_tax_id'] ?? null,
                'billing_address_line1' => $data['billing_address_line1'],
                'billing_address_line2' => $data['billing_address_line2'] ?? null,
                'billing_postal_code' => $data['billing_postal_code'],
                'billing_city' => $data['billing_city'],
                'billing_country_code' => strtoupper($data['billing_country_code']),

                'shipping_same_as_billing' => (bool) $data['shipping_same_as_billing'],
                'shipping_first_name' => $shipping['first_name'],
                'shipping_last_name' => $shipping['last_name'],
                'shipping_company' => $shipping['company'],
                'shipping_address_line1' => $shipping['address_line1'],
                'shipping_address_line2' => $shipping['address_line2'],
                'shipping_postal_code' => $shipping['postal_code'],
                'shipping_city' =>
                    $shipping['city'],
                'shipping_country_code' =>
                    $shippingCountryCode,
                'shipping_country_name_snapshot' =>
                    $shippingMethod[
                        'country_name'
                    ],
                'shipping_point' =>
                    (
                        $data[
                            'shipping_point'
                        ] ?? null
                    ) ?: null,
                'shipping_point_name' =>
                    (
                        $data[
                            'shipping_point_name'
                        ] ?? null
                    ) ?: null,
                'shipping_point_type' =>
                    (
                        $data[
                            'shipping_point_type'
                        ] ?? null
                    ) ?: null,
                'shipping_point_original_id' =>
                    (
                        $data[
                            'shipping_point_original_id'
                        ] ?? null
                    ) ?: null,
                'shipping_point_country_code' =>
                    (
                        $data[
                            'shipping_point_country_code'
                        ] ?? null
                    ) ?: null,

                'customer_note' => $data['customer_note'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($preparedItems as $prepared) {
                $variant = $prepared['variant'];

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'sku_snapshot' => $variant->sku,
                    'product_name_snapshot' => $prepared['translation']->name,
                    'variant_name_snapshot' => $variant->name,
                    'quantity' => $prepared['quantity'],

                    'unit_price_gross' => $this->centsToMoney(
                        $prepared['unit_price_cents']
                    ),
                    'base_unit_price_gross' => $this->centsToMoney(
                        $prepared['base_unit_price_cents']
                    ),
                    'vat_rate' => $variant->vat_rate,
                    'line_total_gross' => $this->centsToMoney(
                        $prepared['line_total_cents']
                    ),
                    'base_line_total_gross' => $this->centsToMoney(
                        $prepared['base_line_total_cents']
                    ),

                    'currency' => $currency,
                    'base_currency' => $pricingSnapshot['base_currency'],
                ]);

                if ($variant->track_stock) {
                    $variant->decrement(
                        'stock_quantity',
                        $prepared['quantity']
                    );
                }
            }

            return $order;
        });

        $this->documents->createOrderConfirmation($order);
        $this->cart->clear();

        return $order->load([
            'items',
            'salesDocuments',
        ]);
    }

    private function shippingData(array $data): array
    {
        if ((bool) $data['shipping_same_as_billing']) {
            return [
                'first_name' => $data['customer_first_name'],
                'last_name' => $data['customer_last_name'],
                'company' => $data['billing_company'] ?? null,
                'address_line1' => $data['billing_address_line1'],
                'address_line2' => $data['billing_address_line2'] ?? null,
                'postal_code' => $data['billing_postal_code'],
                'city' => $data['billing_city'],
                'country_code' => strtoupper($data['billing_country_code']),
            ];
        }

        return [
            'first_name' => $data['shipping_first_name'],
            'last_name' => $data['shipping_last_name'],
            'company' => $data['shipping_company'] ?? null,
            'address_line1' => $data['shipping_address_line1'],
            'address_line2' => $data['shipping_address_line2'] ?? null,
            'postal_code' => $data['shipping_postal_code'],
            'city' => $data['shipping_city'],
            'country_code' => strtoupper($data['shipping_country_code']),
        ];
    }

    private function generateNumber(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function centsToMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
