<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Enums\CatalogTranslationStatus;
use App\Enums\ContextRecommendationType;
use App\Enums\ProductStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleContextRecommendation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextualRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lenticular_article_gets_automatic_lab_recommendation(): void
    {
        $article = $this->article(
            'Druk lentikularny 60 LPI',
            '<p>Jak przygotować obraz do folii soczewkowej i wykonać pitch test.</p>',
            true
        );

        $this->get(
            '/pl/articles/'
            . $article->translation('pl')->slug
        )
            ->assertOk()
            ->assertSee('Lenticular LAB')
            ->assertSee(
                '/pl/lab/lenticular',
                false
            );
    }

    public function test_manual_product_and_tool_are_rendered_when_auto_is_disabled(): void
    {
        $article = $this->article(
            'Fotografia stereo',
            '<p>Praca z parami stereo.</p>',
            false
        );

        $product = $this->product(
            'Okulary anaglifowe czerwono-cyjanowe'
        );

        ArticleContextRecommendation::create([
            'article_id' => $article->id,
            'target_type' =>
                ContextRecommendationType::Tool,
            'tool_key' => 'anaglyph',
            'position' => 1,
            'is_active' => true,
        ]);

        ArticleContextRecommendation::create([
            'article_id' => $article->id,
            'target_type' =>
                ContextRecommendationType::Product,
            'product_id' => $product->id,
            'position' => 2,
            'is_active' => true,
        ]);

        $this->get(
            '/pl/articles/'
            . $article->translation('pl')->slug
        )
            ->assertOk()
            ->assertSee('Anaglyph Maker')
            ->assertSee(
                'Okulary anaglifowe czerwono-cyjanowe'
            )
            ->assertSee(
                '/pl/shop/okulary-anaglifowe',
                false
            );
    }

    public function test_article_without_manual_or_auto_recommendations_has_no_block(): void
    {
        $article = $this->article(
            'Historia fotografii',
            '<p>Artykuł historyczny bez powiązań.</p>',
            false
        );

        $this->get(
            '/pl/articles/'
            . $article->translation('pl')->slug
        )
            ->assertOk()
            ->assertDontSee(
                'Sprawdź ten temat w praktyce'
            );
    }

    public function test_editor_sees_recommendation_controls_in_article_editor(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $article = $this->article(
            'Test rekomendacji',
            '<p>Treść.</p>',
            true
        );

        $this->actingAs($editor)
            ->get(
                '/admin/articles/'
                . $article->id
                . '/edit'
            )
            ->assertOk()
            ->assertSee(
                'Rekomendacje kontekstowe'
            )
            ->assertSee(
                'recommendation_auto',
                false
            )
            ->assertSee(
                'recommendation_tools[]',
                false
            )
            ->assertSee(
                'recommendation_products[]',
                false
            );
    }

    private function article(
        string $title,
        string $body,
        bool $auto
    ): Article {
        $category =
            ArticleCategory::create([
                'name' =>
                    'Technologie 3D '
                    . uniqid(),
                'slug' =>
                    'technologie-3d-'
                    . uniqid(),
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $article = Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => $title,
            'slug' =>
                'legacy-' . uniqid(),
            'excerpt' =>
                'Krótki opis.',
            'body_html' => $body,
            'status' =>
                ArticleStatus::Published,
            'published_at' => now(),
            'recommendation_auto' => $auto,
        ]);

        $article->translations()->create([
            'locale' => 'pl',
            'title' => $title,
            'slug' =>
                'artykul-' . uniqid(),
            'excerpt' =>
                'Krótki opis.',
            'body_html' => $body,
            'translation_status' =>
                ArticleTranslationStatus::Source,
        ]);

        return $article->fresh([
            'translations',
            'category',
        ]);
    }

    private function product(
        string $name
    ): Product {
        $category =
            ProductCategory::create([
                'source_locale' => 'pl',
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $category->translations()->create([
            'locale' => 'pl',
            'name' => 'Okulary 3D',
            'slug' =>
                'okulary-3d-'
                . uniqid(),
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        $product = Product::create([
            'category_id' =>
                $category->id,
            'source_locale' => 'pl',
            'status' =>
                ProductStatus::Active,
            'brand' => 'Test 3D',
            'is_featured' => true,
        ]);

        $product->translations()->create([
            'locale' => 'pl',
            'name' => $name,
            'slug' =>
                'okulary-anaglifowe',
            'short_description' =>
                'Okulary do anaglifów czerwono-cyjanowych.',
            'description_html' =>
                '<p>Produkt testowy.</p>',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        $product->variants()->create([
            'sku' => 'TEST-3D-' . uniqid(),
            'name' => 'Standard',
            'price_gross' => 9.99,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 10,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $product->fresh([
            'translations',
            'activeVariants',
            'category.translations',
        ]);
    }
}
