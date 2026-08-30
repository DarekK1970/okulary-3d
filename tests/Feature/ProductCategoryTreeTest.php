<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function category(
        string $name,
        string $slug,
        ?ProductCategory $parent = null,
        int $sortOrder = 0
    ): ProductCategory {
        $category = ProductCategory::create([
            'parent_id' => $parent?->id,
            'source_locale' => 'pl',
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'pl',
            'name' => $name,
            'slug' => $slug,
            'description' => 'Opis testowy.',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'en',
            'name' => $name . ' EN',
            'slug' => $slug . '-en',
            'description' => 'Test description.',
            'translation_status' =>
                CatalogTranslationStatus::Ready,
        ]);

        return $category;
    }

    private function product(
        User $admin,
        ProductCategory $category,
        string $name,
        string $slug,
        string $sku
    ): Product {
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
            'name' => $name,
            'slug' => $slug,
            'short_description' => 'Produkt testowy.',
            'description_html' => '<p>Opis produktu.</p>',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'name' => 'Standard',
            'price_gross' => 10,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 10,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $product;
    }

    public function test_admin_can_create_child_category(): void
    {
        $parent = $this->category(
            'Materiały',
            'materialy'
        );

        $this->actingAs($this->admin())
            ->post('/admin/product-categories', [
                'parent_id' => $parent->id,
                'source_locale' => 'pl',
                'is_active' => '1',
                'sort_order' => 10,
                'translations' => [
                    'pl' => [
                        'name' => 'Folie',
                        'slug' => 'folie',
                        'description' => 'Folie do optyki 3D.',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $child = ProductCategory::query()
            ->where('parent_id', $parent->id)
            ->firstOrFail();

        $this->assertSame(
            'Folie',
            $child->sourceTranslation()?->name
        );
    }

    public function test_admin_cannot_create_category_cycle(): void
    {
        $root = $this->category(
            'Materiały',
            'materialy'
        );

        $child = $this->category(
            'Folie',
            'folie',
            $root
        );

        $translation = $root->sourceTranslation();

        $this->actingAs($this->admin())
            ->put(
                '/admin/product-categories/' . $root->id,
                [
                    'parent_id' => $child->id,
                    'source_locale' => 'pl',
                    'is_active' => '1',
                    'sort_order' => 0,
                    'translations' => [
                        'pl' => [
                            'name' => $translation?->name,
                            'slug' => $translation?->slug,
                            'description' =>
                                $translation?->description,
                        ],
                    ],
                ]
            )
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_parent_filter_includes_products_from_descendants(): void
    {
        $admin = $this->admin();

        $materials = $this->category(
            'Materiały',
            'materialy',
            null,
            10
        );

        $films = $this->category(
            'Folie',
            'folie',
            $materials,
            20
        );

        $glasses = $this->category(
            'Okulary',
            'okulary',
            null,
            30
        );

        $this->product(
            $admin,
            $films,
            'Folia polaryzacyjna',
            'folia-polaryzacyjna',
            'FILM-001'
        );

        $this->product(
            $admin,
            $glasses,
            'Okulary testowe',
            'okulary-testowe',
            'GLASS-001'
        );

        $this->get('/pl/sklep/materialy')
            ->assertOk()
            ->assertSee('Folia polaryzacyjna')
            ->assertDontSee('Okulary testowe');
    }

    public function test_product_breadcrumb_contains_full_category_path(): void
    {
        $admin = $this->admin();

        $materials = $this->category(
            'Materiały',
            'materialy',
            null,
            10
        );

        $films = $this->category(
            'Folie',
            'folie',
            $materials,
            20
        );

        $this->product(
            $admin,
            $films,
            'Folia polaryzacyjna',
            'folia-polaryzacyjna',
            'FILM-002'
        );

        $this->get('/pl/shop/folia-polaryzacyjna')
            ->assertOk()
            ->assertSeeInOrder([
                'Sklep 3D',
                'Materiały',
                'Folie',
                'Folia polaryzacyjna',
            ]);
    }
}
