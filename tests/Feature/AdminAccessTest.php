<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_to_polish_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/pl/login');
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_editor_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Treści i artykuły');
    }

    public function test_editor_cannot_access_users_section(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_access_users_but_not_system_settings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_system_settings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Ustawienia systemowe');
    }

    public function test_admin_account_page_contains_admin_panel_link(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)
            ->get('/pl/account')
            ->assertOk()
            ->assertSee('Panel administracyjny')
            ->assertSee(route('admin.dashboard'), false);
    }
}
