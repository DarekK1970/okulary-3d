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
        private readonly SalesDocumentService $documents
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

        $order = DB::transaction(function () use (
            $entries,
            $data,
            $user,
            $locale
        ) {
            $preparedItems = [];
            $subtotalCents = 0;
            $currency = null;

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
                    $currency !== null
                    && $currency !== $variant->currency
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __('cart.messages.mixed_currency'),
                    ]);
                }

                $currency ??= $variant->currency;

                $unitPriceCents = $this->moneyToCents(
                    (string) $variant->price_gross
                );
                $lineTotalCents = $unitPriceCents * $quantity;

                $subtotalCents += $lineTotalCents;

                $preparedItems[] = [
                    'variant' => $variant,
                    'translation' => $translation,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'line_total_cents' => $lineTotalCents,
                ];
            }

            if ($preparedItems === [] || $currency === null) {
                throw ValidationException::withMessages([
                    'cart' => __('cart.messages.empty'),
                ]);
            }

            $shippingMethod = $this->shippingMethods->resolve(
                $data['shipping_method'],
                $locale,
                $currency
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
            $shippingGrossCents = $shippingMethod['price_cents'];
            $totalCents = $subtotalCents + $shippingGrossCents;

            $order = Order::create([
                'number' => $this->generateNumber(),
                'public_token' => (string) Str::uuid(),
                'user_id' => $user?->id,
                'locale' => $locale,
                'status' => OrderStatus::Pending,
                'currency' => $currency,

                'subtotal_gross' => $this->centsToMoney(
                    $subtotalCents
                ),
                'shipping_gross' => $this->centsToMoney(
                    $shippingGrossCents
                ),
                'shipping_method' => $shippingMethod['key'],
                'shipping_name_snapshot' => $shippingMethod['name'],

                'payment_method' => $paymentMethod['key'],
                'payment_status' => PaymentStatus::Unpaid,

                'total_gross' => $this->centsToMoney(
                    $totalCents
                ),

                'customer_email' => $data['customer_email'],
                'customer_first_name' => $data['customer_first_name'],
                'customer_last_name' => $data['customer_last_name'],
                'customer_phone' => $data['customer_phone'] ?? null,

                'billing_company' => $data['billing_company'] ?? null,
                'billing_tax_id' => $data['billing_tax_id'] ?? null,
                'billing_address_line1' =>
                    $data['billing_address_line1'],
                'billing_address_line2' =>
                    $data['billing_address_line2'] ?? null,
                'billing_postal_code' =>
                    $data['billing_postal_code'],
                'billing_city' => $data['billing_city'],
                'billing_country_code' => strtoupper(
                    $data['billing_country_code']
                ),

                'shipping_same_as_billing' =>
                    (bool) $data['shipping_same_as_billing'],
                'shipping_first_name' => $shipping['first_name'],
                'shipping_last_name' => $shipping['last_name'],
                'shipping_company' => $shipping['company'],
                'shipping_address_line1' => $shipping['address_line1'],
                'shipping_address_line2' => $shipping['address_line2'],
                'shipping_postal_code' => $shipping['postal_code'],
                'shipping_city' => $shipping['city'],
                'shipping_country_code' => $shipping['country_code'],
                'shipping_point' =>
                    ($data['shipping_point'] ?? null) ?: null,

                'customer_note' => $data['customer_note'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($preparedItems as $prepared) {
                $variant = $prepared['variant'];

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'sku_snapshot' => $variant->sku,
                    'product_name_snapshot' =>
                        $prepared['translation']->name,
                    'variant_name_snapshot' => $variant->name,
                    'quantity' => $prepared['quantity'],
                    'unit_price_gross' => $this->centsToMoney(
                        $prepared['unit_price_cents']
                    ),
                    'vat_rate' => $variant->vat_rate,
                    'line_total_gross' => $this->centsToMoney(
                        $prepared['line_total_cents']
                    ),
                    'currency' => $variant->currency,
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
                'country_code' => strtoupper(
                    $data['billing_country_code']
                ),
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
            'country_code' => strtoupper(
                $data['shipping_country_code']
            ),
        ];
    }

    private function generateNumber(): string
    {
        do {
            $number = sprintf(
                'ORD-%s-%s',
                now()->format('Ymd'),
                strtoupper(Str::random(6))
            );
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    private function moneyToCents(string $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function centsToMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
