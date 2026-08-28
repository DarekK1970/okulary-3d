<?php

namespace Tests\Feature;

use App\Enums\ArchiveTranslationStatus;
use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Enums\CatalogTranslationStatus;
use App\Models\AiTranslationRun;
use App\Models\AppSetting;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\AiTranslationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTranslatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_open_translator_but_not_ai_settings(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/admin/translations')
            ->assertOk()
            ->assertSee('AI Translator')
            ->assertSee('Artykuły')
            ->assertSee('Archiwum')
            ->assertDontSee('Kategorie produktów');

        $this->actingAs($editor)
            ->get('/admin/settings/ai-translation')
            ->assertForbidden();
    }

    public function test_super_admin_can_save_provider_keys_without_env_editing(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->put('/admin/settings/ai-translation', [
                'enabled' => '1',
                'provider' => 'openai',
                'timeout' => 75,
                'openai_model' => 'gpt-5.6',
                'gemini_model' => 'gemini-3.7-flash',
                'openai_api_key' => 'sk-test-openai-secret',
                'gemini_api_key' => 'gemini-test-secret',
                'glossary' => 'stereocard = karta stereoskopowa',
            ])
            ->assertRedirect();

        $settings = app(
            AiTranslationSettingsService::class
        );

        $this->assertTrue($settings->enabled());
        $this->assertSame(
            'openai',
            $settings->provider()
        );
        $this->assertSame(
            'sk-test-openai-secret',
            $settings->apiKey('openai')
        );
        $this->assertSame(
            'gemini-test-secret',
            $settings->apiKey('gemini')
        );

        $raw = DB::table('app_settings')
            ->where('group', 'ai_translation')
            ->where('key', 'openai.api_key')
            ->value('value');

        $this->assertNotSame(
            'sk-test-openai-secret',
            $raw
        );
    }

    public function test_openai_translates_article_to_draft_and_logs_tokens(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $settings = app(
            AiTranslationSettingsService::class
        );

        $settings->set('enabled', '1');
        $settings->set('provider', 'openai');
        $settings->set('openai.model', 'gpt-5.6');
        $settings->set(
            'openai.api_key',
            'sk-test',
            true
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response([
                    'output' => [
                        [
                            'type' => 'message',
                            'content' => [
                                [
                                    'type' => 'output_text',
                                    'text' => json_encode([
                                        'title' => 'How stereoscopy works',
                                        'excerpt' => 'A short introduction.',
                                        'body_html' => '<p>Stereoscopy creates an impression of depth.</p>',
                                        'seo_title' => 'How stereoscopy works',
                                        'seo_description' => 'Learn the basics of stereoscopic imaging.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                    'usage' => [
                        'input_tokens' => 120,
                        'output_tokens' => 80,
                        'total_tokens' => 200,
                    ],
                ], 200),
        ]);

        $article = $this->article($editor);

        $this->actingAs($editor)
            ->post(
                '/admin/translations/article/'
                . $article->id
            )
            ->assertRedirect();

        $article->load('translations');
        $english = $article->translation('en');

        $this->assertNotNull($english);
        $this->assertSame(
            'How stereoscopy works',
            $english->title
        );
        $this->assertSame(
            ArticleTranslationStatus::Draft,
            $english->translation_status
        );
        $this->assertStringContainsString(
            '<p>Stereoscopy creates an impression of depth.</p>',
            $english->body_html
        );

        $run = AiTranslationRun::query()
            ->latest()
            ->firstOrFail();

        $this->assertSame('success', $run->status);
        $this->assertSame('openai', $run->provider);
        $this->assertSame(200, $run->total_tokens);
        $this->assertSame($editor->id, $run->initiated_by);

        Http::assertSent(function ($request) {
            return $request->url()
                === 'https://api.openai.com/v1/responses'
                && data_get(
                    $request->data(),
                    'text.format.type'
                ) === 'json_schema';
        });
    }

    public function test_ready_translation_is_never_overwritten_by_ai(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $settings = app(
            AiTranslationSettingsService::class
        );
        $settings->set('enabled', '1');
        $settings->set('provider', 'openai');
        $settings->set('openai.model', 'gpt-5.6');
        $settings->set('openai.api_key', 'sk-test', true);

        Http::fake();

        $article = $this->article($editor);
        $article->translations()->create([
            'locale' => 'en',
            'title' => 'Editorial translation',
            'slug' => 'editorial-translation',
            'excerpt' => null,
            'body_html' => '<p>Approved text.</p>',
            'translation_status' =>
                ArticleTranslationStatus::Ready,
        ]);

        $this->actingAs($editor)
            ->from('/admin/translations')
            ->post(
                '/admin/translations/article/'
                . $article->id
            )
            ->assertRedirect('/admin/translations')
            ->assertSessionHasErrors('translation');

        $this->assertSame(
            'Editorial translation',
            $article->fresh()
                ->translation('en')
                ->title
        );

        Http::assertNothingSent();
    }

    public function test_gemini_can_translate_product_category_for_admin(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $settings = app(
            AiTranslationSettingsService::class
        );
        $settings->set('enabled', '1');
        $settings->set('provider', 'gemini');
        $settings->set(
            'gemini.model',
            'gemini-3.7-flash'
        );
        $settings->set(
            'gemini.api_key',
            'gemini-test',
            true
        );

        Http::fake([
            'https://generativelanguage.googleapis.com/*' =>
                Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'name' => 'Anaglyph glasses',
                                            'description' => 'Glasses for red-cyan stereoscopic images.',
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'usageMetadata' => [
                        'promptTokenCount' => 70,
                        'candidatesTokenCount' => 35,
                        'totalTokenCount' => 105,
                    ],
                ], 200),
        ]);

        $category = ProductCategory::create([
            'source_locale' => 'pl',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $category->translations()->create([
            'locale' => 'pl',
            'name' => 'Okulary anaglifowe',
            'slug' => 'okulary-anaglifowe',
            'description' => 'Okulary do obrazów czerwono-cyjanowych.',
            'translation_status' =>
                CatalogTranslationStatus::Source,
        ]);

        $this->actingAs($admin)
            ->post(
                '/admin/translations/product_category/'
                . $category->id
            )
            ->assertRedirect();

        $category->load('translations');
        $english = $category->translation('en');

        $this->assertSame(
            'Anaglyph glasses',
            $english->name
        );
        $this->assertSame(
            CatalogTranslationStatus::Draft,
            $english->translation_status
        );

        $this->assertDatabaseHas(
            'ai_translation_runs',
            [
                'provider' => 'gemini',
                'status' => 'success',
                'total_tokens' => 105,
            ]
        );
    }

    private function article(User $user): Article
    {
        $category = ArticleCategory::create([
            'name' => 'Historia',
            'slug' => 'historia-' . uniqid(),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $article = Article::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'title' => 'Jak działa stereoskopia',
            'slug' => 'jak-dziala-stereoskopia-' . uniqid(),
            'excerpt' => 'Krótki wstęp.',
            'body_html' => '<p>Stereoskopia tworzy wrażenie głębi.</p>',
            'status' => ArticleStatus::Draft,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $article->translations()->create([
            'locale' => 'pl',
            'title' => 'Jak działa stereoskopia',
            'slug' => 'jak-dziala-stereoskopia-' . uniqid(),
            'excerpt' => 'Krótki wstęp.',
            'body_html' => '<p>Stereoskopia tworzy wrażenie głębi.</p>',
            'seo_title' => 'Jak działa stereoskopia',
            'seo_description' => 'Podstawy obrazu stereoskopowego.',
            'translation_status' =>
                ArticleTranslationStatus::Source,
        ]);

        return $article->load('translations');
    }
}
