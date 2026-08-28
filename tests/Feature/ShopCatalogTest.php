<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function editor(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);
    }

    private function category(): ProductCategory
    {
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
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'en',
            'name' => 'Anaglyph glasses',
            'slug' => 'anaglyph-glasses',
            'description' => 'Test category.',
            'translation_status' => CatalogTranslationStatus::Ready,
        ]);

        return $category;
    }

    public function test_editor_cannot_access_shop_catalog_admin(): void
    {
        $this->actingAs($this->editor())
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_admin_can_create_multilingual_product_category(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/product-categories', [
                'source_locale' => 'pl',
                'is_active' => '1',
                'sort_order' => 10,
                'translations' => [
                    'pl' => [
                        'name' => 'Folie lentikularne',
                        'slug' => '',
                        'description' => 'Folie do druku lentikularnego.',
                    ],
                    'en' => [
                        'name' => 'Lenticular sheets',
                        'slug' => '',
                        'description' => 'Sheets for lenticular printing.',
                        'translation_status' => CatalogTranslationStatus::Ready->value,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_category_translations', [
            'locale' => 'pl',
            'slug' => 'folie-lentikularne',
            'translation_status' => CatalogTranslationStatus::Source->value,
        ]);

        $this->assertDatabaseHas('product_category_translations', [
            'locale' => 'en',
            'slug' => 'lenticular-sheets',
            'translation_status' => CatalogTranslationStatus::Ready->value,
        ]);
    }

    public function test_admin_can_create_product_with_two_variants(): void
    {
        $admin = $this->admin();
        $category = $this->category();

        $response = $this->actingAs($admin)
            ->post('/admin/products', [
                'category_id' => $category->id,
                'source_locale' => 'pl',
                'status' => ProductStatus::Active->value,
                'brand' => 'Elverre',
                'is_featured' => '1',
                'translations' => [
                    'pl' => [
                        'name' => 'Okulary czerwono-cyjanowe',
                        'slug' => 'okulary-czerwono-cyjanowe',
                        'short_description' => 'Klasyczne okulary 3D.',
                        'description_html' => '<p>Opis produktu PL.</p>',
                    ],
                    'en' => [
                        'name' => 'Red cyan 3D glasses',
                        'slug' => 'red-cyan-3d-glasses',
                        'short_description' => 'Classic 3D glasses.',
                        'description_html' => '<p>Product description EN.</p>',
                        'translation_status' => CatalogTranslationStatus::Ready->value,
                    ],
                ],
                'variants' => [
                    [
                        'sku' => '3D-RC-001',
                        'name' => 'Standard',
                        'price_gross' => '9.99',
                        'vat_rate' => '23',
                        'currency' => 'PLN',
                        'stock_quantity' => 100,
                        'track_stock' => '1',
                        'is_active' => '1',
                        'sort_order' => 0,
                    ],
                    [
                        'sku' => '3D-RC-010',
                        'name' => 'Pakiet 10 szt.',
                        'price_gross' => '79.90',
                        'vat_rate' => '23',
                        'currency' => 'PLN',
                        'stock_quantity' => 20,
                        'track_stock' => '1',
                        'is_active' => '1',
                        'sort_order' => 1,
                    ],
                ],
            ]);

        $product = Product::query()->firstOrFail();

        $response->assertRedirect(
            route('admin.products.edit', $product)
        );

        $this->assertDatabaseCount('product_variants', 2);

        $this->assertDatabaseHas('product_translations', [
            'product_id' => $product->id,
            'locale' => 'pl',
            'slug' => 'okulary-czerwono-cyjanowe',
            'translation_status' => CatalogTranslationStatus::Source->value,
        ]);

        $this->assertDatabaseHas('product_translations', [
            'product_id' => $product->id,
            'locale' => 'en',
            'slug' => 'red-cyan-3d-glasses',
            'translation_status' => CatalogTranslationStatus::Ready->value,
        ]);
    }

    public function test_public_shop_displays_localized_active_product(): void
    {
        $admin = $this->admin();
        $category = $this->category();

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
            'name' => 'Testowe okulary 3D',
            'slug' => 'testowe-okulary-3d',
            'short_description' => 'Opis PL',
            'description_html' => '<p>Treść PL</p>',
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'Test 3D glasses',
            'slug' => 'test-3d-glasses',
            'short_description' => 'EN description',
            'description_html' => '<p>EN content</p>',
            'translation_status' => CatalogTranslationStatus::Ready,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-001',
            'name' => 'Standard',
            'price_gross' => 19.99,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 5,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->get('/pl/shop')
            ->assertOk()
            ->assertSee('Testowe okulary 3D')
            ->assertSee('19,99 PLN');

        $this->get('/en/shop/test-3d-glasses')
            ->assertOk()
            ->assertSee('Test 3D glasses')
            ->assertSee('EN content', false);
    }

    public function test_draft_product_translation_is_not_public(): void
    {
        $admin = $this->admin();
        $category = $this->category();

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Active,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'Hidden product',
            'slug' => 'hidden-product',
            'description_html' => '<p>Hidden</p>',
            'translation_status' => CatalogTranslationStatus::Draft,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HIDDEN-001',
            'price_gross' => 10,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 1,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->get('/en/shop/hidden-product')
            ->assertNotFound();
    }

    public function test_media_used_by_product_cannot_be_deleted(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $category = $this->category();

        Storage::disk('public')->put(
            'media/test/product.jpg',
            'image'
        );

        $media = MediaAsset::create([
            'disk' => 'public',
            'path' => 'media/test/product.jpg',
            'original_name' => 'product.jpg',
            'stored_name' => 'product.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'folder' => 'products',
            'uploaded_by' => $admin->id,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Draft,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $product->media()->attach($media->id, [
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->delete('/admin/media/' . $media->id)
            ->assertSessionHasErrors('media_delete');

        $this->assertDatabaseHas('media_assets', [
            'id' => $media->id,
        ]);
    }
}
