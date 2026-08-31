<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NbpCurrencyRateService
{
    public const ENDPOINT =
        'https://api.nbp.pl/api/exchangerates/tables/A/?format=json';

    public function __construct(
        private CurrencySettingsService $settings
    ) {
    }

    /**
     * Fetch the latest published NBP Table A and persist
     * rates for all enabled foreign shop currencies.
     *
     * The NBP effective date can be older than the fetch date
     * on weekends, holidays and before a new table is published.
     *
     * @return array{
     *   count:int,
     *   effective_date:string|null,
     *   codes:list<string>
     * }
     */
    public function refresh(): array
    {
        $currencies = $this->foreignCurrencies();

        if ($currencies->isEmpty()) {
            return [
                'count' => 0,
                'effective_date' => null,
                'codes' => [],
            ];
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->retry(
                2,
                300,
                throw: false
            )
            ->get(self::ENDPOINT);

        $this->ensureSuccess($response);

        $payload = $response->json();

        if (
            ! is_array($payload)
            || ! isset($payload[0])
            || ! is_array($payload[0])
        ) {
            throw new RuntimeException(
                __('commerce_settings.currencies.errors.invalid_nbp_response')
            );
        }

        $table = $payload[0];

        $effectiveDate = trim(
            (string) (
                $table['effectiveDate']
                ?? ''
            )
        );

        $rates = collect(
            $table['rates'] ?? []
        )
            ->filter(
                fn ($rate) =>
                    is_array($rate)
                    && filled($rate['code'] ?? null)
                    && is_numeric($rate['mid'] ?? null)
            )
            ->keyBy(
                fn (array $rate): string =>
                    strtoupper(
                        (string) $rate['code']
                    )
            );

        if (
            $effectiveDate === ''
            || $rates->isEmpty()
        ) {
            throw new RuntimeException(
                __('commerce_settings.currencies.errors.invalid_nbp_response')
            );
        }

        $requestedCodes = $currencies
            ->pluck('code')
            ->map(
                fn (string $code): string =>
                    strtoupper($code)
            )
            ->values();

        $missing = $requestedCodes
            ->reject(
                fn (string $code): bool =>
                    $rates->has($code)
            )
            ->values();

        if ($missing->isNotEmpty()) {
            throw new RuntimeException(
                __('commerce_settings.currencies.errors.missing_nbp_rates', [
                    'codes' => $missing->implode(', '),
                ])
            );
        }

        $fetchedAt = now();

        DB::transaction(
            function () use (
                $currencies,
                $rates,
                $effectiveDate,
                $fetchedAt
            ): void {
                foreach ($currencies as $currency) {
                    $rate = $rates->get(
                        strtoupper($currency->code)
                    );

                    $normalized = number_format(
                        (float) $rate['mid'],
                        8,
                        '.',
                        ''
                    );

                    CurrencyRate::query()
                        ->updateOrCreate(
                            [
                                'currency_id' =>
                                    $currency->id,
                                'effective_date' =>
                                    $effectiveDate,
                                'source' => 'nbp',
                            ],
                            [
                                'rate_to_base' =>
                                    $normalized,
                                'is_manual' => false,
                                'fetched_at' =>
                                    $fetchedAt,
                            ]
                        );
                }
            }
        );

        return [
            'count' => $currencies->count(),
            'effective_date' => $effectiveDate,
            'codes' => $requestedCodes->all(),
        ];
    }

    /**
     * @return Collection<int, Currency>
     */
    private function foreignCurrencies(): Collection
    {
        return $this->settings
            ->currencies(enabledOnly: true)
            ->reject(
                fn (Currency $currency): bool =>
                    $currency->code
                    === CurrencySettingsService
                        ::BASE_CURRENCY
            )
            ->values();
    }

    private function ensureSuccess(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            __('commerce_settings.currencies.errors.nbp_http', [
                'status' => $response->status(),
            ])
        );
    }
}
