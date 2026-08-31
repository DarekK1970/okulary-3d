<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\CurrencySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyFrontendTest extends TestCase
{
    use RefreshDatabase;

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

    private function product(
        float $price = 100.00
    ): Product {
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
            'name' => 'Okulary anaglifowe',
            'slug' => 'okulary-anaglifowe',
            'description' => 'Kategoria testowa.',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'en',
            'name' => 'Anaglyph glasses',
            'slug' => 'anaglyph-glasses',
            'description' => 'Test category.',
            'translation_status' =>
                CatalogTranslationStatus::Ready,
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
            'short_description' => 'Opis testowy.',
            'description_html' => '<p>Treść.</p>',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'Test glasses',
            'slug' => 'test-glasses',
            'short_description' => 'Test.',
            'description_html' => '<p>Content.</p>',
            'translation_status' =>
                CatalogTranslationStatus::Ready,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CUR-001',
            'name' => 'Standard',
            'price_gross' => $price,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 10,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $product;
    }

    public function test_header_lists_enabled_currencies_with_rates(): void
    {
        $this->seedRates();

        $this->get('/pl')
            ->assertOk()
            ->assertSee('PLN')
            ->assertSee('EUR')
            ->assertSee('GBP')
            ->assertSee('USD');
    }

    public function test_user_can_change_currency_and_it_is_persisted(): void
    {
        $this->seedRates();

        $response = $this
            ->from('/pl/shop')
            ->post('/pl/currency', [
                'currency' => 'EUR',
            ]);

        $response
            ->assertRedirect('/pl/shop')
            ->assertSessionHas(
                CurrencyService::SESSION_KEY,
                'EUR'
            )
            ->assertCookie(
                CurrencyService::COOKIE_KEY,
                'EUR'
            );
    }

    public function test_shop_prices_are_converted_to_selected_currency(): void
    {
        $this->seedRates();
        $this->product(100.00);

        $this
            ->withSession([
                CurrencyService::SESSION_KEY =>
                    'EUR',
            ])
            ->get('/pl/shop')
            ->assertOk()
            ->assertSee('26,25 EUR')
            ->assertDontSee('100,00 PLN');
    }

    public function test_product_variant_prices_are_converted(): void
    {
        $this->seedRates();
        $this->product(100.00);

        $this
            ->withSession([
                CurrencyService::SESSION_KEY =>
                    'GBP',
            ])
            ->get('/pl/shop/okulary-testowe')
            ->assertOk()
            ->assertSee('21,00 GBP')
            ->assertSee(
                'NBP',
                false
            )
            ->assertSee(
                '2026-08-31',
                false
            );
    }

    public function test_currency_markup_is_applied_to_foreign_display_price(): void
    {
        $this->seedRates();

        app(CurrencySettingsService::class)
            ->set(
                'markup_percent',
                '2.00'
            );

        $this->product(100.00);

        $this
            ->withSession([
                CurrencyService::SESSION_KEY =>
                    'EUR',
            ])
            ->get('/pl/shop')
            ->assertOk()
            ->assertSee('25,50 EUR');
    }

    public function test_currency_without_rate_is_not_selectable(): void
    {
        $eur = Currency::query()
            ->where('code', 'EUR')
            ->firstOrFail();

        $this->assertNull(
            CurrencyRate::query()
                ->where(
                    'currency_id',
                    $eur->id
                )
                ->first()
        );

        $this
            ->from('/pl/shop')
            ->post('/pl/currency', [
                'currency' => 'EUR',
            ])
            ->assertSessionHasErrors(
                'currency'
            );
    }
}
