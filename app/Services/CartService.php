<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'shop_cart';

    public function __construct(
        private readonly CurrencyService $currencies
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function entries(): array
    {
        $entries = session()->get(self::SESSION_KEY, []);

        return is_array($entries) ? $entries : [];
    }

    public function count(): int
    {
        return array_sum($this->entries());
    }

    public function add(
        ProductVariant $variant,
        int $quantity,
        string $locale
    ): void {
        $this->assertPurchasable($variant, $quantity, $locale);

        $entries = $this->entries();
        $existingQuantity = (int) ($entries[$variant->id] ?? 0);
        $newQuantity = $existingQuantity + $quantity;

        $this->assertStock($variant, $newQuantity);
        $this->assertCurrencyCompatible($variant);

        $entries[$variant->id] = $newQuantity;
        session()->put(self::SESSION_KEY, $entries);
    }

    public function update(
        ProductVariant $variant,
        int $quantity,
        string $locale
    ): void {
        if ($quantity <= 0) {
            $this->remove($variant->id);
            return;
        }

        $this->assertPurchasable($variant, $quantity, $locale);

        $entries = $this->entries();
        $entries[$variant->id] = $quantity;
        session()->put(self::SESSION_KEY, $entries);
    }

    public function remove(int $variantId): void
    {
        $entries = $this->entries();
        unset($entries[$variantId]);
        session()->put(self::SESSION_KEY, $entries);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function resolvedItems(string $locale): Collection
    {
        $entries = $this->entries();

        if ($entries === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->whereIn('id', array_keys($entries))
            ->with([
                'product.translations',
                'product.media',
                'product.category',
            ])
            ->get()
            ->keyBy('id');

        $pricing = $this->currencies->pricingSnapshot();

        $resolved = collect();
        $cleanEntries = [];

        foreach ($entries as $variantId => $quantity) {
            $variant = $variants->get((int) $variantId);

            if (! $variant) {
                continue;
            }

            try {
                $this->assertPurchasable(
                    $variant,
                    (int) $quantity,
                    $locale
                );
            } catch (ValidationException) {
                continue;
            }

            $translation = $variant->product->publicTranslation($locale);

            if (! $translation) {
                continue;
            }

            $quantity = (int) $quantity;

            $baseUnitPriceCents = $this->currencies->toBaseCents(
                (string) $variant->price_gross,
                $variant->currency
            );

            $unitPriceCents = $this->currencies->convertBaseCentsWithSnapshot(
                $baseUnitPriceCents,
                $pricing
            );

            $cleanEntries[$variant->id] = $quantity;

            $resolved->push([
                'variant' => $variant,
                'product' => $variant->product,
                'translation' => $translation,
                'media' => $variant->product->primaryMedia(),
                'quantity' => $quantity,

                'base_currency' => $pricing['base_currency'],
                'unit_price_base_cents' => $baseUnitPriceCents,
                'line_total_base_cents' => $baseUnitPriceCents * $quantity,

                'unit_price_cents' => $unitPriceCents,
                'line_total_cents' => $unitPriceCents * $quantity,
                'currency' => $pricing['currency'],
                'source_currency' => $variant->currency,
            ]);
        }

        if ($cleanEntries !== $entries) {
            session()->put(self::SESSION_KEY, $cleanEntries);
        }

        return $resolved;
    }

    public function subtotalCents(string $locale): int
    {
        return (int) $this->resolvedItems($locale)
            ->sum('line_total_cents');
    }

    public function subtotalBaseCents(string $locale): int
    {
        return (int) $this->resolvedItems($locale)
            ->sum('line_total_base_cents');
    }

    public function currency(string $locale): ?string
    {
        $first = $this->resolvedItems($locale)->first();

        return $first['currency'] ?? null;
    }

    private function assertPurchasable(
        ProductVariant $variant,
        int $quantity,
        string $locale
    ): void {
        $variant->loadMissing([
            'product.translations',
            'product.category',
        ]);

        $product = $variant->product;

        if (
            ! $variant->is_active
            || ! $product
            || $product->status !== ProductStatus::Active
            || ! $product->category
            || ! $product->category->is_active
            || ! $product->publicTranslation($locale)
        ) {
            throw ValidationException::withMessages([
                'cart' => __('cart.messages.unavailable'),
            ]);
        }

        $this->assertStock($variant, $quantity);
    }

    private function assertStock(
        ProductVariant $variant,
        int $quantity
    ): void {
        if ($variant->track_stock && $quantity > $variant->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.messages.insufficient_stock'),
            ]);
        }
    }

    private function assertCurrencyCompatible(ProductVariant $variant): void
    {
        $entries = $this->entries();

        if ($entries === []) {
            return;
        }

        $existingCurrency = ProductVariant::query()
            ->whereIn('id', array_keys($entries))
            ->where('is_active', true)
            ->value('currency');

        if ($existingCurrency && $existingCurrency !== $variant->currency) {
            throw ValidationException::withMessages([
                'cart' => __('cart.messages.mixed_currency'),
            ]);
        }
    }
}
