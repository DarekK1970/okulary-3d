<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class CurrencySettingsService
{
    private const GROUP = 'currency';

    public const BASE_CURRENCY = 'PLN';

    /**
     * @var Collection<string, AppSetting>|null
     */
    private ?Collection $settingsCache = null;

    public function currencies(
        bool $enabledOnly = false
    ): Collection {
        if (! Schema::hasTable('currencies')) {
            return collect([
                $this->fallbackBaseCurrency(),
            ]);
        }

        return Currency::query()
            ->when(
                $enabledOnly,
                fn ($query) => $query
                    ->where('is_enabled', true)
            )
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public function baseCode(): string
    {
        return self::BASE_CURRENCY;
    }

    public function defaultCode(): string
    {
        if (! Schema::hasTable('currencies')) {
            return self::BASE_CURRENCY;
        }

        $configured = strtoupper(
            (string) $this->get(
                'default',
                self::BASE_CURRENCY
            )
        );

        $exists = Currency::query()
            ->where('code', $configured)
            ->where('is_enabled', true)
            ->exists();

        return $exists
            ? $configured
            : self::BASE_CURRENCY;
    }

    public function autoUpdateEnabled(): bool
    {
        return $this->bool(
            'auto_update',
            true
        );
    }

    public function updateTime(): string
    {
        return $this->get(
            'update_time',
            '06:00'
        ) ?? '06:00';
    }

    public function provider(): string
    {
        return $this->get(
            'provider',
            'nbp'
        ) ?? 'nbp';
    }

    public function markupPercent(): string
    {
        return $this->get(
            'markup_percent',
            '5.00'
        ) ?? '5.00';
    }

    public function get(
        string $key,
        ?string $default = null
    ): ?string {
        if (! Schema::hasTable('app_settings')) {
            return $default;
        }

        $setting = $this->settings()
            ->get($key);

        return $setting?->value ?? $default;
    }

    public function bool(
        string $key,
        bool $default = false
    ): bool {
        return filter_var(
            $this->get(
                $key,
                $default ? '1' : '0'
            ),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function set(
        string $key,
        ?string $value
    ): void {
        if (! Schema::hasTable('app_settings')) {
            throw new RuntimeException(
                'app_settings table is not available.'
            );
        }

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

        $this->flush();
    }

    /**
     * @param list<string> $enabledCodes
     */
    public function saveConfiguration(
        array $enabledCodes,
        string $defaultCode,
        bool $autoUpdate,
        string $updateTime,
        string $markupPercent
    ): void {
        if (
            ! Schema::hasTable('currencies')
            || ! Schema::hasTable('app_settings')
        ) {
            throw new RuntimeException(
                'Currency settings tables are not available.'
            );
        }

        $enabledCodes = array_values(
            array_unique(
                array_map(
                    static fn (string $code): string =>
                        strtoupper(trim($code)),
                    $enabledCodes
                )
            )
        );

        if (! in_array(
            self::BASE_CURRENCY,
            $enabledCodes,
            true
        )) {
            $enabledCodes[] = self::BASE_CURRENCY;
        }

        if (! in_array(
            strtoupper($defaultCode),
            $enabledCodes,
            true
        )) {
            throw new InvalidArgumentException(
                'Default currency must be enabled.'
            );
        }

        DB::transaction(function () use (
            $enabledCodes,
            $defaultCode,
            $autoUpdate,
            $updateTime,
            $markupPercent
        ): void {
            Currency::query()->update([
                'is_enabled' => false,
                'is_base' => false,
            ]);

            Currency::query()
                ->whereIn('code', $enabledCodes)
                ->update([
                    'is_enabled' => true,
                ]);

            Currency::query()
                ->where('code', self::BASE_CURRENCY)
                ->update([
                    'is_enabled' => true,
                    'is_base' => true,
                ]);

            $this->set(
                'base',
                self::BASE_CURRENCY
            );

            $this->set(
                'default',
                strtoupper($defaultCode)
            );

            $this->set(
                'auto_update',
                $autoUpdate ? '1' : '0'
            );

            $this->set(
                'provider',
                'nbp'
            );

            $this->set(
                'update_time',
                $updateTime
            );

            $this->set(
                'markup_percent',
                $markupPercent
            );
        });
    }

    public function saveManualRate(
        Currency $currency,
        string $rate
    ): CurrencyRate {
        if (! Schema::hasTable('currency_rates')) {
            throw new RuntimeException(
                'currency_rates table is not available.'
            );
        }

        if (
            $currency->code
            === self::BASE_CURRENCY
        ) {
            throw new InvalidArgumentException(
                'Base currency rate is fixed at 1.'
            );
        }

        return CurrencyRate::query()
            ->updateOrCreate(
                [
                    'currency_id' =>
                        $currency->id,
                    'effective_date' =>
                        now()->toDateString(),
                    'source' => 'manual',
                ],
                [
                    'rate_to_base' => $rate,
                    'is_manual' => true,
                    'fetched_at' => now(),
                ]
            );
    }

    public function currentRate(
        Currency $currency
    ): ?CurrencyRate {
        if (
            $currency->code
            === self::BASE_CURRENCY
            || ! Schema::hasTable(
                'currency_rates'
            )
        ) {
            return null;
        }

        return CurrencyRate::query()
            ->where(
                'currency_id',
                $currency->id
            )
            ->orderByDesc('effective_date')
            ->orderByDesc('is_manual')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, CurrencyRate>
     */
    public function currentRates(
        Collection $currencies
    ): Collection {
        if (! Schema::hasTable('currency_rates')) {
            return collect();
        }

        return $currencies
            ->mapWithKeys(
                function (
                    Currency $currency
                ): array {
                    $rate = $this->currentRate(
                        $currency
                    );

                    return $rate
                        ? [$currency->id => $rate]
                        : [];
                }
            );
    }

    public function flush(): void
    {
        $this->settingsCache = null;
    }

    /**
     * @return Collection<string, AppSetting>
     */
    private function settings(): Collection
    {
        if (! Schema::hasTable('app_settings')) {
            return collect();
        }

        if ($this->settingsCache === null) {
            $this->settingsCache =
                AppSetting::query()
                    ->where(
                        'group',
                        self::GROUP
                    )
                    ->get()
                    ->keyBy('key');
        }

        return $this->settingsCache;
    }

    private function fallbackBaseCurrency(): Currency
    {
        $currency = new Currency();

        $currency->forceFill([
            'id' => null,
            'code' => self::BASE_CURRENCY,
            'name_pl' => 'Polski złoty',
            'name_en' => 'Polish zloty',
            'symbol' => 'zł',
            'decimal_places' => 2,
            'is_enabled' => true,
            'is_base' => true,
            'sort_order' => 10,
        ]);

        $currency->exists = false;

        return $currency;
    }
}
