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
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\CurrencySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MultiCurrencyCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function seedRates(): void
    {
        foreach (
            [
                'EUR' => '4.00000000',
                'GBP' => '5.00000000',
                'USD' => '3.20000000',
            ]
            as $code => $rate
        ) {
            $currency = Currency::query()
                ->where('code', $code)
                ->firstOrFail();

            CurrencyRate::create([
                'currency_id' => $currency->id,
                'rate_to_base' => $rate,
                'effective_date' => '2026-08-31',
                'source' => 'nbp',
                'is_manual' => false,
                'fetched_at' => now(),
            ]);
        }
    }

    private function variant(float $price = 100.00): ProductVariant
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $category = ProductCategory::create([
            'source_locale' => 'pl',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'pl',
            'name' => 'Okulary 3D',
            'slug' => 'okulary-3d',
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Active,
            'brand' => 'Elverre',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'pl',
            'name' => 'Okulary testowe',
            'slug' => 'okulary-testowe',
            'description_html' => '<p>Opis.</p>',
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CUR-ORDER-001',
            'name' => 'Standard',
            'price_gross' => $price,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 10,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_email' => 'buyer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'customer_phone' => '123456789',

            'billing_company' => '',
            'billing_tax_id' => '',
            'billing_address_line1' => 'ul. Testowa 1',
            'billing_address_line2' => '',
            'billing_postal_code' => '87-100',
            'billing_city' => 'Toruń',
            'billing_country_code' => 'PL',

            'shipping_same_as_billing' => '1',
            'shipping_first_name' => '',
            'shipping_last_name' => '',
            'shipping_company' => '',
            'shipping_address_line1' => '',
            'shipping_address_line2' => '',
            'shipping_postal_code' => '',
            'shipping_city' => '',
            'shipping_country_code' => '',

            'shipping_method' => 'courier',
            'shipping_point' => '',
            'payment_method' => 'bank_transfer',
            'customer_note' => '',
            'accept_terms' => '1',
        ], $overrides);
    }

    public function test_conversion_margin_defaults_to_five_percent(): void
    {
        $this->assertSame(
            '5.00',
            app(CurrencySettingsService::class)->markupPercent()
        );
    }

    public function test_cart_and_shipping_use_selected_currency_with_margin(): void
    {
        $this->seedRates();
        $variant = $this->variant(100.00);

        $this->withSession([
            CurrencyService::SESSION_KEY => 'EUR',
        ])->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->withSession([
            CurrencyService::SESSION_KEY => 'EUR',
        ])->get('/pl/cart')
            ->assertOk()
            ->assertSee('26,25 EUR');

        /*
         * Courier = 18.99 PLN.
         * 18.99 / 4.00 * 1.05 = 4.984875 -> 4.98 EUR.
         */
        $this->withSession([
            CurrencyService::SESSION_KEY => 'EUR',
        ])->get('/pl/checkout')
            ->assertOk()
            ->assertSee('26,25 EUR')
            ->assertSee('4,98 EUR');
    }

    public function test_foreign_currency_order_freezes_rate_margin_and_base_values(): void
    {
        $this->seedRates();
        $variant = $this->variant(100.00);

        $this->withSession([
            CurrencyService::SESSION_KEY => 'EUR',
        ])->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->withSession([
            CurrencyService::SESSION_KEY => 'EUR',
        ])->post(
            '/pl/checkout',
            $this->payload()
        );

        $order = Order::query()
            ->with('items')
            ->firstOrFail();

        $response->assertRedirect(
            '/pl/order/' . $order->public_token
        );

        $this->assertSame('EUR', $order->currency);
        $this->assertSame('PLN', $order->base_currency);
        $this->assertSame('4.00000000', $order->exchange_rate);
        $this->assertSame('nbp', $order->exchange_rate_source);
        $this->assertSame(
            '2026-08-31',
            $order->exchange_rate_effective_date?->toDateString()
        );
        $this->assertSame('5.00', $order->currency_markup_percent);

        $this->assertSame('100.00', $order->subtotal_base_gross);
        $this->assertSame('18.99', $order->shipping_base_gross);
        $this->assertSame('118.99', $order->total_base_gross);

        $this->assertSame('26.25', $order->subtotal_gross);
        $this->assertSame('4.98', $order->shipping_gross);
        $this->assertSame('31.23', $order->total_gross);

        $item = $order->items->firstOrFail();

        $this->assertSame('EUR', $item->currency);
        $this->assertSame('PLN', $item->base_currency);
        $this->assertSame('100.00', $item->base_unit_price_gross);
        $this->assertSame('26.25', $item->unit_price_gross);
        $this->assertSame('100.00', $item->base_line_total_gross);
        $this->assertSame('26.25', $item->line_total_gross);
    }

    public function test_bank_transfer_is_available_for_gbp_and_usd(): void
    {
        $this->seedRates();
        $variant = $this->variant(100.00);

        foreach (['GBP', 'USD'] as $currency) {
            $this->flushSession();

            $this->withSession([
                CurrencyService::SESSION_KEY => $currency,
            ])->post('/pl/cart/items', [
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

            $this->withSession([
                CurrencyService::SESSION_KEY => $currency,
            ])->get('/pl/checkout')
                ->assertOk()
                ->assertSee('Przelew tradycyjny');
        }
    }
}
