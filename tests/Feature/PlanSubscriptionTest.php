<?php

namespace Tests\Feature;

use App\Models\PlanPurchase;
use App\Models\User;
use App\Services\CommerceSettingsService;
use App\Services\PayNowService;
use App\Services\PlanSettingsService;
use App\Services\TokenLensWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlanSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_three_month_plans_and_starts_paynow_payment(): void
    {
        $user = User::factory()->create();
        $settings = app(CommerceSettingsService::class);
        foreach (['paynow.enabled' => '1', 'paynow.sandbox' => '1', 'paynow.api_key' => 'api', 'paynow.signature_key' => 'signature'] as $key => $value) {
            $settings->set($key, $value);
        }
        Http::fake(['https://api.sandbox.paynow.pl/v3/payments' => Http::response(['paymentId' => 'PAY-1', 'redirectUrl' => 'https://pay.test/1', 'status' => 'NEW'])]);

        $this->actingAs($user)->get('/pl/plans')->assertOk()->assertSee('FREE')->assertSee('PRO')->assertSee('PREMIUM')->assertSee('Autoodnawianie');
        $this->actingAs($user)->post('/pl/plans/purchase', ['plan' => 'premium', 'auto_renew' => '1'])->assertRedirect('https://pay.test/1');
        $this->assertDatabaseHas('plan_purchases', ['user_id' => $user->id, 'plan' => 'premium', 'duration_months' => 3, 'token_lens' => 100, 'auto_renew' => true]);
    }

    public function test_confirmed_payment_activates_plan_and_grants_tokens_once(): void
    {
        $user = User::factory()->create();
        $purchase = PlanPurchase::query()->create(['public_token' => fake()->uuid(), 'user_id' => $user->id, 'plan' => 'pro', 'duration_months' => 3, 'token_lens' => 40, 'price' => 99, 'currency' => 'PLN', 'auto_renew' => true, 'status' => 'pending', 'payment_external_id' => 'PAY-2']);
        $service = app(PayNowService::class);
        $this->assertTrue($service->applyPlanPurchaseStatus($purchase, 'CONFIRMED', 'PAY-2'));
        $this->assertFalse($service->applyPlanPurchaseStatus($purchase->fresh(), 'CONFIRMED', 'PAY-2'));
        $this->assertSame('pro', $user->fresh()->lenticular_plan);
        $this->assertSame(40, app(TokenLensWalletService::class)->balance($user));
    }

    public function test_super_admin_can_update_plan_prices_and_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->put('/admin/settings/plans', ['free_tokens' => 0, 'pro_price' => 149.90, 'pro_tokens' => 60, 'premium_price' => 249.90, 'premium_tokens' => 120])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(120, app(PlanSettingsService::class)->plans()['premium']['tokens']);
    }
}
