<?php

namespace Tests\Feature;

use App\Models\PortalAnalyticsBotRequest;
use App\Models\PortalAnalyticsPageView;
use App\Models\PortalAnalyticsSession;
use App\Models\User;
use App\Services\BotDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotTrafficAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_bot_is_recorded_separately_and_never_creates_human_visit(): void
    {
        $this->withHeaders([
            'User-Agent' =>
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])
            ->get('/pl')
            ->assertOk();

        $this->assertDatabaseCount(
            'portal_analytics_bot_requests',
            1
        );

        $this->assertDatabaseHas(
            'portal_analytics_bot_requests',
            [
                'bot_name' =>
                    'Googlebot',
                'category' =>
                    BotDetectorService
                        ::CATEGORY_SEARCH,
                'path' => '/pl',
                'method' => 'GET',
                'status_code' => 200,
            ]
        );

        $this->assertDatabaseCount(
            'portal_analytics_sessions',
            0
        );

        $this->assertDatabaseCount(
            'portal_analytics_page_views',
            0
        );
    }

    public function test_ai_seo_social_and_other_bots_are_classified(): void
    {
        $cases = [
            [
                'ua' =>
                    'PerplexityBot/1.0 (+https://perplexity.ai/perplexitybot)',
                'name' =>
                    'PerplexityBot',
                'category' =>
                    BotDetectorService
                        ::CATEGORY_AI,
            ],
            [
                'ua' =>
                    'Mozilla/5.0 (compatible; SerpstatBot/2.1; +https://serpstatbot.com/)',
                'name' =>
                    'SerpstatBot',
                'category' =>
                    BotDetectorService
                        ::CATEGORY_SEO,
            ],
            [
                'ua' =>
                    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
                'name' =>
                    'FacebookExternalHit',
                'category' =>
                    BotDetectorService
                        ::CATEGORY_SOCIAL,
            ],
            [
                'ua' =>
                    'AwarioBot/1.0',
                'name' =>
                    'AwarioBot',
                'category' =>
                    BotDetectorService
                        ::CATEGORY_OTHER,
            ],
        ];

        foreach (
            $cases
            as $case
        ) {
            $this->withHeaders([
                'User-Agent' =>
                    $case['ua'],
            ])
                ->get('/pl')
                ->assertOk();

            $this->assertDatabaseHas(
                'portal_analytics_bot_requests',
                [
                    'bot_name' =>
                        $case['name'],
                    'category' =>
                        $case['category'],
                ]
            );
        }

        $this->assertDatabaseCount(
            'portal_analytics_bot_requests',
            4
        );

        $this->assertDatabaseCount(
            'portal_analytics_sessions',
            0
        );
    }

    public function test_generic_automation_client_is_not_counted_as_unique_visit(): void
    {
        $this->withHeaders([
            'User-Agent' =>
                'python-requests/2.32.4',
        ])
            ->get('/pl')
            ->assertOk();

        $this->assertDatabaseCount(
            'portal_analytics_bot_requests',
            1
        );

        $this->assertDatabaseCount(
            'portal_analytics_sessions',
            0
        );
    }

    public function test_normal_browser_still_creates_human_analytics_and_not_bot_record(): void
    {
        $this->withHeaders([
            'User-Agent' =>
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/151.0 Safari/537.36',
        ])
            ->get('/pl')
            ->assertOk();

        $this->assertDatabaseCount(
            'portal_analytics_bot_requests',
            0
        );

        $this->assertDatabaseCount(
            'portal_analytics_page_views',
            1
        );

        $this->assertGreaterThanOrEqual(
            1,
            PortalAnalyticsSession::query()
                ->count()
        );
    }

    public function test_private_route_bot_path_does_not_store_concrete_secret_parameter(): void
    {
        /*
         * We test the safe-path rule through the login route,
         * which is private analytics-wise but contains no dynamic
         * secret. The route template must still be used.
         */
        $this->withHeaders([
            'User-Agent' =>
                'Googlebot/2.1',
        ])
            ->get('/pl/login')
            ->assertOk();

        $bot =
            PortalAnalyticsBotRequest::query()
                ->firstOrFail();

        $this->assertSame(
            '/{locale}/login',
            $bot->path
        );
    }

    public function test_bot_dashboard_is_visible_and_human_metrics_remain_zero(): void
    {
        $this->withHeaders([
            'User-Agent' =>
                'PerplexityBot/1.0',
        ])
            ->get('/pl')
            ->assertOk();

        $this->withHeaders([
            'User-Agent' =>
                'SerpstatBot/2.1',
        ])
            ->get('/pl/lab')
            ->assertOk();

        $editor = User::factory()
            ->create([
                'role' =>
                    User::ROLE_EDITOR,
            ]);

        $response =
            $this->actingAs(
                $editor
            )
                ->get(
                    '/admin/analytics?range=7'
                );

        $response
            ->assertOk()
            ->assertSee(
                'Boty internetowe'
            )
            ->assertSee(
                'PerplexityBot'
            )
            ->assertSee(
                'SerpstatBot'
            )
            ->assertSee(
                'Unikalne wizyty'
            );

        $this->assertSame(
            0,
            PortalAnalyticsPageView::query()
                ->count()
        );
    }

    public function test_bot_user_agent_raw_value_is_not_stored(): void
    {
        $agent =
            'VerySpecificSecretLikeBot/9.9 (+https://example.test/bot)';

        $this->withHeaders([
            'User-Agent' => $agent,
        ])
            ->get('/pl')
            ->assertOk();

        $bot =
            PortalAnalyticsBotRequest::query()
                ->firstOrFail();

        $this->assertNotNull(
            $bot->user_agent_hash
        );

        $this->assertSame(
            64,
            strlen(
                $bot->user_agent_hash
            )
        );

        $attributes =
            $bot->getAttributes();

        $this->assertArrayNotHasKey(
            'user_agent',
            $attributes
        );
    }
}
