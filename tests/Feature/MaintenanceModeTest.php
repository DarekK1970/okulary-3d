<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MaintenanceModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_super_admin_can_open_maintenance_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/settings/maintenance')
            ->assertOk()
            ->assertSee('Strona w konserwacji')
            ->assertSee('Bieżący adres IP');
    }

    public function test_regular_admin_cannot_change_maintenance_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/settings/maintenance')
            ->assertForbidden();
    }

    public function test_super_admin_can_enable_maintenance_and_save_multiple_ips(): void
    {
        $this->actingAs($this->superAdmin())
            ->put('/admin/settings/maintenance', [
                'enabled' => '1',
                'allowed_ips' => "203.0.113.10\n2001:db8::10",
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/settings/maintenance');

        $maintenance = app(MaintenanceModeService::class);

        $this->assertTrue($maintenance->enabled());
        $this->assertSame(
            [
                '203.0.113.10',
                '2001:db8::10',
            ],
            $maintenance->allowedIps()
        );
    }

    public function test_invalid_ip_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->from('/admin/settings/maintenance')
            ->put('/admin/settings/maintenance', [
                'enabled' => '1',
                'allowed_ips' => 'not-an-ip',
            ])
            ->assertRedirect('/admin/settings/maintenance')
            ->assertSessionHasErrors('allowed_ips');
    }

    public function test_non_whitelisted_public_request_gets_503(): void
    {
        app(MaintenanceModeService::class)->save(
            true,
            ['198.51.100.10']
        );

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->get('/pl')
            ->assertStatus(503)
            ->assertSee('Trwają prace techniczne')
            ->assertHeader('Retry-After', '3600');
    }

    public function test_whitelisted_ip_keeps_normal_public_access(): void
    {
        app(MaintenanceModeService::class)->save(
            true,
            ['203.0.113.10']
        );

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->get('/pl')
            ->assertOk();
    }

    public function test_admin_and_login_routes_remain_available_during_maintenance(): void
    {
        app(MaintenanceModeService::class)->save(
            true,
            ['198.51.100.10']
        );

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->get('/pl/login')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.10',
            ])
            ->get('/admin/settings/maintenance')
            ->assertOk();
    }
}
