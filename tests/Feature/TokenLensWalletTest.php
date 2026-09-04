<?php

namespace Tests\Feature;

use App\Models\TokenLensTransaction;
use App\Models\User;
use App\Services\TokenLensWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class TokenLensWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_grants_are_idempotent_and_expired_tokens_are_not_in_balance(): void
    {
        $user = User::factory()->create();
        $wallet = app(TokenLensWalletService::class);

        $wallet->grant($user, 100, 'premium_subscription', 'subscription:1', now()->addMonth());
        $wallet->grant($user, 100, 'premium_subscription', 'subscription:1', now()->addMonth());
        $wallet->grant($user, 25, 'expired', 'expired:1', now()->subMinute());

        $this->assertSame(100, $wallet->balance($user));
        $this->assertDatabaseCount('token_lens_transactions', 2);
    }

    public function test_debit_uses_expiring_tokens_first_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $wallet = app(TokenLensWalletService::class);
        $first = $wallet->grant($user, 6, 'grant', 'grant:first', now()->addDay())->grant;
        $second = $wallet->grant($user, 10, 'grant', 'grant:second', now()->addWeek())->grant;

        $wallet->debit($user, 10, 'ai_video', 'ai:job:1', 'Film AI');
        $wallet->debit($user, 10, 'ai_video', 'ai:job:1', 'Film AI');

        $this->assertSame(6, $wallet->balance($user));
        $this->assertSame(-6, (int) $first->transactions()->where('amount', '<', 0)->sum('amount'));
        $this->assertSame(-4, (int) $second->transactions()->where('amount', '<', 0)->sum('amount'));
        $this->assertDatabaseCount('token_lens_transactions', 4);
    }

    public function test_debit_rejects_an_insufficient_balance(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);
        app(TokenLensWalletService::class)->debit($user, 10, 'ai_video', 'ai:job:2');
    }

    public function test_ledger_transactions_cannot_be_changed_or_deleted(): void
    {
        $user = User::factory()->create();
        $transaction = app(TokenLensWalletService::class)->grant($user, 10, 'grant', 'grant:immutable');

        try {
            $transaction->update(['amount' => 20]);
            $this->fail('The ledger transaction was updated.');
        } catch (LogicException) {
            $this->assertSame(10, $transaction->fresh()->amount);
        }

        $this->expectException(LogicException::class);
        $transaction->delete();
    }

    public function test_admin_can_adjust_tokens_and_user_sees_the_balance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.tokens.adjust', $user), [
            'amount' => 35,
            'reason' => 'Pakiet powitalny',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($user)->get('/pl/account')
            ->assertOk()
            ->assertSee('35')
            ->assertSee('Pakiet powitalny');

        $this->assertSame(35, app(TokenLensWalletService::class)->balance($user));
        $this->assertSame('admin_adjustment', TokenLensTransaction::query()->sole()->type);
    }
}
