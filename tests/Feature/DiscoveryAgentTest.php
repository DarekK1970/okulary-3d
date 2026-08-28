<?php

namespace Tests\Feature;

use App\Enums\DiscoveryDecision;
use App\Enums\DiscoveryRunStatus;
use App\Models\AppSetting;
use App\Models\DiscoveryCandidate;
use App\Models\DiscoveryRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscoveryAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_cannot_access_discovery_agent(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/admin/discovery')
            ->assertForbidden();
    }

    public function test_admin_can_access_discovery_but_not_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/discovery')
            ->assertOk()
            ->assertSee('Discovery Agent');

        $this->actingAs($admin)
            ->get('/admin/settings/discovery')
            ->assertForbidden();
    }

    public function test_super_admin_can_update_discovery_settings_without_env(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->put('/admin/settings/discovery', [
                'enabled' => '1',
                'provider' => 'openai',
                'openai_model' => 'gpt-5.6-terra',
                'gemini_model' => 'gemini-3.7-flash',
                'timeout' => 120,
                'freshness_days' => 14,
                'candidate_limit' => 10,
                'min_sources' => 2,
                'min_domains' => 2,
                'exclude_polish_sources' => '1',
                'topics' => "stereoscopy\nlenticular printing",
                'preferred_domains' => "si.edu\nsony.com",
                'excluded_domains' => "spam.example\nexample.pl",
                'extra_instructions' => 'Prefer primary sources.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('app_settings', [
            'group' => 'discovery',
            'key' => 'provider',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'group' => 'discovery',
            'key' => 'topics',
        ]);
    }

    public function test_openai_discovery_saves_only_multi_source_candidate(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->configureOpenAiDiscovery();

        Http::fake([
            'api.openai.com/*' => Http::response(
                $this->openAiResponse([
                    $this->candidate(
                        'spatial-camera-release',
                        'New spatial camera platform',
                        [
                            $this->source(
                                'https://manufacturer.example/news/spatial-camera',
                                'manufacturer.example'
                            ),
                            $this->source(
                                'https://research.example/spatial-imaging-study',
                                'research.example',
                                'research'
                            ),
                        ]
                    ),
                    $this->candidate(
                        'single-source-item',
                        'Unsupported single-source item',
                        [
                            $this->source(
                                'https://only.example/story',
                                'only.example'
                            ),
                        ]
                    ),
                ]),
                200
            ),
        ]);

        $this->actingAs($admin)
            ->post('/admin/discovery/run', [
                'topic' => 'spatial imaging hardware',
                'query' => 'Find recent spatial imaging launches.',
                'freshness_days' => 7,
                'candidate_limit' => 10,
            ])
            ->assertRedirect('/admin/discovery');

        $run = DiscoveryRun::query()->firstOrFail();

        $this->assertSame(
            DiscoveryRunStatus::Completed,
            $run->status
        );
        $this->assertSame(1, $run->saved_candidates);
        $this->assertSame(1, $run->skipped_candidates);
        $this->assertSame(0, $run->duplicate_candidates);
        $this->assertSame(300, $run->total_tokens);

        $candidate = DiscoveryCandidate::query()
            ->with('sources')
            ->firstOrFail();

        $this->assertSame(
            'New spatial camera platform',
            $candidate->title
        );
        $this->assertSame(
            DiscoveryDecision::Pending,
            $candidate->decision
        );
        $this->assertCount(2, $candidate->sources);
        $this->assertCount(2, $candidate->sources->pluck('domain')->unique());

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url()
                    === 'https://api.openai.com/v1/responses'
                && data_get($data, 'tools.0.type') === 'web_search'
                && data_get($data, 'text.format.type') === 'json_schema';
        });
    }

    public function test_polish_sources_are_removed_before_source_threshold_check(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->configureOpenAiDiscovery();

        Http::fake([
            'api.openai.com/*' => Http::response(
                $this->openAiResponse([
                    $this->candidate(
                        'mixed-domains',
                        'Mixed source languages',
                        [
                            $this->source(
                                'https://foreign.example/story',
                                'foreign.example'
                            ),
                            $this->source(
                                'https://portal.pl/artykul',
                                'portal.pl',
                                'news_media',
                                'pl'
                            ),
                        ]
                    ),
                ]),
                200
            ),
        ]);

        $this->actingAs($admin)
            ->post('/admin/discovery/run', [
                'topic' => '3D photography',
                'query' => 'Recent 3D photography news',
                'freshness_days' => 7,
                'candidate_limit' => 5,
            ])
            ->assertRedirect('/admin/discovery');

        $run = DiscoveryRun::query()->firstOrFail();

        $this->assertSame(0, $run->saved_candidates);
        $this->assertSame(1, $run->skipped_candidates);
        $this->assertDatabaseCount('discovery_candidates', 0);
    }

    public function test_repeated_cluster_is_marked_as_duplicate(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->configureOpenAiDiscovery();

        $payload = $this->openAiResponse([
            $this->candidate(
                'same-cluster-key',
                'First discovery title',
                [
                    $this->source(
                        'https://one.example/a',
                        'one.example'
                    ),
                    $this->source(
                        'https://two.example/b',
                        'two.example'
                    ),
                ]
            ),
        ]);

        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push($payload, 200)
                ->push($payload, 200),
        ]);

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($admin)
                ->post('/admin/discovery/run', [
                    'topic' => 'stereoscopy',
                    'query' => 'new stereoscopy developments',
                    'freshness_days' => 7,
                    'candidate_limit' => 5,
                ])
                ->assertRedirect('/admin/discovery');
        }

        $this->assertDatabaseCount('discovery_candidates', 1);

        $secondRun = DiscoveryRun::query()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(1, $secondRun->duplicate_candidates);
        $this->assertSame(0, $secondRun->saved_candidates);
    }

    public function test_admin_can_accept_candidate_for_future_orchestrator(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $run = DiscoveryRun::create([
            'user_id' => $admin->id,
            'provider' => 'openai',
            'model' => 'gpt-5.6-terra',
            'status' => DiscoveryRunStatus::Completed,
            'topic' => 'lenticular',
            'query' => 'lenticular',
            'freshness_days' => 7,
            'requested_candidates' => 5,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $candidate = $run->candidates()->create([
            'fingerprint' => hash('sha256', 'candidate'),
            'cluster_key' => 'candidate',
            'title' => 'Candidate topic',
            'summary' => 'Research summary.',
            'suggested_section' => 'lenticular',
            'relevance_score' => 90,
            'novelty_score' => 80,
            'confidence_score' => 85,
            'facts' => [
                [
                    'fact' => 'A verified fact.',
                    'source_urls' => ['https://example.com/source'],
                ],
            ],
            'keywords' => ['lenticular'],
            'decision' => DiscoveryDecision::Pending,
        ]);

        $this->actingAs($admin)
            ->patch(
                '/admin/discovery/'
                . $candidate->id
                . '/decision',
                [
                    'decision' => 'accepted',
                    'decision_note' => 'Use in the next editorial plan.',
                ]
            )
            ->assertRedirect();

        $candidate->refresh();

        $this->assertSame(
            DiscoveryDecision::Accepted,
            $candidate->decision
        );
        $this->assertSame($admin->id, $candidate->decision_by);
        $this->assertNotNull($candidate->decided_at);
    }

    private function configureOpenAiDiscovery(): void
    {
        $this->setting(
            'ai_translation',
            'openai.api_key',
            'test-openai-key',
            true
        );

        $this->setting('discovery', 'enabled', '1');
        $this->setting('discovery', 'provider', 'openai');
        $this->setting(
            'discovery',
            'openai.model',
            'gpt-5.6-terra'
        );
        $this->setting('discovery', 'min_sources', '2');
        $this->setting('discovery', 'min_domains', '2');
        $this->setting(
            'discovery',
            'exclude_polish_sources',
            '1'
        );
    }

    private function setting(
        string $group,
        string $key,
        string $value,
        bool $secret = false
    ): void {
        AppSetting::query()->create([
            'group' => $group,
            'key' => $key,
            'value' => $value,
            'is_secret' => $secret,
        ]);
    }

    /** @param list<array<string, mixed>> $candidates */
    private function openAiResponse(array $candidates): array
    {
        return [
            'output' => [
                [
                    'type' => 'message',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => json_encode(
                                ['candidates' => $candidates],
                                JSON_UNESCAPED_SLASHES
                                | JSON_UNESCAPED_UNICODE
                            ),
                        ],
                    ],
                ],
            ],
            'usage' => [
                'input_tokens' => 180,
                'output_tokens' => 120,
                'total_tokens' => 300,
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $sources
     * @return array<string, mixed>
     */
    private function candidate(
        string $clusterKey,
        string $title,
        array $sources
    ): array {
        return [
            'cluster_key' => $clusterKey,
            'title' => $title,
            'angle' => 'Explain why this matters for 3D users.',
            'summary' => 'A concise research summary supported by sources.',
            'suggested_section' => 'technology',
            'relevance_score' => 91,
            'novelty_score' => 84,
            'confidence_score' => 88,
            'facts' => [
                [
                    'fact' => 'A factual point supported by the source set.',
                    'source_urls' => array_column($sources, 'url'),
                ],
            ],
            'keywords' => ['3D', 'spatial'],
            'sources' => $sources,
        ];
    }

    /** @return array<string, mixed> */
    private function source(
        string $url,
        string $domain,
        string $type = 'manufacturer',
        string $language = 'en'
    ): array {
        return [
            'url' => $url,
            'title' => 'Source title',
            'domain' => $domain,
            'language' => $language,
            'published_at' => '2026-08-27T12:00:00Z',
            'excerpt' => 'Short paraphrased source note.',
            'source_type' => $type,
            'credibility_score' => 90,
        ];
    }
}
