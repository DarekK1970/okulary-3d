<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleCmsTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);
    }

    public function test_editor_can_open_article_cms(): void
    {
        $this->actingAs($this->editor())
            ->get('/admin/articles')
            ->assertOk()
            ->assertSee('Artykuły')
            ->assertSee('Nowy artykuł');
    }

    public function test_editor_can_create_category(): void
    {
        $this->actingAs($this->editor())
            ->post('/admin/article-categories', [
                'name' => 'Historia 3D',
                'slug' => '',
                'description' => 'Historia stereoskopii',
                'is_active' => '1',
                'sort_order' => 10,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('article_categories', [
            'name' => 'Historia 3D',
            'slug' => 'historia-3d',
            'is_active' => true,
        ]);
    }

    public function test_editor_can_create_article_with_hero_image(): void
    {
        Storage::fake('public');

        $editor = $this->editor();
        $category = ArticleCategory::create([
            'name' => 'Techniki 3D',
            'slug' => 'techniki-3d',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($editor)
            ->post('/admin/articles', [
                'category_id' => $category->id,
                'title' => 'Jak działa stereoskopia',
                'slug' => '',
                'excerpt' => 'Krótki opis.',
                'body_html' => '<p>Treść <strong>artykułu</strong>.</p>',
                'status' => ArticleStatus::Draft->value,
                'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 800),
            ]);

        $article = Article::query()->firstOrFail();

        $response->assertRedirect(route('admin.articles.edit', $article));

        $this->assertSame('jak-dziala-stereoskopia', $article->slug);
        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertNotNull($article->hero_image_path);

        Storage::disk('public')->assertExists($article->hero_image_path);
    }

    public function test_article_html_is_sanitized(): void
    {
        $editor = $this->editor();
        $category = ArticleCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($editor)
            ->post('/admin/articles', [
                'category_id' => $category->id,
                'title' => 'Bezpieczny artykuł',
                'slug' => '',
                'body_html' => '<p onclick="alert(1)">OK</p><script>alert(2)</script>',
                'status' => ArticleStatus::Draft->value,
            ])
            ->assertSessionHasNoErrors();

        $body = Article::query()->firstOrFail()->body_html;

        $this->assertStringContainsString('<p>OK</p>', $body);
        $this->assertStringNotContainsString('script', $body);
        $this->assertStringNotContainsString('onclick', $body);
    }

    public function test_scheduled_article_is_published_by_command(): void
    {
        $editor = $this->editor();
        $category = ArticleCategory::create([
            'name' => 'Aktualności',
            'slug' => 'aktualnosci',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $article = Article::create([
            'category_id' => $category->id,
            'title' => 'Zaplanowany artykuł',
            'slug' => 'zaplanowany-artykul',
            'body_html' => '<p>Treść</p>',
            'status' => ArticleStatus::Scheduled,
            'published_at' => now()->subMinute(),
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        $this->artisan('articles:publish-scheduled')
            ->assertSuccessful();

        $article->refresh();

        $this->assertSame(ArticleStatus::Published, $article->status);
    }

    public function test_category_with_articles_cannot_be_deleted(): void
    {
        $editor = $this->editor();
        $category = ArticleCategory::create([
            'name' => 'Historia',
            'slug' => 'historia',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Article::create([
            'category_id' => $category->id,
            'title' => 'Artykuł',
            'slug' => 'artykul',
            'body_html' => '<p>Treść</p>',
            'status' => ArticleStatus::Draft,
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        $this->actingAs($editor)
            ->delete('/admin/article-categories/' . $category->id)
            ->assertSessionHasErrors('category_delete');

        $this->assertDatabaseHas('article_categories', [
            'id' => $category->id,
        ]);
    }
}
