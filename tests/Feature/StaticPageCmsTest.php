<?php

namespace Tests\Feature;

use App\Models\AiTranslationRun;
use App\Models\StaticPage;
use App\Models\User;
use App\Services\AiTranslationProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class StaticPageCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()
            ->create([
                'role' =>
                    User::ROLE_ADMIN,
            ]);
    }

    public function test_migration_seeds_required_static_pages(): void
    {
        $this->assertSame(
            10,
            StaticPage::query()
                ->count()
        );
        foreach (
            [
                'about',
                'faq',
                'shipping-payments',
                'returns-complaints',
                'privacy-policy',
                'portal-terms',
                'editorial-policy',
                'partner-program',
                'shop-terms',
                'secure-payments',
            ]
            as $key
        ) {
            $this->assertDatabaseHas(
                'static_pages',
                [
                    'key' => $key,
                    'is_active' =>
                        true,
                ]
            );
        }
    }

    public function test_admin_list_has_edit_and_auto_translation_actions(): void
    {
        $this->actingAs(
            $this->admin()
        )
            ->get(
                '/admin/static-pages'
            )
            ->assertOk()
            ->assertSee(
                'Strony statyczne'
            )
            ->assertSee(
                'Sklep'
            )
            ->assertSee(
                'Edytuj'
            )
            ->assertSee(
                'Automatyczne tłumaczenie'
            )
            ->assertSee(
                'Polityka prywatności'
            )
            ->assertSee(
                'Regulamin sklepu'
            );
    }

    public function test_static_page_editor_is_wysiwyg_and_multilingual(): void
    {
        $page = StaticPage::query()
            ->where(
                'key',
                'faq'
            )
            ->firstOrFail();
        $this->actingAs(
            $this->admin()
        )
            ->get(
                '/admin/static-pages/'
                . $page->id
                . '/edit'
            )
            ->assertOk()
            ->assertSee(
                'data-wysiwyg',
                false
            )
            ->assertSee(
                'contenteditable="true"',
                false
            )
            ->assertSee(
                'data-translation-tab="pl"',
                false
            )
            ->assertSee(
                'data-translation-tab="en"',
                false
            );
    }

    public function test_admin_can_save_sanitized_wysiwyg_content(): void
    {
        $page = StaticPage::query()
            ->where(
                'key',
                'privacy-policy'
            )
            ->firstOrFail();
        $this->actingAs(
            $this->admin()
        )
            ->put(
                '/admin/static-pages/'
                . $page->id,
                [
                    'translations' => [
                        'pl' => [
                            'title' =>
                                'Polityka prywatności',
                            'body_html' =>
                                '<h2>Dane</h2>'
                                . '<p>Treść <strong>OK</strong>.</p>'
                                . '<script>alert(1)</script>',
                            'seo_title' =>
                                'Polityka prywatności',
                            'seo_description' =>
                                'Informacje o prywatności.',
                        ],
                        'en' => [
                            'title' => '',
                            'body_html' => '',
                            'seo_title' => '',
                            'seo_description' => '',
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                '/admin/static-pages/'
                . $page->id
                . '/edit'
            );
        $translation =
            $page
                ->fresh()
                ->translation('pl');

        $this->assertNotNull(
            $translation
        );

        $this->assertStringContainsString(
            '<strong>OK</strong>',
            $translation
                ->body_html
        );

        $this->assertStringNotContainsString(
            '<script',
            $translation
                ->body_html
        );
    }

    public function test_automatic_translation_creates_missing_language_version(): void
    {
        $page = StaticPage::query()
            ->where(
                'key',
                'faq'
            )
            ->firstOrFail();
        $page
            ->translations()
            ->where(
                'locale',
                'pl'
            )
            ->update([
                'body_html' =>
                    '<p>Najczęstsze pytania.</p>',
                'seo_description' =>
                    'Najczęstsze pytania i odpowiedzi.',
            ]);
        $this->mock(
            AiTranslationProviderService::class,
            function (
                MockInterface $mock
            ): void {
                $mock
                    ->shouldReceive(
                        'translate'
                    )
                    ->once()
                    ->with(
                        \Mockery::type('array'),
                        'pl',
                        'en',
                        'static_page'
                    )
                    ->andReturn([
                        'fields' => [
                            'title' => 'FAQ',
                            'body_html' =>
                                '<p>Frequently asked questions.</p>',
                            'seo_title' =>
                                'FAQ',
                            'seo_description' =>
                                'Frequently asked questions and answers.',
                        ],
                        'provider' =>
                            'openai',
                        'model' =>
                            'test-model',
                        'input_tokens' =>
                            100,
                        'output_tokens' =>
                            80,
                        'total_tokens' =>
                            180,
                        'raw_text' =>
                            '{"ok":true}',
                    ]);
            }
        );
        $this->actingAs(
            $this->admin()
        )
            ->post(
                '/admin/static-pages/'
                . $page->id
                . '/translate'
            )
            ->assertSessionHas(
                'status'
            );
        $this->assertDatabaseHas(
            'static_page_translations',
            [
                'static_page_id' =>
                    $page->id,
                'locale' => 'en',
                'title' => 'FAQ',
                'translation_status' =>
                    'ready',
            ]
        );
        $this->assertDatabaseHas(
            'ai_translation_runs',
            [
                'content_type' =>
                    'static_page',
                'content_id' =>
                    $page->id,
                'target_locale' =>
                    'en',
                'status' =>
                    'success',
            ]
        );
    }

    public function test_public_page_uses_source_fallback_until_translation_exists(): void
    {
        $this->get(
            '/en/info/faq'
        )
            ->assertOk()
            ->assertSee(
                '<h1>',
                false
            )
            ->assertSee('FAQ');
    }

    public function test_about_page_is_public_and_main_navigation_uses_it(): void
    {
        $this->get('/pl/info/about')
            ->assertOk()
            ->assertSee('O nas')
            ->assertSee(
                '/pl/info/about',
                false
            );
    }

    public function test_footer_links_to_static_pages_instead_of_hashes(): void
    {
        $this->get(
            '/pl/info/faq'
        )
            ->assertOk()
            ->assertSee(
                '/pl/info/faq',
                false
            )
            ->assertSee(
                '/pl/info/shipping-payments',
                false
            )
            ->assertSee(
                '/pl/info/returns-complaints',
                false
            )
            ->assertSee(
                '/pl/info/privacy-policy',
                false
            )
            ->assertSee(
                '/pl/info/portal-terms',
                false
            )
            ->assertSee(
                '/pl/info/editorial-policy',
                false
            )
            ->assertSee(
                '/pl/info/shop-terms',
                false
            )
            ->assertSee(
                '/pl/info/secure-payments',
                false
            );
    }

    public function test_non_admin_cannot_manage_static_pages(): void
    {
        $user = User::factory()
            ->create([
                'role' =>
                    User::ROLE_USER,
            ]);

        $this->actingAs($user)
            ->get(
                '/admin/static-pages'
            )
            ->assertForbidden();
    }
}
