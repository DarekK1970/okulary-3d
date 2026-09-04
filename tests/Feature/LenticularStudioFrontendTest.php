<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FalAiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LenticularStudioFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_lenticular_lab_opens_the_studio_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/pl/lab/lenticular')
            ->assertOk()
            ->assertSee(route('lab.lenticular.studio', ['locale' => 'pl']));
    }

    public function test_guest_is_redirected_from_lenticular_studio(): void
    {
        $this->get('/pl/lab/lenticular/studio')->assertRedirect('/pl/login');
    }

    public function test_user_sees_four_project_paths_and_honest_plan_limits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/pl/lab/lenticular/studio')
            ->assertOk()
            ->assertSee('Jak chcesz stworzyć swój obraz?')
            ->assertSee('Efekt FLIP / MORFING / ZOOM')
            ->assertSee('Wczytaj film lub od 2 do 6 zdjęć')
            ->assertDontSee('Efekt FLIP z filmu')
            ->assertSee('3D z własnych zdjęć')
            ->assertSee('3D z dwóch zdjęć')
            ->assertSee('Agent AI: klatka startowa i końcowa')
            ->assertDontSee('Seedance')
            ->assertSee('3D z jednego zdjęcia')
            ->assertSee('Plan podstawowy')
            ->assertSee('NIEDOSTĘPNE W TYM PLANIE')
            ->assertSee('Przejdź na')
            ->assertSee(route('lab.projects.create', ['locale' => 'pl']))
            ->assertSee(route('lab.lenticular.ai.sequence.create', ['locale' => 'pl']))
            ->assertDontSee('<details class="nav-dropdown" open', false);
    }

    public function test_frontend_reports_when_fal_ai_is_ready_without_exposing_secrets(): void
    {
        $settings = app(FalAiSettingsService::class);
        $settings->set('enabled', '1');
        $settings->set('api_key', 'fal-private-key', true);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/pl/lab/lenticular/studio')
            ->assertOk()
            ->assertSee('Silnik AI gotowy')
            ->assertDontSee('fal-private-key');
    }

    public function test_super_admin_sees_premium_access(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)->get('/pl/lab/lenticular/studio')
            ->assertOk()
            ->assertSee('Plan PREMIUM')
            ->assertSee('najwyższe bezpieczne limity technologiczne')
            ->assertSee('DOSTĘPNE')
            ->assertSee(route('lab.lenticular.ai.pair.create', ['locale' => 'pl']))
            ->assertSee(route('lab.lenticular.ai.single.create', ['locale' => 'pl']))
            ->assertDontSee('Wymaga PRO');
    }

    public function test_pro_user_sees_available_ai_paths_with_a4_limit(): void
    {
        $pro = User::factory()->create(['lenticular_plan' => 'pro']);

        $this->actingAs($pro)->get('/pl/lab/lenticular/studio')
            ->assertOk()
            ->assertSee('Plan PRO')
            ->assertSee('DOSTĘPNE · A4')
            ->assertSee('DOSTĘPNE · 25 · A3')
            ->assertSee(route('lab.lenticular.ai.pair.create', ['locale' => 'pl']))
            ->assertSee(route('lab.lenticular.ai.single.create', ['locale' => 'pl']))
            ->assertDontSee('NIEDOSTĘPNE W TYM PLANIE');
    }
}
