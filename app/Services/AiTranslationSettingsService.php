<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class AiTranslationSettingsService
{
    private const GROUP = 'ai_translation';

    /** @var Collection<string, AppSetting>|null */
    private ?Collection $cache = null;

    public function enabled(): bool
    {
        return $this->bool('enabled', false);
    }

    public function provider(): string
    {
        $provider = $this->get('provider', 'openai');

        return in_array(
            $provider,
            ['openai', 'gemini'],
            true
        ) ? $provider : 'openai';
    }

    public function timeout(): int
    {
        return max(
            10,
            min(
                180,
                (int) $this->get('timeout', '60')
            )
        );
    }

    public function apiKey(?string $provider = null): ?string
    {
        $provider ??= $this->provider();

        return $this->get(
            $provider . '.api_key'
        );
    }

    public function model(?string $provider = null): string
    {
        $provider ??= $this->provider();

        $default = $provider === 'gemini'
            ? 'gemini-3.7-flash'
            : 'gpt-5.6';

        return (string) $this->get(
            $provider . '.model',
            $default
        );
    }

    public function glossary(): ?string
    {
        return $this->get('glossary');
    }

    public function configured(?string $provider = null): bool
    {
        $provider ??= $this->provider();

        return $this->enabled()
            && filled($this->apiKey($provider))
            && filled($this->model($provider));
    }

    public function maskedSecret(string $provider): string
    {
        $value = $this->apiKey($provider);

        if (blank($value)) {
            return '';
        }

        $length = mb_strlen($value);

        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        return mb_substr($value, 0, 4)
            . str_repeat('•', min(16, $length - 8))
            . mb_substr($value, -4);
    }

    public function set(
        string $key,
        ?string $value,
        bool $secret = false
    ): void {
        AppSetting::query()->updateOrCreate(
            [
                'group' => self::GROUP,
                'key' => $key,
            ],
            [
                'value' => $value,
                'is_secret' => $secret,
            ]
        );

        $this->cache = null;
    }

    public function get(
        string $key,
        ?string $default = null
    ): ?string {
        $setting = $this->settings()->get($key);

        return $setting?->value ?? $default;
    }

    private function bool(
        string $key,
        bool $default
    ): bool {
        return filter_var(
            $this->get(
                $key,
                $default ? '1' : '0'
            ),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /** @return Collection<string, AppSetting> */
    private function settings(): Collection
    {
        if ($this->cache === null) {
            $this->cache = AppSetting::query()
                ->where('group', self::GROUP)
                ->get()
                ->keyBy('key');
        }

        return $this->cache;
    }
}
