<?php

namespace Tests\Feature;

use App\Enums\ArchiveTranslationStatus;
use App\Enums\ArticlePortalSection;
use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Models\ArchiveItem;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticlePortalSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        ArticleCategory::query()->firstOrCreate(
            ['slug' => 'ciekawostki-historyczne'],
            [
                'name' => 'Ciekawostki historyczne',
                'portal_section' => ArticlePortalSection::HistoryCuriosities,
                'is_active' => true,
                'sort_order' => 20,
            ]
        );

        ArticleCategory::query()->firstOrCreate(
            ['slug' => 'techniki-3d'],
            [
                'name' => 'Techniki 3D',
                'portal_section' => ArticlePortalSection::Techniques,
                'is_active' => true,
                'sort_order' => 30,
            ]
        );
    }

    public function test_special_categories_are_mapped_by_stable_portal_section_not_id(): void
    {
        $history = ArticleCategory::query()
            ->where('slug', 'ciekawostki-historyczne')
            ->firstOrFail();
        $techniques = ArticleCategory::query()
            ->where('slug', 'techniki-3d')
            ->firstOrFail();

        $this->assertSame(
            ArticlePortalSection::HistoryCuriosities,
            $history->portal_section
        );
        $this->assertSame(
            ArticlePortalSection::Techniques,
            $techniques->portal_section
        );
    }

    public function test_general_articles_do_not_show_history_or_technique_articles(): void
    {
        $general = ArticleCategory::create([
            'name' => 'Aktualności',
            'slug' => 'aktualnosci-test',
            'portal_section' => ArticlePortalSection::Articles,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $history = ArticleCategory::query()
            ->where('slug', 'ciekawostki-historyczne')
            ->firstOrFail();
        $techniques = ArticleCategory::query()
            ->where('slug', 'techniki-3d')
            ->firstOrFail();

        $this->publishedArticle($general, 'Publikacja ogólna', 'publikacja-ogolna');
        $this->publishedArticle($history, 'Sekret stereoskopii', 'sekret-stereoskopii');
        $this->publishedArticle($techniques, 'Technika anaglifu', 'technika-anaglifu');

        $this->get('/pl/articles')
            ->assertOk()
            ->assertSee('Publikacja ogólna')
            ->assertDontSee('Sekret stereoskopii')
            ->assertDontSee('Technika anaglifu');
    }

    public function test_history_and_techniques_have_independent_article_pages(): void
    {
        $history = ArticleCategory::query()
            ->where('slug', 'ciekawostki-historyczne')
            ->firstOrFail();
        $techniques = ArticleCategory::query()
            ->where('slug', 'techniki-3d')
            ->firstOrFail();

        $this->publishedArticle($history, 'Historia stereokarty', 'historia-stereokarty');
        $this->publishedArticle($techniques, 'Jak zrobić anaglif', 'jak-zrobic-anaglif');

        $this->get('/pl/articles?section=history-curiosities')
            ->assertOk()
            ->assertSee('Ciekawostki historyczne')
            ->assertSee('Historia stereokarty')
            ->assertDontSee('Jak zrobić anaglif');

        $this->get('/pl/articles?section=techniques')
            ->assertOk()
            ->assertSee('Techniki 3D')
            ->assertSee('Jak zrobić anaglif')
            ->assertDontSee('Historia stereokarty')
            ->assertSee('home-publication-card', false);
    }

    public function test_history_menu_contains_curiosities_and_existing_archive(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertSee('Ciekawostki historyczne')
            ->assertSee('Archiwum stereoskopii')
            ->assertSee('/pl/articles?section=history-curiosities', false)
            ->assertSee('/pl/archive', false);
    }

    public function test_homepage_hides_empty_techniques_and_archive_modules(): void
    {
        $this->get('/pl')
            ->assertOk()
            ->assertDontSee('id="techniques"', false)
            ->assertDontSee('class="home-section archive-section"', false)
            ->assertDontSee('Spatial Photos');
    }

    public function test_homepage_uses_real_technique_articles_and_real_archive_items(): void
    {
        $techniques = ArticleCategory::query()
            ->where('slug', 'techniki-3d')
            ->firstOrFail();

        $this->publishedArticle(
            $techniques,
            'Polaryzacja w kinie 3D',
            'polaryzacja-w-kinie-3d'
        );

        $archive = ArchiveItem::create([
            'source_locale' => 'pl',
            'technique' => 'stereocard',
            'year_from' => 1901,
            'circa' => false,
            'source_name' => 'Testowe archiwum',
            'rights_status' => 'public_domain',
            'original_image_path' => 'archive/test/real-card.jpg',
            'is_published' => true,
            'published_at' => now(),
        ]);
        Storage::disk('public')->put($archive->original_image_path, 'image');
        $archive->translations()->create([
            'locale' => 'pl',
            'title' => 'Prawdziwa karta stereo',
            'slug' => 'prawdziwa-karta-stereo',
            'description' => 'Opis.',
            'translation_status' => ArchiveTranslationStatus::Source,
        ]);

        $this->get('/pl')
            ->assertOk()
            ->assertSee('id="techniques"', false)
            ->assertSee('Polaryzacja w kinie 3D')
            ->assertDontSee('Spatial Photos')
            ->assertSee('class="home-section archive-section"', false)
            ->assertSee('Prawdziwa karta stereo')
            ->assertDontSee('Paryż — widok z Sekwany');
    }

    private function publishedArticle(
        ArticleCategory $category,
        string $title,
        string $slug
    ): Article {
        $article = Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => $title,
            'slug' => 'legacy-' . $slug,
            'excerpt' => 'Krótki wstęp do publikacji.',
            'body_html' => '<p>Treść publikacji.</p>',
            'status' => ArticleStatus::Published,
            'published_at' => now(),
        ]);

        $article->translations()->create([
            'locale' => 'pl',
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'Krótki wstęp do publikacji.',
            'body_html' => '<p>Treść publikacji.</p>',
            'translation_status' => ArticleTranslationStatus::Source,
        ]);

        return $article;
    }
}
