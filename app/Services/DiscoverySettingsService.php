<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class DiscoverySettingsService
{
    private const GROUP = 'discovery';

    /** @var Collection<string, AppSetting>|null */
    private ?Collection $cache = null;

    public function __construct(
        private AiTranslationSettingsService $aiSettings
    ) {
    }

    public function enabled(): bool
    {
        return $this->bool('enabled', false);
    }

    public function provider(): string
    {
        $provider = $this->get(
            'provider',
            $this->aiSettings->provider()
        );

        return in_array($provider, ['openai', 'gemini'], true)
            ? $provider
            : 'openai';
    }

    public function model(?string $provider = null): string
    {
        $provider ??= $this->provider();

        $default = $provider === 'gemini'
            ? 'gemini-3.7-flash'
            : 'gpt-5.6-terra';

        return (string) $this->get(
            $provider . '.model',
            $default
        );
    }

    public function apiKey(?string $provider = null): ?string
    {
        return $this->aiSettings->apiKey(
            $provider ?? $this->provider()
        );
    }

    public function configured(): bool
    {
        return $this->enabled()
            && filled($this->apiKey())
            && filled($this->model());
    }

    public function timeout(): int
    {
        return max(
            20,
            min(
                300,
                (int) $this->get('timeout', '120')
            )
        );
    }

    public function freshnessDays(): int
    {
        return max(
            1,
            min(
                365,
                (int) $this->get('freshness_days', '14')
            )
        );
    }

    public function candidateLimit(): int
    {
        return max(
            1,
            min(
                25,
                (int) $this->get('candidate_limit', '10')
            )
        );
    }

    public function minSources(): int
    {
        return max(
            1,
            min(
                6,
                (int) $this->get('min_sources', '2')
            )
        );
    }

    public function minDomains(): int
    {
        return max(
            1,
            min(
                6,
                (int) $this->get('min_domains', '2')
            )
        );
    }

    public function excludePolishSources(): bool
    {
        return $this->bool(
            'exclude_polish_sources',
            true
        );
    }

    /** @return list<string> */
    public function topics(): array
    {
        $raw = (string) $this->get(
            'topics',
            implode("\n", [
                'stereoscopy and 3D photography',
                'lenticular printing and lenticular displays',
                'spatial photos and spatial video',
                'autostereoscopic displays and glasses-free 3D',
                '3D cinema and projection technology',
                'historic stereoscopic photography and optical devices',
                'consumer 3D cameras, VR and spatial imaging hardware',
            ])
        );

        return $this->lines($raw);
    }

    /** @return list<string> */
    public function excludedDomains(): array
    {
        return $this->lines(
            (string) $this->get('excluded_domains', '')
        );
    }

    /** @return list<string> */
    public function preferredDomains(): array
    {
        return $this->lines(
            (string) $this->get('preferred_domains', '')
        );
    }

    public function extraInstructions(): ?string
    {
        return $this->get('extra_instructions');
    }

    public function set(
        string $key,
        ?string $value
    ): void {
        AppSetting::query()->updateOrCreate(
            [
                'group' => self::GROUP,
                'key' => $key,
            ],
            [
                'value' => $value,
                'is_secret' => false,
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

    /** @return list<string> */
    private function lines(string $value): array
    {
        return collect(
            preg_split('/\R+/', $value) ?: []
        )
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->unique()
            ->values()
            ->all();
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
