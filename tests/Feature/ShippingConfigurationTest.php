<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductVariant;
use App\Models\ShippingCountry;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\ShippingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_shipping_defaults_are_seeded(): void
    {
        $this->assertDatabaseHas(
            'shipping_countries',
            [
                'code' => 'PL',
                'is_enabled' => true,
                'is_default' => true,
            ]
        );

        $this->assertDatabaseHas(
            'shipping_countries',
            [
                'code' => 'DE',
                'is_enabled' => false,
            ]
        );

        $this->assertDatabaseHas(
            'shipping_methods',
            [
                'code' => 'courier',
                'is_enabled' => true,
            ]
        );

        $this->assertSame(
            '10.00',
            app(
                ShippingSettingsService::class
            )->logisticsMarginPercent()
        );
    }

    public function test_admin_can_open_shipping_configuration(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/shipping')
            ->assertOk()
            ->assertSee(
                'Dostawy i cenniki wysyłek'
            )
            ->assertSee(
                'Marża logistyczna'
            )
            ->assertSee('Niemcy');
    }

    public function test_editor_cannot_manage_shipping(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/admin/shipping')
            ->assertForbidden();
    }

    public function test_admin_can_enable_country_and_change_margin(): void
    {
        $methods = ShippingMethod::query()
            ->pluck('id')
            ->all();

        $this->actingAs($this->admin())
            ->put(
                '/admin/shipping/settings',
                [
                    'logistics_margin_percent' =>
                        '12.50',
                    'countries' => [
                        'PL',
                        'DE',
                    ],
                    'methods' => $methods,
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertDatabaseHas(
            'shipping_countries',
            [
                'code' => 'DE',
                'is_enabled' => true,
            ]
        );

        $this->assertDatabaseHas(
            'shipping_countries',
            [
                'code' => 'PL',
                'is_enabled' => true,
                'is_default' => true,
            ]
        );

        $this->assertSame(
            '12.50',
            app(
                ShippingSettingsService::class
            )->logisticsMarginPercent()
        );
    }

    public function test_logistics_margin_applies_only_outside_poland(): void
    {
        $service = app(
            ShippingSettingsService::class
        );

        $this->assertSame(
            4000,
            $service
                ->applyLogisticsMarginCents(
                    4000,
                    'PL'
                )
        );

        $this->assertSame(
            4400,
            $service
                ->applyLogisticsMarginCents(
                    4000,
                    'DE'
                )
        );
    }

    public function test_admin_can_create_non_overlapping_weight_rate(): void
    {
        $germany = ShippingCountry::query()
            ->where('code', 'DE')
            ->firstOrFail();

        $courier = ShippingMethod::query()
            ->where('code', 'courier')
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->post(
                '/admin/shipping/rates',
                [
                    'shipping_country_id' =>
                        $germany->id,
                    'shipping_method_id' =>
                        $courier->id,
                    'weight_from_kg' =>
                        '0.000',
                    'weight_to_kg' =>
                        '1.000',
                    'price_pln' =>
                        '39.00',
                    'is_enabled' => '1',
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'shipping_rates',
            [
                'shipping_country_id' =>
                    $germany->id,
                'shipping_method_id' =>
                    $courier->id,
                'weight_from_grams' => 0,
                'weight_to_grams' => 1000,
                'price_pln' => '39.00',
            ]
        );
    }

    public function test_overlapping_weight_rate_is_rejected(): void
    {
        $germany = ShippingCountry::query()
            ->where('code', 'DE')
            ->firstOrFail();

        $courier = ShippingMethod::query()
            ->where('code', 'courier')
            ->firstOrFail();

        ShippingRate::create([
            'shipping_country_id' =>
                $germany->id,
            'shipping_method_id' =>
                $courier->id,
            'weight_from_grams' => 0,
            'weight_to_grams' => 1000,
            'price_pln' => '39.00',
            'is_enabled' => true,
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/shipping')
            ->post(
                '/admin/shipping/rates',
                [
                    'shipping_country_id' =>
                        $germany->id,
                    'shipping_method_id' =>
                        $courier->id,
                    'weight_from_kg' =>
                        '0.500',
                    'weight_to_kg' =>
                        '2.000',
                    'price_pln' =>
                        '49.00',
                    'is_enabled' => '1',
                ]
            )
            ->assertSessionHasErrors(
                'weight_from_kg'
            );

        $this->assertSame(
            1,
            ShippingRate::query()
                ->where(
                    'shipping_country_id',
                    $germany->id
                )
                ->where(
                    'shipping_method_id',
                    $courier->id
                )
                ->count()
        );
    }

    public function test_admin_can_save_variant_weight(): void
    {
        $user = $this->admin();

        $category = ProductCategory::create([
            'source_locale' => 'pl',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' =>
                $category->id,
            'locale' => 'pl',
            'name' => 'Test',
            'slug' => 'test-shipping',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Draft,
            'brand' => null,
            'is_featured' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'WEIGHT-001',
            'name' => 'Test',
            'price_gross' => '10.00',
            'vat_rate' => '23.00',
            'currency' => 'PLN',
            'stock_quantity' => 10,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->put(
                '/admin/shipping/weights',
                [
                    'weights' => [
                        $variant->id => 275,
                    ],
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'product_variants',
            [
                'id' => $variant->id,
                'weight_grams' => 275,
            ]
        );
    }
}
