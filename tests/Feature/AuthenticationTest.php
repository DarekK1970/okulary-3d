<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_polish_login_page(): void
    {
        $this->get('/pl/login')
            ->assertOk()
            ->assertSee('Zaloguj się');
    }

    public function test_guest_can_view_english_login_page(): void
    {
        $this->get('/en/login')
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_user_can_register_and_receives_default_user_role(): void
    {
        $response = $this->post('/pl/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/pl/account');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_registered_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123',
        ]);

        $response = $this->post('/pl/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertRedirect('/pl/account');
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_is_redirected_to_localized_login_from_account(): void
    {
        $this->get('/en/account')
            ->assertRedirect('/en/login');
    }

    public function test_authenticated_user_can_view_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/pl/account')
            ->assertOk()
            ->assertSee('Moje konto')
            ->assertSee($user->email);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/pl/forgot-password', [
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
