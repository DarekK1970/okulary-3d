<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ArticleCategory;
use Illuminate\Support\Collection;

class OrchestratorSettingsService
{
    private const GROUP = 'orchestrator';

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

        return in_array(
            $provider,
            ['openai', 'gemini'],
            true
        ) ? $provider : 'openai';
    }

    public function model(
        ?string $provider = null
    ): string {
        $provider ??= $this->provider();

        $default = $provider === 'gemini'
            ? 'gemini-3.7-flash'
            : 'gpt-5.6';

        return (string) $this->get(
            $provider . '.model',
            $default
        );
    }

    public function apiKey(
        ?string $provider = null
    ): ?string {
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
                (int) $this->get(
                    'timeout',
                    '120'
                )
            )
        );
    }

    public function weeklyArticleLimit(): int
    {
        return max(
            1,
            min(
                7,
                (int) $this->get(
                    'weekly_article_limit',
                    '3'
                )
            )
        );
    }

    public function minRelevance(): int
    {
        return max(
            0,
            min(
                100,
                (int) $this->get(
                    'min_relevance',
                    '70'
                )
            )
        );
    }

    public function targetWords(): int
    {
        return max(
            450,
            min(
                2200,
                (int) $this->get(
                    'target_words',
                    '900'
                )
            )
        );
    }

    public function sourceLocale(): string
    {
        $locale = (string) $this->get(
            'source_locale',
            config('locales.default', 'pl')
        );

        $supported = array_keys(
            config(
                'locales.supported',
                ['pl' => []]
            )
        );

        return in_array(
            $locale,
            $supported,
            true
        ) ? $locale : config(
            'locales.default',
            'pl'
        );
    }

    public function defaultCategoryId(): ?int
    {
        $value = (int) $this->get(
            'default_category_id',
            '0'
        );

        return $value > 0
            ? $value
            : null;
    }

    public function defaultCategory(): ?ArticleCategory
    {
        $categoryId =
            $this->defaultCategoryId();

        if ($categoryId) {
            $category = ArticleCategory::query()
                ->whereKey($categoryId)
                ->where('is_active', true)
                ->first();

            if ($category) {
                return $category;
            }
        }

        return ArticleCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();
    }

    /**
     * @return list<array{
     *     day: int,
     *     time: string
     * }>
     */
    public function scheduleSlots(): array
    {
        $raw = (string) $this->get(
            'schedule_slots',
            implode("\n", [
                '1@09:00',
                '3@09:00',
                '5@09:00',
            ])
        );

        $slots = collect(
            preg_split('/\R+/', $raw) ?: []
        )
            ->map(function ($line): ?array {
                $line = trim(
                    (string) $line
                );

                if (
                    ! preg_match(
                        '/^([1-7])@([01]\d|2[0-3]):([0-5]\d)$/',
                        $line,
                        $matches
                    )
                ) {
                    return null;
                }

                return [
                    'day' =>
                        (int) $matches[1],
                    'time' =>
                        $matches[2]
                        . ':'
                        . $matches[3],
                ];
            })
            ->filter(
                static fn ($slot): bool =>
                    is_array($slot)
            )
            ->unique(
                static fn (array $slot): string =>
                    $slot['day']
                    . '@'
                    . $slot['time']
            )
            ->sortBy(
                static fn (array $slot): string =>
                    str_pad(
                        (string) $slot['day'],
                        2,
                        '0',
                        STR_PAD_LEFT
                    )
                    . '@'
                    . $slot['time']
            )
            ->values()
            ->all();

        return $slots !== []
            ? $slots
            : [
                [
                    'day' => 1,
                    'time' => '09:00',
                ],
                [
                    'day' => 3,
                    'time' => '09:00',
                ],
                [
                    'day' => 5,
                    'time' => '09:00',
                ],
            ];
    }

    public function scheduleSlotsRaw(): string
    {
        return (string) $this->get(
            'schedule_slots',
            "1@09:00\n3@09:00\n5@09:00"
        );
    }

    public function extraInstructions(): ?string
    {
        return $this->get(
            'extra_instructions'
        );
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
        $setting =
            $this->settings()->get($key);

        return $setting?->value
            ?? $default;
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

    /**
     * @return Collection<string, AppSetting>
     */
    private function settings(): Collection
    {
        if ($this->cache === null) {
            $this->cache =
                AppSetting::query()
                    ->where(
                        'group',
                        self::GROUP
                    )
                    ->get()
                    ->keyBy('key');
        }

        return $this->cache;
    }
}
