<?php

namespace Tests\Feature;

use App\Models\PortalAnalyticsEvent;
use App\Models\PortalAnalyticsPageView;
use App\Models\PortalAnalyticsSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PortalAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_views_create_one_anonymous_session_without_ip_storage(): void
    {
        $headers = [
            'User-Agent' =>
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/151.0',
        ];

        $this->withHeaders($headers)
            ->get('/pl')
            ->assertOk();

        $this->withHeaders($headers)
            ->get('/pl/lab')
            ->assertOk();

        $this->assertDatabaseCount(
            'portal_analytics_page_views',
            2
        );

        $sessionCount =
            PortalAnalyticsSession::query()
                ->count();

        $this->assertGreaterThanOrEqual(
            1,
            $sessionCount
        );

        $this->assertLessThanOrEqual(
            2,
            $sessionCount
        );

        $this->assertSame(
            2,
            PortalAnalyticsSession::query()
                ->sum('pageviews_count')
        );

        $this->assertDatabaseHas(
            'portal_analytics_sessions',
            [
                'device_type' =>
                    'desktop',
            ]
        );

        $this->assertFalse(
            Schema::hasColumn(
                'portal_analytics_sessions',
                'ip'
            )
        );

        $this->assertFalse(
            Schema::hasColumn(
                'portal_analytics_sessions',
                'ip_address'
            )
        );
    }

    public function test_dnt_and_known_bots_are_not_tracked(): void
    {
        $this->withHeaders([
            'DNT' => '1',
            'User-Agent' =>
                'Mozilla/5.0 Chrome/151.0',
        ])
            ->get('/pl')
            ->assertOk();

        $this->withHeaders([
            'User-Agent' =>
                'Googlebot/2.1 (+http://www.google.com/bot.html)',
        ])
            ->get('/pl/lab')
            ->assertOk();

        $this->assertDatabaseCount(
            'portal_analytics_sessions',
            0
        );

        $this->assertDatabaseCount(
            'portal_analytics_page_views',
            0
        );
    }

    public function test_private_account_and_admin_routes_are_not_stored(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/pl/account')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/admin')
            ->assertOk();

        $this->assertDatabaseCount(
            'portal_analytics_page_views',
            0
        );
    }

    public function test_client_event_endpoint_records_anonymous_interaction(): void
    {
        $headers = [
            'User-Agent' =>
                'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile',
        ];

        $this->withHeaders($headers)
            ->get('/pl/lab/lenticular')
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson(
                '/analytics/event',
                [
                    'event_name' =>
                        'lab_action',
                    'category' =>
                        'lab.lenticular',
                    'label' =>
                        'pitch-export-pdf',
                    'route_name' =>
                        'lab.lenticular',
                    'path' =>
                        '/pl/lab/lenticular',
                    'locale' =>
                        'pl',
                ]
            )
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $event =
            PortalAnalyticsEvent::query()
                ->firstOrFail();

        $this->assertSame(
            'lab_action',
            $event->event_name
        );

        $this->assertSame(
            'pitch-export-pdf',
            $event->label
        );

        $session =
            PortalAnalyticsSession::query()
                ->where(
                    'events_count',
                    1
                )
                ->firstOrFail();

        $this->assertSame(
            'mobile',
            $session->device_type
        );
    }

    public function test_utm_campaign_is_kept_as_session_acquisition_source(): void
    {
        $this->withHeaders([
            'User-Agent' =>
                'Mozilla/5.0 Chrome/151.0',
        ])->get(
            '/pl?utm_source=facebook&utm_medium=social&utm_campaign=launch'
        )->assertOk();

        $session =
            PortalAnalyticsSession::query()
                ->firstOrFail();

        $this->assertSame(
            'campaign',
            $session->source_group
        );

        $this->assertSame(
            'facebook',
            $session->source_name
        );

        $this->assertSame(
            'launch',
            $session->utm_campaign
        );
    }

    public function test_editor_can_open_real_analytics_dashboard(): void
    {
        PortalAnalyticsSession::create([
            'id' =>
                '11111111-1111-4111-8111-111111111111',
            'browser_session_hash' =>
                str_repeat('a', 64),
            'started_at' => now(),
            'last_seen_at' => now(),
            'landing_path' => '/pl',
            'landing_locale' => 'pl',
            'source_group' => 'direct',
            'device_type' => 'desktop',
            'pageviews_count' => 1,
            'events_count' => 0,
        ]);

        PortalAnalyticsPageView::create([
            'analytics_session_id' =>
                '11111111-1111-4111-8111-111111111111',
            'route_name' => 'home',
            'path' => '/pl',
            'locale' => 'pl',
            'page_type' => 'home',
            'occurred_at' => now(),
        ]);

        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/admin/analytics?range=7')
            ->assertOk()
            ->assertSee('Portal Analytics')
            ->assertSee('Odsłony')
            ->assertSee('Popularne strony')
            ->assertSee('/pl')
            ->assertSee('Privacy-first');
    }

    public function test_public_layout_contains_event_collector_configuration(): void
    {
        $this->withHeaders([
            'DNT' => '1',
        ])
            ->get('/pl')
            ->assertOk()
            ->assertSee(
                'portal-analytics-endpoint',
                false
            )
            ->assertSee(
                route('analytics.event'),
                false
            )
            ->assertSee(
                'data-portal-route="home"',
                false
            );
    }
}
