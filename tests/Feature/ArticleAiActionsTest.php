<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\AiTranslationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleAiActionsTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()
            ->create([
                'role' =>
                    User::ROLE_EDITOR,
            ]);
    }

    private function article(
        User $user,
        bool $withImage = false
    ): Article {
        $category =
            ArticleCategory::create([
                'name' =>
                    'Historia 3D',
                'slug' =>
                    'historia-3d',
                'is_active' =>
                    true,
                'sort_order' =>
                    0,
            ]);

        $article =
            Article::create([
                'category_id' =>
                    $category->id,
                'source_locale' =>
                    'pl',
                'title' =>
                    'Vistascreen i stereoskopia',
                'slug' =>
                    'legacy-vistascreen',
                'excerpt' =>
                    'Historia brytyjskich zestawów stereoskopowych.',
                'body_html' =>
                    '<p>Artykuł o historii Vistascreen i fotografii stereoskopowej.</p>',
                'status' =>
                    ArticleStatus::Published,
                'published_at' =>
                    now(),
                'created_by' =>
                    $user->id,
                'updated_by' =>
                    $user->id,
            ]);

        $article->translations()
            ->create([
                'locale' => 'pl',
                'title' =>
                    'Vistascreen i stereoskopia',
                'slug' =>
                    'vistascreen-i-stereoskopia',
                'excerpt' =>
                    'Historia brytyjskich zestawów stereoskopowych.',
                'body_html' =>
                    '<p>Artykuł o historii Vistascreen i fotografii stereoskopowej.</p>',
                'seo_title' =>
                    'Vistascreen i stereoskopia',
                'seo_description' =>
                    'Historia Vistascreen.',
                'translation_status' =>
                    ArticleTranslationStatus::Source,
            ]);

        if ($withImage) {
            $media =
                MediaAsset::create([
                    'disk' => 'public',
                    'path' =>
                        'media/test/existing.png',
                    'original_name' =>
                        'existing.png',
                    'stored_name' =>
                        'existing.png',
                    'mime_type' =>
                        'image/png',
                    'extension' =>
                        'png',
                    'size_bytes' =>
                        68,
                    'width' => 1,
                    'height' => 1,
                    'title' =>
                        'Existing',
                    'folder' =>
                        'article-heroes',
                    'uploaded_by' =>
                        $user->id,
                ]);

            $article->update([
                'hero_media_id' =>
                    $media->id,
                'hero_image_path' =>
                    $media->path,
            ]);
        }

        return $article->fresh([
            'translations',
            'heroMedia',
        ]);
    }

    private function configureOpenAi(): void
    {
        $settings =
            app(
                AiTranslationSettingsService::class
            );

        $settings->set(
            'enabled',
            '1'
        );

        $settings->set(
            'openai.api_key',
            'test-openai-key',
            true
        );

        $settings->set(
            'openai.image_model',
            'gpt-image-2'
        );
    }

    public function test_article_list_has_icon_actions_and_ai_translation_route(): void
    {
        $editor = $this->editor();
        $article =
            $this->article($editor);

        $response =
            $this->actingAs(
                $editor
            )
                ->get(
                    '/admin/articles'
                );

        $response
            ->assertOk()
            ->assertSee(
                'article-action-icons',
                false
            )
            ->assertSee(
                'Automatyczna translacja'
            )
            ->assertSee(
                route(
                    'admin.translations.translate',
                    [
                        'type' =>
                            'article',
                        'id' =>
                            $article->id,
                    ]
                ),
                false
            )
            ->assertSee(
                route(
                    'admin.articles.generate-image',
                    $article
                ),
                false
            )
            ->assertSee(
                'Wygeneruj obraz'
            )
            ->assertSee(
                'Podgląd'
            );
    }

    public function test_generate_image_action_is_not_rendered_when_article_has_image(): void
    {
        $editor = $this->editor();
        $article =
            $this->article(
                $editor,
                true
            );

        $this->actingAs(
            $editor
        )
            ->get(
                '/admin/articles'
            )
            ->assertOk()
            ->assertDontSee(
                route(
                    'admin.articles.generate-image',
                    $article
                ),
                false
            );
    }

    public function test_editor_can_generate_and_attach_openai_image(): void
    {
        Storage::fake('public');

        $editor =
            $this->editor();

        $article =
            $this->article(
                $editor
            );

        $this->configureOpenAi();

        /*
         * Valid 1x1 PNG. The production service accepts the real
         * 1536x1024 response exactly the same way.
         */
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl8vK0AAAAASUVORK5CYII=',
            true
        );

        Http::fake([
            'https://api.openai.com/v1/images/generations' =>
                Http::response([
                    'data' => [
                        [
                            'b64_json' =>
                                base64_encode(
                                    $png
                                ),
                        ],
                    ],
                    'usage' => [
                        'input_tokens' =>
                            42,
                        'output_tokens' =>
                            100,
                        'total_tokens' =>
                            142,
                    ],
                ], 200),
        ]);

        $this->actingAs(
            $editor
        )
            ->post(
                '/admin/articles/'
                . $article->id
                . '/generate-image'
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'status'
            );

        $article->refresh();

        $this->assertNotNull(
            $article
                ->hero_media_id
        );

        $this->assertNotNull(
            $article
                ->hero_image_path
        );

        $media =
            MediaAsset::query()
                ->findOrFail(
                    $article
                        ->hero_media_id
                );

        Storage::disk('public')
            ->assertExists(
                $media->path
            );

        $this->assertSame(
            'article-heroes-ai',
            $media->folder
        );

        $this->assertDatabaseHas(
            'ai_translation_runs',
            [
                'content_type' =>
                    'article_image',
                'content_id' =>
                    $article->id,
                'provider' =>
                    'openai',
                'model' =>
                    'gpt-image-2',
                'status' =>
                    'success',
                'total_tokens' =>
                    142,
            ]
        );

        Http::assertSent(
            function ($request): bool {
                return $request->url()
                    === 'https://api.openai.com/v1/images/generations'
                    && $request[
                        'model'
                    ] === 'gpt-image-2'
                    && $request[
                        'size'
                    ] === '1536x1024'
                    && $request[
                        'quality'
                    ] === 'medium';
            }
        );
    }

    public function test_existing_image_is_never_overwritten_by_generator(): void
    {
        Storage::fake('public');

        $editor =
            $this->editor();

        $article =
            $this->article(
                $editor,
                true
            );

        $this->configureOpenAi();

        Http::fake();

        $originalMediaId =
            $article
                ->hero_media_id;

        $this->actingAs(
            $editor
        )
            ->post(
                '/admin/articles/'
                . $article->id
                . '/generate-image'
            )
            ->assertSessionHasErrors(
                'article_ai_image'
            );

        $this->assertSame(
            $originalMediaId,
            $article
                ->fresh()
                ->hero_media_id
        );

        Http::assertNothingSent();
    }
}
