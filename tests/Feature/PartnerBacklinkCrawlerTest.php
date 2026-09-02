<?php

namespace Tests\Feature;

use App\Enums\PartnerLinkStatus;
use App\Mail\PartnerBacklinkStatusMail;
use App\Models\PartnerLink;
use App\Models\User;
use App\Services\PartnerBacklinkChecker;
use App\Services\PartnerBacklinkMonitor;
use App\Services\PartnerUrlSafetyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PartnerBacklinkCrawlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_checker_finds_real_anchor_to_portal(): void
    {
        Http::fake([
            'https://partner.example/links' => Http::response(
                '<html><body><p>okulary-3d.pl</p><a href="https://okulary-3d.pl/pl">Portal miłośników 3D</a></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $safety = Mockery::mock(PartnerUrlSafetyService::class);
        $safety->shouldReceive('inspect')
            ->once()
            ->with('https://partner.example/links')
            ->andReturn([
                'scheme' => 'https',
                'host' => 'partner.example',
                'port' => 443,
                'ip' => '93.184.216.34',
            ]);

        $result = (new PartnerBacklinkChecker($safety))->check($this->partner());

        $this->assertTrue($result['reachable']);
        $this->assertTrue($result['backlink_found']);
        $this->assertSame(200, $result['http_status']);
    }

    public function test_plain_text_domain_is_not_treated_as_backlink(): void
    {
        Http::fake([
            'https://partner.example/links' => Http::response(
                '<html><body><p>Visit okulary-3d.pl for more information.</p><a href="https://example.org">Other</a></body></html>',
                200
            ),
        ]);

        $safety = Mockery::mock(PartnerUrlSafetyService::class);
        $safety->shouldReceive('inspect')->once()->andReturn([
            'scheme' => 'https',
            'host' => 'partner.example',
            'port' => 443,
            'ip' => '93.184.216.34',
        ]);

        $result = (new PartnerBacklinkChecker($safety))->check($this->partner());

        $this->assertTrue($result['reachable']);
        $this->assertFalse($result['backlink_found']);
    }

    public function test_ssrf_guard_rejects_local_and_private_addresses(): void
    {
        $service = new PartnerUrlSafetyService();

        foreach ([
            'http://127.0.0.1/admin',
            'http://10.0.0.15/',
            'http://192.168.1.20/',
            'http://localhost/',
        ] as $url) {
            try {
                $service->inspect($url);
                $this->fail('Unsafe URL was accepted: ' . $url);
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_missing_backlink_suspends_active_partner_immediately(): void
    {
        Mail::fake();
        $partner = $this->partner([
            'status' => PartnerLinkStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $monitor = new PartnerBacklinkMonitor(
            $this->checkerReturning([
                'reachable' => true,
                'backlink_found' => false,
                'http_status' => 200,
                'error' => null,
                'checked_url' => $partner->backlink_url,
            ])
        );

        $result = $monitor->check($partner);
        $partner->refresh();

        $this->assertSame(PartnerLinkStatus::SuspendedBacklink, $partner->status);
        $this->assertSame('backlink_missing', $partner->last_check_error);
        $this->assertTrue($result['status_changed']);
        Mail::assertSent(
            PartnerBacklinkStatusMail::class,
            fn (PartnerBacklinkStatusMail $mail): bool =>
                $mail->partner->is($partner) && $mail->event === 'suspended_backlink'
        );
    }

    public function test_unreachable_partner_is_suspended_only_after_second_consecutive_failure(): void
    {
        Mail::fake();
        $partner = $this->partner([
            'status' => PartnerLinkStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $checker = Mockery::mock(PartnerBacklinkChecker::class);
        $checker->shouldReceive('check')->twice()->andReturn([
            'reachable' => false,
            'backlink_found' => false,
            'http_status' => null,
            'error' => 'connection_timeout',
            'checked_url' => $partner->backlink_url,
        ]);

        $monitor = new PartnerBacklinkMonitor($checker);

        $monitor->check($partner);
        $partner->refresh();
        $this->assertSame(PartnerLinkStatus::Active, $partner->status);
        $this->assertSame(1, $partner->consecutive_failures);

        $monitor->check($partner);
        $partner->refresh();
        $this->assertSame(PartnerLinkStatus::SuspendedUnreachable, $partner->status);
        $this->assertSame(2, $partner->consecutive_failures);

        Mail::assertSent(
            PartnerBacklinkStatusMail::class,
            fn (PartnerBacklinkStatusMail $mail): bool => $mail->event === 'suspended_unreachable'
        );
    }

    public function test_partner_is_restored_automatically_when_backlink_returns(): void
    {
        Mail::fake();
        $partner = $this->partner([
            'status' => PartnerLinkStatus::SuspendedBacklink,
            'email_verified_at' => now(),
            'approved_at' => now(),
            'consecutive_failures' => 0,
            'last_check_error' => 'backlink_missing',
        ]);

        $monitor = new PartnerBacklinkMonitor(
            $this->checkerReturning([
                'reachable' => true,
                'backlink_found' => true,
                'http_status' => 200,
                'error' => null,
                'checked_url' => $partner->backlink_url,
            ])
        );

        $monitor->check($partner);
        $partner->refresh();

        $this->assertSame(PartnerLinkStatus::Active, $partner->status);
        $this->assertNotNull($partner->last_backlink_found_at);
        $this->assertNull($partner->last_check_error);

        Mail::assertSent(
            PartnerBacklinkStatusMail::class,
            fn (PartnerBacklinkStatusMail $mail): bool => $mail->event === 'restored'
        );
    }

    public function test_admin_can_run_backlink_check_immediately(): void
    {
        Mail::fake();
        $partner = $this->partner([
            'status' => PartnerLinkStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $admin = $this->user(User::ROLE_ADMIN);

        $this->app->instance(
            PartnerBacklinkChecker::class,
            $this->checkerReturning([
                'reachable' => true,
                'backlink_found' => true,
                'http_status' => 200,
                'error' => null,
                'checked_url' => $partner->backlink_url,
            ])
        );

        $this->actingAs($admin)
            ->from('/admin/partners')
            ->post('/admin/partners/' . $partner->id . '/check-backlink')
            ->assertRedirect('/admin/partners')
            ->assertSessionHas('status');

        $partner->refresh();
        $this->assertNotNull($partner->last_checked_at);
        $this->assertNotNull($partner->last_backlink_found_at);
    }

    public function test_weekly_command_processes_approved_partner(): void
    {
        $partner = $this->partner([
            'status' => PartnerLinkStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $monitor = Mockery::mock(PartnerBacklinkMonitor::class);
        $monitor->shouldReceive('check')
            ->once()
            ->with(Mockery::on(fn (PartnerLink $value): bool => $value->is($partner)))
            ->andReturn([
                'reachable' => true,
                'backlink_found' => true,
                'http_status' => 200,
                'error' => null,
                'checked_url' => $partner->backlink_url,
                'status_changed' => false,
                'consecutive_failures' => 0,
                'current_status' => PartnerLinkStatus::Active->value,
            ]);

        $this->app->instance(PartnerBacklinkMonitor::class, $monitor);

        $exitCode = Artisan::call('partners:check-backlinks');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Sprawdzono: 1', Artisan::output());
    }

    private function checkerReturning(array $result): PartnerBacklinkChecker
    {
        $checker = Mockery::mock(PartnerBacklinkChecker::class);
        $checker->shouldReceive('check')->once()->andReturn($result);

        return $checker;
    }

    private function partner(array $overrides = []): PartnerLink
    {
        return PartnerLink::query()->create(array_merge([
            'source_locale' => 'pl',
            'name' => 'Stereo Partner',
            'website_url' => 'https://partner.example',
            'domain' => 'partner.example',
            'backlink_url' => 'https://partner.example/links',
            'description' => 'Serwis poświęcony fotografii stereoskopowej i obrazom 3D.',
            'logo_path' => 'partners/logos/partner.webp',
            'email' => 'partner@example.org',
            'commercial' => false,
            'contact_person' => 'Jan Testowy',
            'phone' => '+48 500 000 000',
            'status' => PartnerLinkStatus::EmailPending,
            'backlink_commitment_at' => now(),
            'privacy_accepted_at' => now(),
        ], $overrides));
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
