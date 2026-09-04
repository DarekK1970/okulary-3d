<?php

namespace App\Services;

class PlanSettingsService
{
    public function __construct(private readonly CommerceSettingsService $settings) {}

    public function plans(): array
    {
        return [
            'free' => ['price' => 0, 'tokens' => $this->settings->int('plans.free.tokens', 0)],
            'pro' => ['price' => $this->decimal('plans.pro.price', 99), 'tokens' => $this->settings->int('plans.pro.tokens', 40)],
            'premium' => ['price' => $this->decimal('plans.premium.price', 200), 'tokens' => $this->settings->int('plans.premium.tokens', 100)],
        ];
    }

    public function update(array $values): void
    {
        $this->settings->setMany([
            'plans.free.tokens' => (string) $values['free_tokens'],
            'plans.pro.price' => number_format((float) $values['pro_price'], 2, '.', ''),
            'plans.pro.tokens' => (string) $values['pro_tokens'],
            'plans.premium.price' => number_format((float) $values['premium_price'], 2, '.', ''),
            'plans.premium.tokens' => (string) $values['premium_tokens'],
        ]);
    }

    private function decimal(string $key, float $default): float
    {
        return round((float) $this->settings->get($key, (string) $default), 2);
    }
}
