<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CurrencyService
{
    public const SESSION_KEY = 'shop_currency';
    public const COOKIE_KEY = 'shop_currency';

    public function __construct(
        private CurrencySettingsService $settings
    ) {
    }

    /**
     * @return Collection<int, Currency>
     */
    public function selectableCurrencies(): Collection
    {
        return $this->settings
            ->currencies(enabledOnly: true)
            ->filter(
                fn (Currency $currency): bool =>
                    $this->isBase($currency)
                    || $this->rateFor($currency) !== null
            )
            ->values();
    }

    public function selectedCode(
        ?Request $request = null
    ): string {
        $request ??= request();

        $candidate = strtoupper(
            trim(
                (string) (
                    $request->session()->get(
                        self::SESSION_KEY
                    )
                    ?? $request->cookie(
                        self::COOKIE_KEY
                    )
                    ?? $this->settings
                        ->defaultCode()
                )
            )
        );

        if (! $this->isSelectable($candidate)) {
            $candidate = $this->fallbackCode();
        }

        if (
            $request->hasSession()
            && $request->session()->get(
                self::SESSION_KEY
            ) !== $candidate
        ) {
            $request->session()->put(
                self::SESSION_KEY,
                $candidate
            );
        }

        return $candidate;
    }

    public function selectedCurrency(
        ?Request $request = null
    ): Currency {
        $code = $this->selectedCode(
            $request
        );

        if (! Schema::hasTable('currencies')) {
            return $this->fallbackCurrency();
        }

        return Currency::query()
            ->where('code', $code)
            ->firstOrFail();
    }

    public function isSelectable(
        string $code
    ): bool {
        $code = strtoupper(trim($code));

        return $this->selectableCurrencies()
            ->contains(
                fn (Currency $currency): bool =>
                    $currency->code === $code
            );
    }

    /**
     * @return array{
     *   currency:string,
     *   base_currency:string,
     *   rate:string,
     *   source:string|null,
     *   effective_date:string|null,
     *   markup_percent:string
     * }
     */
    public function pricingSnapshot(
        ?Request $request = null
    ): array {
        return $this->pricingSnapshotForCode(
            $this->selectedCode($request)
        );
    }

    /**
     * @return array{
     *   currency:string,
     *   base_currency:string,
     *   rate:string,
     *   source:string|null,
     *   effective_date:string|null,
     *   markup_percent:string
     * }
     */
    public function pricingSnapshotForCode(
        string $targetCurrency
    ): array {
        $targetCurrency = strtoupper(
            trim($targetCurrency)
        );

        if (
            $targetCurrency
            === CurrencySettingsService
                ::BASE_CURRENCY
        ) {
            return [
                'currency' =>
                    CurrencySettingsService
                        ::BASE_CURRENCY,
                'base_currency' =>
                    CurrencySettingsService
                        ::BASE_CURRENCY,
                'rate' => '1.00000000',
                'source' => 'base',
                'effective_date' => null,
                'markup_percent' => '0.00',
            ];
        }

        if (! Schema::hasTable('currencies')) {
            throw new RuntimeException(
                __('currency.errors.unavailable')
            );
        }

        $currency = Currency::query()
            ->where(
                'code',
                $targetCurrency
            )
            ->where('is_enabled', true)
            ->first();

        $rate = $currency
            ? $this->rateFor($currency)
            : null;

        if (
            ! $currency
            || ! $rate
            || (float) $rate->rate_to_base <= 0
        ) {
            throw new RuntimeException(
                __('currency.errors.unavailable')
            );
        }

        return [
            'currency' => $currency->code,
            'base_currency' =>
                CurrencySettingsService
                    ::BASE_CURRENCY,
            'rate' =>
                (string) $rate->rate_to_base,
            'source' =>
                $rate->source,
            'effective_date' =>
                $rate->effective_date
                    ?->toDateString(),
            'markup_percent' =>
                number_format(
                    max(
                        0,
                        (float) $this->settings
                            ->markupPercent()
                    ),
                    2,
                    '.',
                    ''
                ),
        ];
    }

    public function toBaseCents(
        float|int|string $amount,
        string $sourceCurrency
    ): int {
        $sourceCurrency = strtoupper(
            trim($sourceCurrency)
        );

        if (
            $sourceCurrency
            === CurrencySettingsService
                ::BASE_CURRENCY
        ) {
            return $this->moneyToCents(
                (float) $amount
            );
        }

        if (! Schema::hasTable('currencies')) {
            throw new RuntimeException(
                __('currency.errors.source_rate_missing', [
                    'currency' =>
                        $sourceCurrency,
                ])
            );
        }

        $source = Currency::query()
            ->where(
                'code',
                $sourceCurrency
            )
            ->first();

        $rate = $source
            ? $this->rateFor($source)
            : null;

        if (
            ! $source
            || ! $rate
            || (float) $rate->rate_to_base <= 0
        ) {
            throw new RuntimeException(
                __('currency.errors.source_rate_missing', [
                    'currency' =>
                        $sourceCurrency,
                ])
            );
        }

        return $this->moneyToCents(
            (float) $amount
            * (float) $rate->rate_to_base
        );
    }

    /**
     * @param array{
     *   currency:string,
     *   base_currency:string,
     *   rate:string,
     *   source:string|null,
     *   effective_date:string|null,
     *   markup_percent:string
     * } $snapshot
     */
    public function convertBaseCentsWithSnapshot(
        int $baseCents,
        array $snapshot
    ): int {
        if (
            $snapshot['currency']
            === $snapshot['base_currency']
        ) {
            return $baseCents;
        }

        $rate = (float) $snapshot['rate'];

        if ($rate <= 0) {
            throw new RuntimeException(
                __('currency.errors.invalid_rate')
            );
        }

        $targetAmount =
            ($baseCents / 100)
            / $rate;

        $markup = max(
            0,
            (float) $snapshot[
                'markup_percent'
            ]
        );

        if ($markup > 0) {
            $targetAmount *=
                1 + ($markup / 100);
        }

        return $this->moneyToCents(
            $targetAmount
        );
    }

    public function convertBaseCents(
        int $baseCents,
        string $targetCurrency
    ): int {
        return $this
            ->convertBaseCentsWithSnapshot(
                $baseCents,
                $this->pricingSnapshotForCode(
                    $targetCurrency
                )
            );
    }

    public function convertToSelectedCents(
        float|int|string $amount,
        string $sourceCurrency = 'PLN',
        ?Request $request = null
    ): int {
        $snapshot = $this->pricingSnapshot(
            $request
        );

        return $this
            ->convertBaseCentsWithSnapshot(
                $this->toBaseCents(
                    $amount,
                    $sourceCurrency
                ),
                $snapshot
            );
    }

    public function convertToSelected(
        float|int|string $amount,
        string $sourceCurrency = 'PLN',
        ?Request $request = null
    ): float {
        return $this->convertToSelectedCents(
            $amount,
            $sourceCurrency,
            $request
        ) / 100;
    }

    public function convert(
        float|int|string $amount,
        string $sourceCurrency,
        string $targetCurrency
    ): float {
        return $this->convertBaseCents(
            $this->toBaseCents(
                $amount,
                $sourceCurrency
            ),
            $targetCurrency
        ) / 100;
    }

    public function formatSelected(
        float|int|string $amount,
        string $sourceCurrency = 'PLN',
        ?string $locale = null,
        ?Request $request = null
    ): string {
        $currency =
            $this->selectedCurrency(
                $request
            );

        return $this->formatCents(
            $this->convertToSelectedCents(
                $amount,
                $sourceCurrency,
                $request
            ),
            $currency,
            $locale
        );
    }

    public function formatCents(
        int $cents,
        Currency|string $currency,
        ?string $locale = null
    ): string {
        return $this->formatAmount(
            $cents / 100,
            $currency,
            $locale
        );
    }

    public function formatAmount(
        float|int|string $amount,
        Currency|string $currency,
        ?string $locale = null
    ): string {
        if (is_string($currency)) {
            $code = strtoupper($currency);

            if (! Schema::hasTable('currencies')) {
                if (
                    $code
                    !== CurrencySettingsService
                        ::BASE_CURRENCY
                ) {
                    throw new RuntimeException(
                        __('currency.errors.unavailable')
                    );
                }

                $currency =
                    $this->fallbackCurrency();
            } else {
                $currency = Currency::query()
                    ->where(
                        'code',
                        $code
                    )
                    ->firstOrFail();
            }
        }

        $locale ??= app()->getLocale();

        $formatted = number_format(
            (float) $amount,
            max(
                0,
                (int) $currency
                    ->decimal_places
            ),
            $locale === 'pl'
                ? ','
                : '.',
            $locale === 'pl'
                ? ' '
                : ','
        );

        return $formatted
            . ' '
            . $currency->code;
    }

    public function selectedRate(
        ?Request $request = null
    ): ?CurrencyRate {
        return $this->rateFor(
            $this->selectedCurrency($request)
        );
    }

    public function selectedIsBase(
        ?Request $request = null
    ): bool {
        return $this->selectedCode($request)
            === CurrencySettingsService
                ::BASE_CURRENCY;
    }

    private function fallbackCode(): string
    {
        $default =
            $this->settings->defaultCode();

        if (
            $this->isSelectableWithoutFallback(
                $default
            )
        ) {
            return $default;
        }

        return CurrencySettingsService
            ::BASE_CURRENCY;
    }

    private function isSelectableWithoutFallback(
        string $code
    ): bool {
        $code = strtoupper(trim($code));

        if (! Schema::hasTable('currencies')) {
            return $code
                === CurrencySettingsService
                    ::BASE_CURRENCY;
        }

        $currency = Currency::query()
            ->where('code', $code)
            ->where('is_enabled', true)
            ->first();

        if (! $currency) {
            return false;
        }

        return $this->isBase($currency)
            || $this->rateFor($currency)
                !== null;
    }

    private function isBase(
        Currency $currency
    ): bool {
        return $currency->code
            === CurrencySettingsService
                ::BASE_CURRENCY;
    }

    private function rateFor(
        Currency $currency
    ): ?CurrencyRate {
        if ($this->isBase($currency)) {
            return null;
        }

        return $this->settings
            ->currentRate($currency);
    }

    private function fallbackCurrency(): Currency
    {
        $currency = new Currency();

        $currency->forceFill([
            'id' => null,
            'code' =>
                CurrencySettingsService
                    ::BASE_CURRENCY,
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

    private function moneyToCents(
        float $value
    ): int {
        return (int) round(
            $value * 100,
            0,
            PHP_ROUND_HALF_UP
        );
    }
}
