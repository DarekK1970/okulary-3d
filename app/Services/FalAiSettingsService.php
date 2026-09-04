<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class FalAiSettingsService
{
    private const GROUP = 'fal_ai';

    /** @var Collection<string, AppSetting>|null */
    private ?Collection $cache = null;

    public function enabled(): bool
    {
        return $this->bool('enabled', false);
    }

    public function apiKey(): ?string
    {
        return $this->get('api_key');
    }

    public function configured(): bool
    {
        return $this->enabled() && filled($this->apiKey());
    }

    public function timeout(): int
    {
        return max(10, min(180, (int) $this->get('timeout', '60')));
    }

    public function seedanceModel(): string
    {
        return (string) $this->get('seedance_model', 'bytedance/seedance-2.5/image-to-video');
    }

    public function resolution(): string
    {
        return (string) $this->get('resolution', '720p');
    }

    public function duration(): int
    {
        return max(4, min(30, (int) $this->get('duration', '4')));
    }

    public function generateAudio(): bool
    {
        return $this->bool('generate_audio', false);
    }

    public function upscalingEnabled(): bool
    {
        return $this->bool('upscaling_enabled', true);
    }

    public function upscalerModel(): string
    {
        return (string) $this->get('upscaler_model', 'fal-ai/bytedance-upscaler/upscale/video');
    }

    public function upscaleResolution(): string
    {
        return (string) $this->get('upscale_resolution', '4k');
    }

    public function maximumJobCost(): float
    {
        return max(0.01, (float) $this->get('maximum_job_cost_usd', '5.00'));
    }

    public function dailyBudget(): float
    {
        return max(0.01, (float) $this->get('daily_budget_usd', '50.00'));
    }

    public function maskedSecret(): string
    {
        $value = $this->apiKey();
        if (blank($value)) {
            return '';
        }
        $length = mb_strlen($value);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        return mb_substr($value, 0, 4).str_repeat('•', min(16, $length - 8)).mb_substr($value, -4);
    }

    public function set(string $key, ?string $value, bool $secret = false): void
    {
        AppSetting::query()->updateOrCreate(
            ['group' => self::GROUP, 'key' => $key],
            ['value' => $value, 'is_secret' => $secret]
        );
        $this->cache = null;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->settings()->get($key)?->value ?? $default;
    }

    private function bool(string $key, bool $default): bool
    {
        return filter_var($this->get($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return Collection<string, AppSetting> */
    private function settings(): Collection
    {
        return $this->cache ??= AppSetting::query()->where('group', self::GROUP)->get()->keyBy('key');
    }
}
