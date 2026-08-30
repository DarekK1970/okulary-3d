<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategorySeoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function category(
        string $plName,
        string $plSlug,
        string $enName,
        string $enSlug,
        ?ProductCategory $parent = null
    ): ProductCategory {
        $category = ProductCategory::create([
            'parent_id' => $parent?->id,
            'source_locale' => 'pl',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'pl',
            'name' => $plName,
            'slug' => $plSlug,
            'description' => 'Krótki opis ' . $plName,
            'content_html' => '<h2>Więcej o ' . $plName . '</h2>',
            'seo_title' => $plName . ' — SEO',
            'seo_description' => 'Meta PL ' . $plName,
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'en',
            'name' => $enName,
            'slug' => $enSlug,
            'description' => 'Short description ' . $enName,
            'content_html' => '<h2>More about ' . $enName . '</h2>',
            'seo_title' => $enName . ' — SEO',
            'seo_description' => 'Meta EN ' . $enName,
            'translation_status' =>
                CatalogTranslationStatus::Ready,
        ]);

        return $category;
    }

    public function test_admin_can_save_category_seo_fields(): void
    {
        $category = $this->category(
            'Materiały',
            'materialy',
            'Materials',
            'materials'
        );

        $this->actingAs($this->admin())
            ->put(
                '/admin/product-categories/' . $category->id,
                [
                    'parent_id' => '',
                    'source_locale' => 'pl',
                    'is_active' => '1',
                    'sort_order' => 10,
                    'translations' => [
                        'pl' => [
                            'name' => 'Materiały',
                            'slug' => 'materialy',
                            'description' => 'Nowe intro.',
                            'content_html' => '<p>Nowa treść SEO.</p>',
                            'seo_title' => 'Nowy SEO title',
                            'seo_description' => 'Nowy meta description',
                        ],
                        'en' => [
                            'name' => 'Materials',
                            'slug' => 'materials',
                            'description' => 'New intro.',
                            'content_html' => '<p>New SEO content.</p>',
                            'seo_title' => 'New SEO title EN',
                            'seo_description' => 'New meta description EN',
                            'translation_status' => 'ready',
                        ],
                    ],
                ]
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'product_category_translations',
            [
                'product_category_id' => $category->id,
                'locale' => 'pl',
                'content_html' => '<p>Nowa treść SEO.</p>',
                'seo_title' => 'Nowy SEO title',
                'seo_description' => 'Nowy meta description',
            ]
        );
    }

    public function test_clean_category_urls_render_seo_and_hreflang(): void
    {
        $materials = $this->category(
            'Materiały',
            'materialy',
            'Materials',
            'materials'
        );

        $this->category(
            'Folie',
            'folie',
            'Films',
            'films',
            $materials
        );

        $response = $this->get('/pl/sklep/materialy/folie');

        $response
            ->assertOk()
            ->assertSee('Folie — SEO')
            ->assertSee('Meta PL Folie')
            ->assertSee('<h2>Więcej o Folie</h2>', false)
            ->assertSee(
                'href="http://okulary-3d.test/en/shop/materials/films"',
                false
            )
            ->assertSee(
                'href="http://okulary-3d.test/pl/sklep/materialy/folie"',
                false
            );

        $this->get('/en/shop/materials/films')
            ->assertOk()
            ->assertSee('Films — SEO')
            ->assertSee('More about Films', false);
    }

    public function test_legacy_category_query_redirects_to_clean_url(): void
    {
        $this->category(
            'Materiały',
            'materialy',
            'Materials',
            'materials'
        );

        $this->get('/pl/shop?category=materialy')
            ->assertRedirect('/pl/sklep/materialy')
            ->assertStatus(301);
    }

    public function test_sitemap_contains_localized_category_urls(): void
    {
        $this->category(
            'Materiały',
            'materialy',
            'Materials',
            'materials'
        );

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(
                'http://okulary-3d.test/pl/sklep/materialy',
                false
            )
            ->assertSee(
                'http://okulary-3d.test/en/shop/materials',
                false
            );
    }
}
