<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleTranslationStatus;
use App\Enums\DiscoveryDecision;
use App\Enums\DiscoveryRunStatus;
use App\Enums\OrchestratorItemStatus;
use App\Enums\OrchestratorPlanStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\DiscoveryRun;
use App\Models\OrchestratorPlan;
use App\Models\OrchestratorPlanItem;
use App\Models\OrchestratorRun;
use App\Models\User;
use App\Services\AiTranslationSettingsService;
use App\Services\OrchestratorSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContentOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_cannot_access_orchestrator(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/admin/orchestrator')
            ->assertForbidden();
    }

    public function test_admin_can_access_orchestrator_but_not_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/orchestrator')
            ->assertOk()
            ->assertSee('Content Orchestrator');

        $this->actingAs($admin)
            ->get('/admin/settings/orchestrator')
            ->assertForbidden();
    }

    public function test_super_admin_can_save_orchestrator_settings_without_env(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $category = $this->category();

        $this->actingAs($superAdmin)
            ->put('/admin/settings/orchestrator', [
                'enabled' => '1',
                'provider' => 'openai',
                'openai_model' => 'gpt-5.6',
                'gemini_model' => 'gemini-3.7-flash',
                'timeout' => 120,
                'weekly_article_limit' => 3,
                'min_relevance' => 75,
                'target_words' => 900,
                'source_locale' => 'pl',
                'default_category_id' =>
                    $category->id,
                'schedule_slots' =>
                    "1@09:00\n3@09:00\n5@09:00",
                'extra_instructions' =>
                    'Prefer practical topics.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(
            'app_settings',
            [
                'group' => 'orchestrator',
                'key' => 'default_category_id',
            ]
        );

        $settings = app(
            OrchestratorSettingsService::class
        );

        $this->assertTrue(
            $settings->enabled()
        );

        $this->assertSame(
            75,
            $settings->minRelevance()
        );

        $this->assertSame(
            $category->id,
            $settings->defaultCategoryId()
        );
    }

    public function test_plan_uses_only_accepted_unused_candidates(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->configure($this->category());

        $accepted = $this->candidate(
            $admin,
            DiscoveryDecision::Accepted,
            'Accepted stereo topic',
            92
        );

        $rejected = $this->candidate(
            $admin,
            DiscoveryDecision::Rejected,
            'Rejected topic',
            99
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response(
                    $this->openAiResponse(
                        [
                            'summary' =>
                                'Balanced weekly plan.',
                            'items' => [
                                [
                                    'candidate_id' =>
                                        $accepted->id,
                                    'planned_title' =>
                                        'Nowy etap fotografii przestrzennej',
                                    'editorial_angle' =>
                                        'Wyjaśnić praktyczne znaczenie zmiany.',
                                    'rationale' =>
                                        'Wysoka trafność i dobre źródła.',
                                    'suggested_section' =>
                                        'spatial',
                                ],
                                [
                                    'candidate_id' =>
                                        $rejected->id,
                                    'planned_title' =>
                                        'This must be ignored',
                                    'editorial_angle' =>
                                        'Ignored.',
                                    'rationale' =>
                                        'Ignored.',
                                    'suggested_section' =>
                                        'technology',
                                ],
                            ],
                        ],
                        220,
                        80
                    ),
                    200
                ),
        ]);

        $this->actingAs($admin)
            ->post('/admin/orchestrator/plans', [
                'week_start' =>
                    '2026-08-31',
                'article_limit' => 3,
            ])
            ->assertRedirect();

        $plan = OrchestratorPlan::query()
            ->with('items')
            ->firstOrFail();

        $this->assertSame(
            OrchestratorPlanStatus::Draft,
            $plan->status
        );

        $this->assertCount(
            1,
            $plan->items
        );

        $this->assertSame(
            $accepted->id,
            $plan->items->first()
                ->discovery_candidate_id
        );

        $this->assertSame(
            '2026-08-31 09:00',
            $plan->items->first()
                ->planned_for
                ->format('Y-m-d H:i')
        );

        $run = OrchestratorRun::query()
            ->where('action', 'plan')
            ->firstOrFail();

        $this->assertSame(
            'success',
            $run->status
        );

        $this->assertSame(
            300,
            $run->total_tokens
        );

        Http::assertSent(
            function ($request) use ($accepted) {
                $data = $request->data();
                $input = json_encode(
                    $data['input'] ?? []
                );

                return $request->url()
                        === 'https://api.openai.com/v1/responses'
                    && ! array_key_exists(
                        'tools',
                        $data
                    )
                    && str_contains(
                        (string) $input,
                        (string) $accepted->id
                    )
                    && data_get(
                        $data,
                        'text.format.type'
                    ) === 'json_schema';
            }
        );
    }

    public function test_article_draft_cannot_be_generated_before_plan_approval(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->configure($this->category());

        $item = $this->planItem(
            $admin,
            OrchestratorPlanStatus::Draft
        );

        Http::fake();

        $this->actingAs($admin)
            ->from(
                '/admin/orchestrator/plans/'
                . $item->plan->id
            )
            ->post(
                '/admin/orchestrator/items/'
                . $item->id
                . '/draft'
            )
            ->assertRedirect(
                '/admin/orchestrator/plans/'
                . $item->plan->id
            )
            ->assertSessionHasErrors(
                'orchestrator'
            );

        $this->assertDatabaseCount(
            'articles',
            0
        );

        Http::assertNothingSent();
    }

    public function test_approved_plan_creates_article_draft_with_verified_source_list(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $category = $this->category();

        $this->configure($category);

        $item = $this->planItem(
            $admin,
            OrchestratorPlanStatus::Approved
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
                Http::response(
                    $this->openAiResponse(
                        [
                            'article' => [
                                'title' =>
                                    'Jak zmienia się fotografia przestrzenna',
                                'excerpt' =>
                                    'Nowe rozwiązanie pokazuje kierunek rozwoju obrazowania 3D.',
                                'body_html' =>
                                    '<p>To oryginalny szkic oparty wyłącznie na pakiecie Discovery.</p><h2>Co się zmieniło</h2><p>Zweryfikowane fakty zostały połączone w jeden kontekst.</p>',
                                'seo_title' =>
                                    'Fotografia przestrzenna — nowy etap',
                                'seo_description' =>
                                    'Wyjaśniamy znaczenie nowego rozwiązania dla fotografii przestrzennej.',
                            ],
                        ],
                        450,
                        280
                    ),
                    200
                ),
        ]);

        $this->actingAs($admin)
            ->post(
                '/admin/orchestrator/items/'
                . $item->id
                . '/draft'
            )
            ->assertRedirect();

        $article = Article::query()
            ->with('translations')
            ->firstOrFail();

        $this->assertSame(
            ArticleStatus::Draft,
            $article->status
        );

        $this->assertSame(
            $category->id,
            $article->category_id
        );

        $this->assertSame(
            'pl',
            $article->source_locale
        );

        $translation =
            $article->translation('pl');

        $this->assertNotNull(
            $translation
        );

        $this->assertSame(
            ArticleTranslationStatus::Source,
            $translation->translation_status
        );

        $this->assertStringContainsString(
            '<h2>Źródła</h2>',
            $translation->body_html
        );

        $this->assertStringContainsString(
            'https://primary.example/source',
            $translation->body_html
        );

        $this->assertStringContainsString(
            'https://research.example/study',
            $translation->body_html
        );

        $item->refresh();

        $this->assertSame(
            OrchestratorItemStatus::DraftCreated,
            $item->status
        );

        $this->assertSame(
            $article->id,
            $item->article_id
        );

        $this->assertSame(
            OrchestratorPlanStatus::Completed,
            $item->plan->fresh()->status
        );

        $run = OrchestratorRun::query()
            ->where('action', 'draft')
            ->firstOrFail();

        $this->assertSame(
            730,
            $run->total_tokens
        );

        Http::assertSent(
            fn ($request) =>
                $request->url()
                    === 'https://api.openai.com/v1/responses'
                && ! array_key_exists(
                    'tools',
                    $request->data()
                )
        );
    }

    public function test_approved_candidate_cannot_be_reused_in_another_plan(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->configure($this->category());

        $item = $this->planItem(
            $admin,
            OrchestratorPlanStatus::Draft
        );

        $service = app(
            \App\Services\OrchestratorService::class
        );

        $this->assertSame(
            0,
            $service->availableAcceptedCount()
        );

        $this->assertDatabaseHas(
            'orchestrator_plan_items',
            [
                'discovery_candidate_id' =>
                    $item->candidate->id,
            ]
        );
    }

    private function category(): ArticleCategory
    {
        return ArticleCategory::create([
            'name' => 'Aktualności 3D',
            'slug' =>
                'aktualnosci-3d-'
                . uniqid(),
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function configure(
        ArticleCategory $category
    ): void {
        $ai = app(
            AiTranslationSettingsService::class
        );

        $ai->set(
            'openai.api_key',
            'sk-test',
            true
        );

        $settings = app(
            OrchestratorSettingsService::class
        );

        $settings->set('enabled', '1');
        $settings->set(
            'provider',
            'openai'
        );
        $settings->set(
            'openai.model',
            'gpt-5.6'
        );
        $settings->set(
            'weekly_article_limit',
            '3'
        );
        $settings->set(
            'min_relevance',
            '70'
        );
        $settings->set(
            'target_words',
            '900'
        );
        $settings->set(
            'source_locale',
            'pl'
        );
        $settings->set(
            'default_category_id',
            (string) $category->id
        );
        $settings->set(
            'schedule_slots',
            "1@09:00\n3@09:00\n5@09:00"
        );
    }

    private function candidate(
        User $user,
        DiscoveryDecision $decision,
        string $title,
        int $relevance
    ) {
        $run = DiscoveryRun::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'model' => 'gpt-5.6-terra',
            'status' =>
                DiscoveryRunStatus::Completed,
            'topic' => 'spatial imaging',
            'query' => 'spatial imaging',
            'freshness_days' => 7,
            'requested_candidates' => 5,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $candidate =
            $run->candidates()->create([
                'fingerprint' =>
                    hash(
                        'sha256',
                        $title . uniqid()
                    ),
                'cluster_key' =>
                    Str::slug($title)
                    . '-'
                    . uniqid(),
                'title' => $title,
                'angle' =>
                    'Explain the practical significance.',
                'summary' =>
                    'A verified research summary.',
                'suggested_section' =>
                    'spatial',
                'relevance_score' =>
                    $relevance,
                'novelty_score' => 85,
                'confidence_score' => 90,
                'facts' => [
                    [
                        'fact' =>
                            'Verified fact one.',
                        'source_urls' => [
                            'https://primary.example/source',
                            'https://research.example/study',
                        ],
                    ],
                ],
                'keywords' => [
                    'spatial',
                    'stereoscopy',
                ],
                'decision' => $decision,
                'decision_by' =>
                    $user->id,
                'decided_at' => now(),
                'decision_note' =>
                    'Useful for the portal.',
            ]);

        $candidate->sources()->createMany([
            [
                'url' =>
                    'https://primary.example/source',
                'url_hash' =>
                    hash(
                        'sha256',
                        'https://primary.example/source'
                    ),
                'title' =>
                    'Primary announcement',
                'domain' =>
                    'primary.example',
                'language' => 'en',
                'source_type' => 'primary',
                'credibility_score' => 95,
            ],
            [
                'url' =>
                    'https://research.example/study',
                'url_hash' =>
                    hash(
                        'sha256',
                        'https://research.example/study'
                    ),
                'title' =>
                    'Independent study',
                'domain' =>
                    'research.example',
                'language' => 'en',
                'source_type' => 'research',
                'credibility_score' => 92,
            ],
        ]);

        return $candidate->fresh(
            'sources'
        );
    }

    private function planItem(
        User $admin,
        OrchestratorPlanStatus $status
    ): OrchestratorPlanItem {
        $candidate = $this->candidate(
            $admin,
            DiscoveryDecision::Accepted,
            'Spatial imaging candidate',
            95
        );

        $plan = OrchestratorPlan::create([
            'week_start' =>
                '2026-08-31',
            'week_end' =>
                '2026-09-06',
            'status' => $status,
            'provider' => 'openai',
            'model' => 'gpt-5.6',
            'created_by' => $admin->id,
            'approved_by' =>
                $status
                    === OrchestratorPlanStatus::Approved
                    ? $admin->id
                    : null,
            'approved_at' =>
                $status
                    === OrchestratorPlanStatus::Approved
                    ? now()
                    : null,
        ]);

        return $plan->items()->create([
            'discovery_candidate_id' =>
                $candidate->id,
            'position' => 1,
            'planned_for' =>
                '2026-08-31 09:00:00',
            'planned_title' =>
                'Planned spatial imaging article',
            'editorial_angle' =>
                'Explain what changes for readers.',
            'rationale' =>
                'High relevance and confidence.',
            'suggested_section' =>
                'spatial',
            'status' =>
                OrchestratorItemStatus::Planned,
        ])->load([
            'plan',
            'candidate.sources',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function openAiResponse(
        array $payload,
        int $inputTokens,
        int $outputTokens
    ): array {
        return [
            'output' => [
                [
                    'type' => 'message',
                    'content' => [
                        [
                            'type' =>
                                'output_text',
                            'text' =>
                                json_encode(
                                    $payload,
                                    JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                ),
                        ],
                    ],
                ],
            ],
            'usage' => [
                'input_tokens' =>
                    $inputTokens,
                'output_tokens' =>
                    $outputTokens,
                'total_tokens' =>
                    $inputTokens
                    + $outputTokens,
            ],
        ];
    }
}
