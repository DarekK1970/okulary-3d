<?php

namespace Tests\Feature;

use App\Enums\ArticlePortalSection;
use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageArticleCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_three_compact_horizontal_publication_cards(): void
    {
        $category = ArticleCategory::create([
            'name' => 'Historia 3D',
            'slug' => 'historia-3d',
            'portal_section' => ArticlePortalSection::Articles,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        foreach (range(1, 4) as $index) {
            $article = Article::create([
                'category_id' => $category->id,
                'source_locale' => 'pl',
                'title' => 'Artykuł ' . $index,
                'slug' => 'legacy-artykul-' . $index,
                'excerpt' => 'Krótki wstęp publikacji numer ' . $index . '.',
                'body_html' => '<p>Treść publikacji.</p>',
                'status' => ArticleStatus::Published,
                'published_at' => now()->subMinutes($index),
            ]);

            $article->translations()->create([
                'locale' => 'pl',
                'title' => 'Artykuł ' . $index,
                'slug' => 'artykul-' . $index,
                'excerpt' => 'Krótki wstęp publikacji numer ' . $index . '.',
                'body_html' => '<p>Treść publikacji.</p>',
                'translation_status' => ArticleTranslationStatus::Source,
            ]);
        }

        $response = $this->get('/pl');

        $response
            ->assertOk()
            ->assertSee('home-publications-grid', false)
            ->assertSee('home-publication-card', false)
            ->assertSee('home-publication-image', false)
            ->assertSee('home-publication-copy', false)
            ->assertSee('Czytaj dalej');

        /*
         * HomeController deliberately exposes only the latest 3.
         */
        $response
            ->assertSee('Artykuł 1')
            ->assertSee('Artykuł 2')
            ->assertSee('Artykuł 3')
            ->assertDontSee('Artykuł 4');
    }

    public function test_homepage_card_uses_excerpt_and_real_article_url(): void
    {
        $category = ArticleCategory::query()->firstOrCreate(
            ['slug' => 'techniki-3d'],
            [
                'name' => 'Techniki 3D',
                'portal_section' => ArticlePortalSection::Techniques,
                'is_active' => true,
                'sort_order' => 30,
            ]
        );

        $category->update([
            'portal_section' => ArticlePortalSection::Techniques,
            'is_active' => true,
        ]);

        $article = Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => 'Test poziomej karty',
            'slug' => 'legacy-test-poziomej-karty',
            'excerpt' => 'To jest krótki wstęp wyświetlany obok pionowej miniatury.',
            'body_html' => '<p>Dłuższa treść artykułu.</p>',
            'status' => ArticleStatus::Published,
            'published_at' => now(),
        ]);

        $article->translations()->create([
            'locale' => 'pl',
            'title' => 'Test poziomej karty',
            'slug' => 'test-poziomej-karty',
            'excerpt' => 'To jest krótki wstęp wyświetlany obok pionowej miniatury.',
            'body_html' => '<p>Dłuższa treść artykułu.</p>',
            'translation_status' => ArticleTranslationStatus::Source,
        ]);

        $url = route('articles.show', [
            'locale' => 'pl',
            'slug' => 'test-poziomej-karty',
        ]);

        $this->get('/pl')
            ->assertOk()
            ->assertSee('To jest krótki wstęp wyświetlany obok pionowej miniatury.')
            ->assertSee('href="' . $url . '"', false);
    }
}
