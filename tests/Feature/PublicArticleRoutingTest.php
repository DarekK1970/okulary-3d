<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicArticleRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_index_route_lists_real_published_article(): void
    {
        $article =
            $this->publishedArticle(
                title:
                    'Vistascreen — test publikacji',
                slug:
                    'vistascreen-test',
                categoryName:
                    'Historia 3D',
                categorySlug:
                    'historia-3d'
            );

        $this->get('/pl/articles')
            ->assertOk()
            ->assertSee(
                'Vistascreen — test publikacji'
            )
            ->assertSee(
                route(
                    'articles.show',
                    [
                        'locale' =>
                            'pl',
                        'slug' =>
                            'vistascreen-test',
                    ]
                ),
                false
            );

        $this->assertTrue(
            $article->exists
        );
    }

    public function test_homepage_uses_published_articles_instead_of_demo_cards(): void
    {
        $this->publishedArticle(
            title:
                'Prawdziwy artykuł z bazy',
            slug:
                'prawdziwy-artykul'
        );

        $response =
            $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee(
                'Prawdziwy artykuł z bazy'
            )
            ->assertSee(
                route(
                    'articles.show',
                    [
                        'locale' =>
                            'pl',
                        'slug' =>
                            'prawdziwy-artykul',
                    ]
                ),
                false
            )
            ->assertDontSee(
                'Historia stereoskopii w pigułce'
            );
    }

    public function test_articles_navigation_points_to_real_listing_route(): void
    {
        $this->publishedArticle(
            title: 'Routing 3D',
            slug: 'routing-3d'
        );

        $listing =
            route(
                'articles.index',
                ['locale' => 'pl']
            );

        $this->get('/pl')
            ->assertOk()
            ->assertSee(
                'href="' . $listing . '"',
                false
            );

        $this->get(
            '/pl/articles/routing-3d'
        )
            ->assertOk()
            ->assertSee(
                'href="' . $listing . '"',
                false
            );
    }

    public function test_articles_index_filters_by_category(): void
    {
        $this->publishedArticle(
            title:
                'Historia stereoskopii',
            slug:
                'historia-stereoskopii',
            categoryName:
                'Historia 3D',
            categorySlug:
                'historia-3d'
        );

        $this->publishedArticle(
            title:
                'Fotografia stereo',
            slug:
                'fotografia-stereo',
            categoryName:
                'Fotografia',
            categorySlug:
                'fotografia'
        );

        $this->get(
            '/pl/articles?category=historia-3d'
        )
            ->assertOk()
            ->assertSee(
                'Historia stereoskopii'
            )
            ->assertDontSee(
                'Fotografia stereo'
            );
    }

    public function test_draft_and_future_articles_are_not_visible_on_listing_or_home(): void
    {
        $category =
            $this->category(
                'Techniki 3D',
                'techniki-3d'
            );

        $draft = Article::create([
            'category_id' =>
                $category->id,
            'source_locale' => 'pl',
            'title' => 'Draft',
            'slug' => 'draft',
            'body_html' =>
                '<p>Draft</p>',
            'status' =>
                ArticleStatus::Draft,
            'published_at' =>
                null,
        ]);

        $draft->translations()
            ->create([
                'locale' => 'pl',
                'title' =>
                    'Niewidoczny draft',
                'slug' =>
                    'niewidoczny-draft',
                'body_html' =>
                    '<p>Draft</p>',
                'translation_status' =>
                    ArticleTranslationStatus::Source,
            ]);

        $future =
            Article::create([
                'category_id' =>
                    $category->id,
                'source_locale' =>
                    'pl',
                'title' =>
                    'Future',
                'slug' =>
                    'future',
                'body_html' =>
                    '<p>Future</p>',
                'status' =>
                    ArticleStatus::Published,
                'published_at' =>
                    now()->addDay(),
            ]);

        $future->translations()
            ->create([
                'locale' => 'pl',
                'title' =>
                    'Przyszła publikacja',
                'slug' =>
                    'przyszla-publikacja',
                'body_html' =>
                    '<p>Future</p>',
                'translation_status' =>
                    ArticleTranslationStatus::Source,
            ]);

        $this->get('/pl/articles')
            ->assertOk()
            ->assertDontSee(
                'Niewidoczny draft'
            )
            ->assertDontSee(
                'Przyszła publikacja'
            );

        $this->get('/pl')
            ->assertOk()
            ->assertDontSee(
                'Niewidoczny draft'
            )
            ->assertDontSee(
                'Przyszła publikacja'
            );
    }

    private function publishedArticle(
        string $title,
        string $slug,
        string $categoryName =
            'Techniki 3D',
        string $categorySlug =
            'techniki-3d'
    ): Article {
        $category =
            $this->category(
                $categoryName,
                $categorySlug
            );

        $article = Article::create([
            'category_id' =>
                $category->id,
            'source_locale' =>
                'pl',
            'title' =>
                $title,
            'slug' =>
                'legacy-' . $slug,
            'excerpt' =>
                'Opis publikacji.',
            'body_html' =>
                '<p>Treść publikacji testowej.</p>',
            'status' =>
                ArticleStatus::Published,
            'published_at' =>
                now(),
            'recommendation_auto' =>
                false,
        ]);

        $article->translations()
            ->create([
                'locale' => 'pl',
                'title' =>
                    $title,
                'slug' =>
                    $slug,
                'excerpt' =>
                    'Opis publikacji.',
                'body_html' =>
                    '<p>Treść publikacji testowej.</p>',
                'translation_status' =>
                    ArticleTranslationStatus::Source,
            ]);

        return $article->fresh([
            'translations',
            'category',
        ]);
    }

    private function category(
        string $name,
        string $slug
    ): ArticleCategory {
        return ArticleCategory::query()
            ->firstOrCreate(
                [
                    'slug' => $slug,
                ],
                [
                    'name' => $name,
                    'is_active' =>
                        true,
                    'sort_order' => 0,
                ]
            );
    }
}
