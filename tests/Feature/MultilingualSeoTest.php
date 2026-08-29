<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Enums\CatalogTranslationStatus;
use App\Enums\ProductStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultilingualSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_canonical_hreflang_x_default_and_open_graph(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee(
                'rel="canonical" href="'
                . route(
                    'home',
                    ['locale' => 'pl']
                )
                . '"',
                false
            )
            ->assertSee(
                'hreflang="pl"',
                false
            )
            ->assertSee(
                'hreflang="en"',
                false
            )
            ->assertSee(
                'hreflang="x-default"',
                false
            )
            ->assertSee(
                'property="og:locale" content="pl_PL"',
                false
            )
            ->assertSee(
                'application/ld+json',
                false
            );
    }

    public function test_filtered_archive_is_noindex_and_canonical_points_to_clean_listing(): void
    {
        $this->get(
            '/pl/archive?technique=stereocard&year_to=1910'
        )
            ->assertOk()
            ->assertSee(
                'name="robots" content="noindex,follow"',
                false
            )
            ->assertSee(
                'rel="canonical" href="'
                . route(
                    'archive.index',
                    ['locale' => 'pl']
                )
                . '"',
                false
            );
    }

    public function test_article_uses_localized_slugs_for_hreflang_and_article_schema(): void
    {
        $article =
            $this->articleWithTranslations();

        $pl = $article->translation('pl');
        $en = $article->translation('en');

        $this->get(
            '/pl/articles/' . $pl->slug
        )
            ->assertOk()
            ->assertSee(
                'rel="canonical" href="'
                . route(
                    'articles.show',
                    [
                        'locale' => 'pl',
                        'slug' => $pl->slug,
                    ]
                )
                . '"',
                false
            )
            ->assertSee(
                'hreflang="en"',
                false
            )
            ->assertSee(
                route(
                    'articles.show',
                    [
                        'locale' => 'en',
                        'slug' => $en->slug,
                    ]
                ),
                false
            )
            ->assertSee(
                '"@type":"Article"',
                false
            )
            ->assertSee(
                '"inLanguage":"pl"',
                false
            );
    }

    public function test_product_page_contains_product_structured_data(): void
    {
        $product =
            $this->productWithTranslations();

        $translation =
            $product->translation('pl');

        $this->get(
            '/pl/shop/'
            . $translation->slug
        )
            ->assertOk()
            ->assertSee(
                '"@type":"Product"',
                false
            )
            ->assertSee(
                '"priceCurrency":"PLN"',
                false
            )
            ->assertSee(
                '"availability":"https://schema.org/InStock"',
                false
            );
    }

    public function test_sitemap_contains_static_and_public_multilingual_content_but_not_draft_translation(): void
    {
        $article =
            $this->articleWithTranslations();

        $draftArticle =
            $this->articleWithTranslations(
                enStatus:
                    ArticleTranslationStatus::Draft,
                suffix: 'draft'
            );

        $pl = $article->translation('pl');
        $en = $article->translation('en');

        $draftEn =
            $draftArticle->translation('en');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/xml; charset=UTF-8'
            )
            ->assertSee(
                route(
                    'lab.lenticular',
                    ['locale' => 'pl']
                ),
                false
            )
            ->assertSee(
                route(
                    'articles.show',
                    [
                        'locale' => 'pl',
                        'slug' => $pl->slug,
                    ]
                ),
                false
            )
            ->assertSee(
                route(
                    'articles.show',
                    [
                        'locale' => 'en',
                        'slug' => $en->slug,
                    ]
                ),
                false
            )
            ->assertDontSee(
                route(
                    'articles.show',
                    [
                        'locale' => 'en',
                        'slug' => $draftEn->slug,
                    ]
                ),
                false
            )
            ->assertSee(
                'hreflang="x-default"',
                false
            );
    }

    public function test_robots_txt_points_to_sitemap_and_blocks_private_areas(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee(
                'User-agent: *',
                false
            )
            ->assertSee(
                'Disallow: /admin',
                false
            )
            ->assertSee(
                'Disallow: /*/checkout',
                false
            )
            ->assertSee(
                'Sitemap: '
                . route('sitemap'),
                false
            );
    }

    private function articleWithTranslations(
        ArticleTranslationStatus $enStatus =
            ArticleTranslationStatus::Ready,
        string $suffix = 'main'
    ): Article {
        $category =
            ArticleCategory::create([
                'name' =>
                    'SEO 3D ' . $suffix,
                'slug' =>
                    'seo-3d-' . $suffix
                    . '-' . uniqid(),
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $article = Article::create([
            'category_id' =>
                $category->id,
            'source_locale' => 'pl',
            'title' =>
                'Artykuł SEO ' . $suffix,
            'slug' =>
                'legacy-' . $suffix
                . '-' . uniqid(),
            'excerpt' =>
                'Opis artykułu.',
            'body_html' =>
                '<p>Treść artykułu o stereoskopii.</p>',
            'status' =>
                ArticleStatus::Published,
            'published_at' => now(),
            'recommendation_auto' =>
                false,
        ]);

        $article->translations()->create([
            'locale' => 'pl',
            'title' =>
                'Polski artykuł '
                . $suffix,
            'slug' =>
                'polski-artykul-'
                . $suffix
                . '-'
                . uniqid(),
            'excerpt' =>
                'Polski opis SEO.',
            'body_html' =>
                '<p>Polska treść.</p>',
            'seo_title' =>
                'Polski tytuł SEO',
            'seo_description' =>
                'Polski opis SEO.',
            'translation_status' =>
                ArticleTranslationStatus::Source,
        ]);

        $article->translations()->create([
            'locale' => 'en',
            'title' =>
                'English article '
                . $suffix,
            'slug' =>
                'english-article-'
                . $suffix
                . '-'
                . uniqid(),
            'excerpt' =>
                'English SEO summary.',
            'body_html' =>
                '<p>English content.</p>',
            'seo_title' =>
                'English SEO title',
            'seo_description' =>
                'English SEO description.',
            'translation_status' =>
                $enStatus,
        ]);

        return $article->fresh([
            'translations',
            'category',
        ]);
    }

    private function productWithTranslations(): Product
    {
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

        $category->translations()->create([
            'locale' => 'en',
            'name' => '3D Glasses',
            'slug' =>
                '3d-glasses-'
                . uniqid(),
            'translation_status' =>
                CatalogTranslationStatus::Ready,
        ]);

        $product = Product::create([
            'category_id' =>
                $category->id,
            'source_locale' => 'pl',
            'status' =>
                ProductStatus::Active,
            'brand' => 'Stereo Test',
            'is_featured' => false,
        ]);

        $product->translations()->create([
            'locale' => 'pl',
            'name' =>
                'Okulary anaglifowe',
            'slug' =>
                'okulary-anaglifowe-'
                . uniqid(),
            'short_description' =>
                'Okulary czerwono-cyjanowe.',
            'description_html' =>
                '<p>Produkt do oglądania anaglifów.</p>',
            'seo_title' =>
                'Okulary anaglifowe',
            'seo_description' =>
                'Okulary do anaglifów.',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        $product->translations()->create([
            'locale' => 'en',
            'name' =>
                'Anaglyph glasses',
            'slug' =>
                'anaglyph-glasses-'
                . uniqid(),
            'short_description' =>
                'Red-cyan 3D glasses.',
            'description_html' =>
                '<p>Glasses for viewing anaglyphs.</p>',
            'seo_title' =>
                'Anaglyph glasses',
            'seo_description' =>
                'Red-cyan anaglyph glasses.',
            'translation_status' =>
                CatalogTranslationStatus::Ready,
        ]);

        $product->variants()->create([
            'sku' => 'SEO-' . uniqid(),
            'name' => 'Standard',
            'price_gross' => 12.99,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 20,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $product->fresh([
            'translations',
            'activeVariants',
            'media',
        ]);
    }
}
