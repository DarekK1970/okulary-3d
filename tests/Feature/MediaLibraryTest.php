<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
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

    public function test_editor_can_open_media_library(): void
    {
        $this->actingAs($this->editor())
            ->get('/admin/media')
            ->assertOk()
            ->assertSee('Biblioteka mediów')
            ->assertSee('Dodaj obrazy');
    }

    public function test_editor_can_upload_image_to_library(): void
    {
        Storage::fake('public');

        $editor = $this->editor();

        $this->actingAs($editor)
            ->post('/admin/media', [
                'folder' => 'Artykuły historyczne',
                'files' => [
                    UploadedFile::fake()->image(
                        'stereo.jpg',
                        1200,
                        800
                    ),
                ],
            ])
            ->assertSessionHasNoErrors();

        $media = MediaAsset::query()->firstOrFail();

        $this->assertSame(
            'Artykuły historyczne',
            $media->folder
        );

        $this->assertSame(
            $editor->id,
            $media->uploaded_by
        );

        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_metadata_can_be_updated(): void
    {
        $editor = $this->editor();

        $media = MediaAsset::create([
            'disk' => 'public',
            'path' => 'media/test/image.jpg',
            'original_name' => 'image.jpg',
            'stored_name' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'folder' => 'general',
            'uploaded_by' => $editor->id,
        ]);

        $this->actingAs($editor)
            ->put('/admin/media/' . $media->id, [
                'title' => 'Stereoskop historyczny',
                'alt_text' => 'Drewniany stereoskop',
                'caption' => 'Eksponat z XIX wieku.',
                'folder' => 'Historia',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('media_assets', [
            'id' => $media->id,
            'title' => 'Stereoskop historyczny',
            'alt_text' => 'Drewniany stereoskop',
            'folder' => 'Historia',
        ]);
    }

    public function test_existing_media_can_be_selected_as_article_hero(): void
    {
        $editor = $this->editor();
        $category = $this->category();

        $media = MediaAsset::create([
            'disk' => 'public',
            'path' => 'media/test/hero.jpg',
            'original_name' => 'hero.jpg',
            'stored_name' => 'hero.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'folder' => 'article-heroes',
            'uploaded_by' => $editor->id,
        ]);

        $this->actingAs($editor)
            ->post('/admin/articles', [
                'category_id' => $category->id,
                'source_locale' => 'pl',
                'status' => ArticleStatus::Draft->value,
                'hero_media_id' => $media->id,
                'translations' => [
                    'pl' => [
                        'title' => 'Artykuł z biblioteką',
                        'body_html' => '<p>Treść.</p>',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $article = Article::query()->firstOrFail();

        $this->assertSame($media->id, $article->hero_media_id);
        $this->assertSame($media->path, $article->hero_image_path);
    }

    public function test_used_media_cannot_be_deleted(): void
    {
        Storage::fake('public');

        $editor = $this->editor();
        $category = $this->category();

        Storage::disk('public')->put('media/test/used.jpg', 'image');

        $media = MediaAsset::create([
            'disk' => 'public',
            'path' => 'media/test/used.jpg',
            'original_name' => 'used.jpg',
            'stored_name' => 'used.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'folder' => 'general',
            'uploaded_by' => $editor->id,
        ]);

        Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => 'Legacy',
            'slug' => 'legacy',
            'body_html' => '<p>Legacy</p>',
            'hero_media_id' => $media->id,
            'hero_image_path' => $media->path,
            'status' => ArticleStatus::Draft,
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        $this->actingAs($editor)
            ->delete('/admin/media/' . $media->id)
            ->assertSessionHasErrors('media_delete');

        $this->assertDatabaseHas('media_assets', [
            'id' => $media->id,
        ]);

        Storage::disk('public')->assertExists($media->path);
    }

    public function test_unused_media_is_deleted_with_physical_file(): void
    {
        Storage::fake('public');

        $editor = $this->editor();

        Storage::disk('public')->put(
            'media/test/unused.jpg',
            'image'
        );

        $media = MediaAsset::create([
            'disk' => 'public',
            'path' => 'media/test/unused.jpg',
            'original_name' => 'unused.jpg',
            'stored_name' => 'unused.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'folder' => 'general',
            'uploaded_by' => $editor->id,
        ]);

        $this->actingAs($editor)
            ->delete('/admin/media/' . $media->id)
            ->assertRedirect('/admin/media');

        $this->assertDatabaseMissing('media_assets', [
            'id' => $media->id,
        ]);

        Storage::disk('public')->assertMissing(
            'media/test/unused.jpg'
        );
    }
}
