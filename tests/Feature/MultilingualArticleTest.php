<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultilingualArticleTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);
    }

    private function category(): ArticleCategory
    {
        return ArticleCategory::create([
            'name' => 'Techniki 3D',
            'slug' => 'techniki-3d',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_editor_can_create_pl_and_en_versions_with_independent_slugs(): void
    {
        $editor = $this->editor();
        $category = $this->category();

        $response = $this->actingAs($editor)
            ->post('/admin/articles', [
                'category_id' => $category->id,
                'source_locale' => 'pl',
                'status' => ArticleStatus::Published->value,
                'translations' => [
                    'pl' => [
                        'title' => 'Historia stereoskopii',
                        'slug' => 'historia-stereoskopii',
                        'excerpt' => 'Polski opis.',
                        'body_html' => '<p>Polska treść.</p>',
                        'seo_title' => 'Historia stereoskopii i obrazu 3D',
                        'seo_description' => 'Poznaj historię stereoskopii.',
                    ],
                    'en' => [
                        'title' => 'History of stereoscopy',
                        'slug' => 'history-of-stereoscopy',
                        'excerpt' => 'English description.',
                        'body_html' => '<p>English content.</p>',
                        'seo_title' => 'History of stereoscopy',
                        'seo_description' => 'Explore stereoscopy history.',
                        'translation_status' => ArticleTranslationStatus::Ready->value,
                    ],
                ],
            ]);

        $article = Article::query()->firstOrFail();

        $response->assertRedirect(route('admin.articles.edit', $article));

        $this->assertDatabaseHas('article_translations', [
            'article_id' => $article->id,
            'locale' => 'pl',
            'slug' => 'historia-stereoskopii',
            'translation_status' => ArticleTranslationStatus::Source->value,
        ]);

        $this->assertDatabaseHas('article_translations', [
            'article_id' => $article->id,
            'locale' => 'en',
            'slug' => 'history-of-stereoscopy',
            'translation_status' => ArticleTranslationStatus::Ready->value,
        ]);
    }

    public function test_public_article_uses_locale_specific_slug_and_seo(): void
    {
        $editor = $this->editor();
        $category = $this->category();

        $article = Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => 'Test',
            'slug' => 'test',
            'body_html' => '<p>Legacy</p>',
            'status' => ArticleStatus::Published,
            'published_at' => now(),
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'pl',
            'title' => 'Polski artykuł',
            'slug' => 'polski-artykul',
            'body_html' => '<p>Treść PL</p>',
            'seo_title' => 'SEO PL',
            'seo_description' => 'Opis SEO PL',
            'translation_status' => ArticleTranslationStatus::Source,
        ]);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'en',
            'title' => 'English article',
            'slug' => 'english-article',
            'body_html' => '<p>EN content</p>',
            'seo_title' => 'SEO EN',
            'seo_description' => 'SEO description EN',
            'translation_status' => ArticleTranslationStatus::Ready,
        ]);

        $this->get('/pl/articles/polski-artykul')
            ->assertOk()
            ->assertSee('Polski artykuł')
            ->assertSee('SEO PL', false)
            ->assertSee('Opis SEO PL', false);

        $this->get('/en/articles/english-article')
            ->assertOk()
            ->assertSee('English article')
            ->assertSee('SEO EN', false)
            ->assertSee('SEO description EN', false);
    }

    public function test_draft_translation_is_not_public(): void
    {
        $editor = $this->editor();
        $category = $this->category();

        $article = Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => 'Test',
            'slug' => 'test',
            'body_html' => '<p>Legacy</p>',
            'status' => ArticleStatus::Published,
            'published_at' => now(),
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'en',
            'title' => 'Draft English',
            'slug' => 'draft-english',
            'body_html' => '<p>Draft</p>',
            'translation_status' => ArticleTranslationStatus::Draft,
        ]);

        $this->get('/en/articles/draft-english')
            ->assertNotFound();
    }

    public function test_same_slug_can_exist_in_different_locales(): void
    {
        $editor = $this->editor();
        $category = $this->category();

        $article = Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => 'Legacy',
            'slug' => 'legacy',
            'body_html' => '<p>Legacy</p>',
            'status' => ArticleStatus::Draft,
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'pl',
            'title' => 'Test PL',
            'slug' => 'stereo',
            'body_html' => '<p>PL</p>',
            'translation_status' => ArticleTranslationStatus::Source,
        ]);

        ArticleTranslation::create([
            'article_id' => $article->id,
            'locale' => 'en',
            'title' => 'Test EN',
            'slug' => 'stereo',
            'body_html' => '<p>EN</p>',
            'translation_status' => ArticleTranslationStatus::Ready,
        ]);

        $this->assertSame(
            2,
            ArticleTranslation::query()
                ->where('slug', 'stereo')
                ->count()
        );
    }

    public function test_partial_non_source_translation_is_rejected(): void
    {
        $editor = $this->editor();
        $category = $this->category();

        $this->actingAs($editor)
            ->post('/admin/articles', [
                'category_id' => $category->id,
                'source_locale' => 'pl',
                'status' => ArticleStatus::Draft->value,
                'translations' => [
                    'pl' => [
                        'title' => 'Polski',
                        'body_html' => '<p>Treść</p>',
                    ],
                    'en' => [
                        'title' => 'Only title',
                        'body_html' => '',
                        'translation_status' => ArticleTranslationStatus::Draft->value,
                    ],
                ],
            ])
            ->assertSessionHasErrors('translations.en.title');

        $this->assertDatabaseCount('articles', 0);
    }
}
