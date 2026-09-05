<?php

namespace App\Services;

use App\Models\User;

class LenticularAccessService
{
    public const FREE = 'free';

    public const PRO = 'pro';

    public const PREMIUM = 'premium';

    public function plan(?User $user): string
    {
        if ($user?->role === User::ROLE_SUPER_ADMIN) {
            return self::PREMIUM;
        }

        if ($user?->plan_expires_at?->isPast()) {
            return self::FREE;
        }

        return in_array($user?->lenticular_plan, [self::PRO, self::PREMIUM], true)
            ? $user->lenticular_plan
            : self::FREE;
    }

    public function canUseAgent(string $plan): bool
    {
        return in_array($plan, [self::PRO, self::PREMIUM], true);
    }

    /** @return list<string> */
    public function agentPrintSizes(string $plan): array
    {
        return match ($plan) {
            self::PREMIUM => ['A3', 'A4', 'A5', '15x10'],
            self::PRO => ['A4', 'A5', '15x10'],
            default => ['A5', '15x10'],
        };
    }

    /** @return list<string> */
    public function sequencePrintSizes(string $plan): array
    {
        return $plan === self::FREE ? ['A5', '15x10'] : ['A3', 'A4', 'A5', '15x10'];
    }

    public function sequenceLimit(string $plan): int
    {
        return match ($plan) {
            self::PREMIUM => 100,
            self::PRO => 25,
            default => 12,
        };
    }

    /** @return list<int> */
    public function printerDpis(string $plan): array
    {
        $basic = [600, 720, 1200, 1440];

        return $plan === self::FREE
            ? $basic
            : [...$basic, 2400, 2540, 4000, 4800];
    }
}
