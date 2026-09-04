<?php

namespace App\Services;

use App\Models\TokenLensGrant;
use App\Models\TokenLensTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TokenLensWalletService
{
    public function balance(User $user): int
    {
        return (int) TokenLensTransaction::query()
            ->where('user_id', $user->id)
            ->where(function ($query): void {
                $query->whereNull('token_lens_grant_id')->orWhereHas('grant', fn ($grant) => $grant
                    ->where('effective_at', '<=', now())
                    ->where(fn ($expiry) => $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now())));
            })->sum('amount');
    }

    public function expiresAt(User $user): ?Carbon
    {
        $grants = TokenLensGrant::query()
            ->where('user_id', $user->id)
            ->where('effective_at', '<=', now())
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->get();

        foreach ($grants as $grant) {
            $remaining = $grant->amount + (int) $grant->transactions()->where('amount', '<', 0)->sum('amount');
            if ($remaining > 0) {
                return $grant->expires_at;
            }
        }

        return null;
    }

    public function grant(User $user, int $amount, string $source, string $idempotencyKey, ?\DateTimeInterface $expiresAt = null, ?string $description = null, string $transactionType = 'grant'): TokenLensTransaction
    {
        abort_if($amount < 1, 422);

        return DB::transaction(function () use ($user, $amount, $source, $idempotencyKey, $expiresAt, $description, $transactionType): TokenLensTransaction {
            $existing = TokenLensTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $grant = TokenLensGrant::query()->create([
                'user_id' => $user->id, 'source' => $source, 'amount' => $amount,
                'effective_at' => now(), 'expires_at' => $expiresAt,
            ]);

            return TokenLensTransaction::query()->create([
                'user_id' => $user->id, 'token_lens_grant_id' => $grant->id,
                'type' => $transactionType, 'amount' => $amount, 'idempotency_key' => $idempotencyKey,
                'description' => $description,
            ]);
        });
    }

    public function debit(User $user, int $amount, string $type, string $idempotencyKey, ?string $description = null): void
    {
        abort_if($amount < 1, 422);
        DB::transaction(function () use ($user, $amount, $type, $idempotencyKey, $description): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (TokenLensTransaction::query()
                ->where('reference_type', 'wallet_debit')
                ->where('reference_id', $idempotencyKey)
                ->exists()) {
                return;
            }
            if ($this->balance($user) < $amount) {
                throw ValidationException::withMessages(['token_lens' => __('portal_auth.wallet.insufficient')]);
            }
            $remaining = $amount;
            $grants = TokenLensGrant::query()->where('user_id', $user->id)->where('effective_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->orderByRaw('expires_at IS NULL')->orderBy('expires_at')->orderBy('created_at')->get();
            foreach ($grants as $grant) {
                $available = $grant->amount + (int) TokenLensTransaction::query()->where('token_lens_grant_id', $grant->id)->where('amount', '<', 0)->sum('amount');
                $take = min($remaining, max(0, $available));
                if ($take === 0) {
                    continue;
                }
                TokenLensTransaction::query()->create([
                    'user_id' => $user->id, 'token_lens_grant_id' => $grant->id, 'type' => $type,
                    'amount' => -$take, 'idempotency_key' => $idempotencyKey.':'.$grant->id,
                    'reference_type' => 'wallet_debit', 'reference_id' => $idempotencyKey,
                    'description' => $description,
                ]);
                $remaining -= $take;
                if ($remaining === 0) {
                    break;
                }
            }
        });
    }

    public function adminAdjust(User $user, int $amount, string $reason, User $admin): void
    {
        $key = 'admin:'.$admin->id.':'.Str::uuid();
        if ($amount > 0) {
            $this->grant($user, $amount, 'admin_adjustment', $key, description: $reason, transactionType: 'admin_adjustment');
        } else {
            $this->debit($user, abs($amount), 'admin_adjustment', $key, $reason);
        }
    }
}
