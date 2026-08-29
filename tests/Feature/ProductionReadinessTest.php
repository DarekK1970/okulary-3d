<?php

namespace Tests\Feature;

use App\Models\PortalAnalyticsSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_response_contains_security_headers(): void
    {
        $this->withHeaders([
            'DNT' => '1',
        ])
            ->get('/pl')
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff'
            )
            ->assertHeader(
                'X-Frame-Options',
                'SAMEORIGIN'
            )
            ->assertHeader(
                'Referrer-Policy',
                'strict-origin-when-cross-origin'
            )
            ->assertHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=(), usb=(), payment=(self)'
            );
    }

    public function test_private_pages_are_not_browser_cacheable(): void
    {
        $response = $this->get(
            '/pl/login'
        )->assertOk();

        $cacheControl = (string)
            $response->headers->get(
                'Cache-Control'
            );

        $this->assertStringContainsString(
            'no-store',
            $cacheControl
        );

        $this->assertStringContainsString(
            'private',
            $cacheControl
        );

        $response->assertHeader(
            'Pragma',
            'no-cache'
        );
    }

    public function test_readiness_endpoint_checks_runtime_dependencies_without_analytics_noise(): void
    {
        $this->withHeaders([
            'User-Agent' =>
                'Mozilla/5.0 Chrome/151.0',
        ])
            ->get('/health/ready')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'checks' => [
                    'database' => true,
                    'cache' => true,
                    'storage' => true,
                ],
            ]);

        $this->assertDatabaseCount(
            'portal_analytics_page_views',
            0
        );

        $this->assertDatabaseCount(
            'portal_analytics_sessions',
            0
        );
    }

    public function test_route_actions_needed_for_production_cache_are_not_closures(): void
    {
        foreach (
            [
                'home',
                'admin.content',
                'admin.shop',
            ] as $routeName
        ) {
            $route = Route::getRoutes()
                ->getByName($routeName);

            $this->assertNotNull($route);

            $this->assertFalse(
                $route->getAction('uses')
                    instanceof \Closure,
                $routeName
                . ' must be route-cacheable'
            );
        }
    }

    public function test_local_release_check_passes(): void
    {
        $exitCode = Artisan::call(
            'app:release-check'
        );

        $this->assertSame(
            0,
            $exitCode,
            Artisan::output()
        );
    }

    public function test_analytics_retention_command_removes_only_expired_sessions(): void
    {
        PortalAnalyticsSession::create([
            'id' =>
                '11111111-1111-4111-8111-111111111111',
            'browser_session_hash' =>
                str_repeat('a', 64),
            'started_at' =>
                now()->subDays(250),
            'last_seen_at' =>
                now()->subDays(250),
            'landing_path' => '/pl',
            'landing_locale' => 'pl',
            'source_group' => 'direct',
            'device_type' => 'desktop',
            'pageviews_count' => 0,
            'events_count' => 0,
        ]);

        PortalAnalyticsSession::create([
            'id' =>
                '22222222-2222-4222-8222-222222222222',
            'browser_session_hash' =>
                str_repeat('b', 64),
            'started_at' =>
                now()->subDays(10),
            'last_seen_at' =>
                now()->subDays(10),
            'landing_path' => '/pl',
            'landing_locale' => 'pl',
            'source_group' => 'direct',
            'device_type' => 'mobile',
            'pageviews_count' => 0,
            'events_count' => 0,
        ]);

        $this->artisan(
            'portal:analytics-prune',
            ['--days' => 180]
        )->assertSuccessful();

        $this->assertDatabaseMissing(
            'portal_analytics_sessions',
            [
                'id' =>
                    '11111111-1111-4111-8111-111111111111',
            ]
        );

        $this->assertDatabaseHas(
            'portal_analytics_sessions',
            [
                'id' =>
                    '22222222-2222-4222-8222-222222222222',
            ]
        );
    }
}
