<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\ShippingCountry;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DynamicShippingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function variant(
        int $weightGrams = 1200,
        float $price = 100.00
    ): ProductVariant {
        $category =
            ProductCategory::create([
                'source_locale' => 'pl',
                'is_active' => true,
                'sort_order' => 0,
            ]);

        ProductCategoryTranslation::create([
            'product_category_id' =>
                $category->id,
            'locale' => 'pl',
            'name' => 'Okulary 3D',
            'slug' => 'shipping-test',
            'translation_status' =>
                CatalogTranslationStatus
                    ::Source,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Active,
            'brand' => 'Elverre',
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'pl',
            'name' => 'Produkt wysyłkowy',
            'slug' => 'produkt-wysylkowy',
            'description_html' =>
                '<p>Opis.</p>',
            'translation_status' =>
                CatalogTranslationStatus
                    ::Source,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SHIP-DYN-001',
            'name' => 'Standard',
            'price_gross' => $price,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 10,
            'weight_grams' => $weightGrams,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function enableGermany(
        string $price = '40.00',
        int $from = 1001,
        int $to = 5000
    ): ShippingRate {
        $country = ShippingCountry::query()
            ->where('code', 'DE')
            ->firstOrFail();

        $country->update([
            'is_enabled' => true,
        ]);

        $method = ShippingMethod::query()
            ->where('code', 'courier')
            ->firstOrFail();

        return ShippingRate::create([
            'shipping_country_id' =>
                $country->id,
            'shipping_method_id' =>
                $method->id,
            'weight_from_grams' => $from,
            'weight_to_grams' => $to,
            'price_pln' => $price,
            'is_enabled' => true,
        ]);
    }

    private function seedEurRate(): void
    {
        $eur = Currency::query()
            ->where('code', 'EUR')
            ->firstOrFail();

        CurrencyRate::create([
            'currency_id' => $eur->id,
            'rate_to_base' => '4.00000000',
            'effective_date' => '2026-09-01',
            'source' => 'nbp',
            'is_manual' => false,
            'fetched_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        array $overrides = []
    ): array {
        return array_merge([
            'customer_email' =>
                'buyer@example.com',
            'customer_first_name' =>
                'Jan',
            'customer_last_name' =>
                'Kowalski',
            'customer_phone' =>
                '123456789',

            'billing_company' => '',
            'billing_tax_id' => '',
            'billing_address_line1' =>
                'Teststrasse 1',
            'billing_address_line2' =>
                '',
            'billing_postal_code' =>
                '10115',
            'billing_city' => 'Berlin',
            'billing_country_code' =>
                'DE',

            'shipping_country_code' =>
                'DE',
            'shipping_same_as_billing' =>
                '1',
            'shipping_first_name' => '',
            'shipping_last_name' => '',
            'shipping_company' => '',
            'shipping_address_line1' =>
                '',
            'shipping_address_line2' =>
                '',
            'shipping_postal_code' => '',
            'shipping_city' => '',

            'shipping_method' =>
                'courier',
            'shipping_point' => '',
            'payment_method' =>
                'bank_transfer',
            'customer_note' => '',
            'accept_terms' => '1',
        ], $overrides);
    }

    public function test_checkout_lists_only_admin_enabled_shipping_countries(): void
    {
        $variant = $this->variant();

        ShippingCountry::query()
            ->where('code', 'DE')
            ->update([
                'is_enabled' => true,
            ]);

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->get('/pl/checkout')
            ->assertOk()
            ->assertSee(
                'value="PL"',
                false
            )
            ->assertSee(
                'value="DE"',
                false
            )
            ->assertDontSee(
                'value="FR"',
                false
            );
    }

    public function test_shipping_quote_uses_cart_weight_country_and_logistics_margin(): void
    {
        $this->seedEurRate();
        $this->enableGermany();

        $variant = $this->variant(
            1200,
            100.00
        );

        $this->withSession([
            CurrencyService::SESSION_KEY =>
                'EUR',
        ])->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->withSession([
            CurrencyService::SESSION_KEY =>
                'EUR',
        ])->getJson(
            '/pl/checkout/shipping-options?country=DE'
        )
            ->assertOk()
            ->assertJsonPath(
                'weight_grams',
                1200
            )
            ->assertJsonPath(
                'currency',
                'EUR'
            )
            ->assertJsonPath(
                'methods.0.key',
                'courier'
            )
            ->assertJsonPath(
                'methods.0.price_cents',
                1155
            )
            ->assertJsonPath(
                'methods.0.formatted_price',
                '11,55 EUR'
            );
    }

    public function test_order_freezes_dynamic_shipping_snapshot(): void
    {
        $this->seedEurRate();
        $rate = $this->enableGermany();

        $variant = $this->variant(
            1200,
            100.00
        );

        $this->withSession([
            CurrencyService::SESSION_KEY =>
                'EUR',
        ])->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->withSession([
            CurrencyService::SESSION_KEY =>
                'EUR',
        ])->post(
            '/pl/checkout',
            $this->payload()
        );

        $order = Order::query()
            ->firstOrFail();

        $response->assertRedirect(
            '/pl/order/'
            . $order->public_token
        );

        $this->assertSame(
            $rate->id,
            $order->shipping_rate_id
        );
        $this->assertSame(
            1200,
            $order->shipping_weight_grams
        );
        $this->assertSame(
            'DE',
            $order->shipping_country_code
        );
        $this->assertSame(
            'Niemcy',
            $order
                ->shipping_country_name_snapshot
        );
        $this->assertSame(
            '40.00',
            $order
                ->shipping_base_before_margin
        );
        $this->assertSame(
            '10.00',
            $order
                ->shipping_logistics_margin_percent
        );
        $this->assertSame(
            '44.00',
            $order->shipping_base_gross
        );
        $this->assertSame(
            '11.55',
            $order->shipping_gross
        );
        $this->assertSame(
            '144.00',
            $order->total_base_gross
        );
        $this->assertSame(
            '37.80',
            $order->total_gross
        );
    }

    public function test_inactive_country_is_rejected(): void
    {
        $variant = $this->variant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post(
            '/pl/checkout',
            $this->payload([
                'billing_country_code' =>
                    'FR',
                'shipping_country_code' =>
                    'FR',
            ])
        )
            ->assertSessionHasErrors(
                'shipping_country_code'
            );

        $this->assertDatabaseCount(
            'orders',
            0
        );
    }

    public function test_country_without_matching_weight_rate_cannot_be_ordered(): void
    {
        $this->enableGermany(
            '39.00',
            0,
            1000
        );

        $variant = $this->variant(
            1200
        );

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post(
            '/pl/checkout',
            $this->payload()
        )
            ->assertSessionHasErrors(
                'shipping_method'
            );

        $this->assertDatabaseCount(
            'orders',
            0
        );
    }

    public function test_missing_product_weight_blocks_checkout(): void
    {
        $variant = $this->variant(
            1200
        );

        $variant->update([
            'weight_grams' => null,
        ]);

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->get('/pl/checkout')
            ->assertRedirect('/pl/cart')
            ->assertSessionHasErrors(
                'cart'
            );
    }
}
