<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\User;
use App\Services\AiTranslationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductAiActionsTest extends TestCase
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

    private function product(
        User $admin,
        bool $withSeo = false
    ): Product {
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

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Active,
            'brand' => 'Elvenica',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'pl',
            'name' => 'Kartonowe okulary anaglifowe',
            'slug' => 'kartonowe-okulary-anaglifowe',
            'short_description' =>
                'Okulary czerwono-cyjanowe w kartonowej oprawce.',
            'description_html' =>
                '<p>Okulary do oglądania obrazów anaglifowych 3D.</p>',
            'seo_title' => $withSeo
                ? 'Kartonowe okulary anaglifowe 3D'
                : null,
            'seo_description' => $withSeo
                ? 'Kartonowe okulary anaglifowe czerwono-cyjanowe do obrazów 3D.'
                : null,
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        return $product;
    }

    private function configureOpenAi(): void
    {
        $settings = app(
            AiTranslationSettingsService::class
        );

        $settings->set('enabled', '1');
        $settings->set('provider', 'openai');
        $settings->set('openai.model', 'gpt-test');
        $settings->set(
            'openai.api_key',
            'test-key',
            true
        );
    }

    public function test_product_list_contains_compact_ai_actions(): void
    {
        $admin = $this->admin();
        $product = $this->product($admin);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Autouzupełnianie SEO')
            ->assertSee('Autotranslator')
            ->assertSee(
                route(
                    'admin.translations.translate',
                    [
                        'type' => 'product_seo',
                        'id' => $product->id,
                    ]
                ),
                false
            );
    }

    public function test_admin_can_auto_fill_missing_product_seo(): void
    {
        $admin = $this->admin();
        $product = $this->product($admin);

        $this->configureOpenAi();

        Http::fake([
            'https://api.openai.com/*' =>
                Http::response([
                    'output' => [
                        [
                            'content' => [
                                [
                                    'type' =>
                                        'output_text',
                                    'text' => json_encode([
                                        'seo_title' =>
                                            'Kartonowe okulary anaglifowe 3D',
                                        'seo_description' =>
                                            'Kartonowe okulary anaglifowe czerwono-cyjanowe do oglądania obrazów 3D.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                    'usage' => [
                        'input_tokens' => 120,
                        'output_tokens' => 30,
                        'total_tokens' => 150,
                    ],
                ], 200),
        ]);

        $this->actingAs($admin)
            ->post(
                '/admin/translations/product_seo/'
                . $product->id
            )
            ->assertSessionHasNoErrors();

        $translation = $product
            ->fresh()
            ->sourceTranslation();

        $this->assertSame(
            'Kartonowe okulary anaglifowe 3D',
            $translation?->seo_title
        );

        $this->assertSame(
            'Kartonowe okulary anaglifowe czerwono-cyjanowe do oglądania obrazów 3D.',
            $translation?->seo_description
        );

        $this->assertDatabaseHas(
            'ai_translation_runs',
            [
                'content_type' => 'product',
                'content_id' => $product->id,
                'source_locale' => 'pl',
                'target_locale' => 'pl',
                'status' => 'success',
                'total_tokens' => 150,
            ]
        );
    }

    public function test_auto_fill_does_not_overwrite_complete_seo(): void
    {
        $admin = $this->admin();
        $product = $this->product(
            $admin,
            true
        );

        $this->configureOpenAi();

        Http::fake();

        $this->actingAs($admin)
            ->post(
                '/admin/translations/product_seo/'
                . $product->id
            )
            ->assertSessionHasErrors('product_ai');

        Http::assertNothingSent();

        $translation = $product
            ->fresh()
            ->sourceTranslation();

        $this->assertSame(
            'Kartonowe okulary anaglifowe 3D',
            $translation?->seo_title
        );
    }

    public function test_editor_cannot_run_product_seo_action(): void
    {
        $admin = $this->admin();
        $product = $this->product($admin);

        $this->actingAs($this->editor())
            ->post(
                '/admin/translations/product_seo/'
                . $product->id
            )
            ->assertForbidden();
    }
}
