<?php

namespace App\Console\Commands;

use App\Services\CurrencySettingsService;
use App\Services\NbpCurrencyRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateCurrencyRates extends Command
{
    protected $signature =
        'currency:rates-update
        {--scheduled : Run only when automatic updates are enabled and the configured HH:MM matches the current time}';

    protected $description =
        'Pobierz najnowsze kursy walut z tabeli A NBP';

    public function handle(
        CurrencySettingsService $settings,
        NbpCurrencyRateService $rates
    ): int {
        if ($this->option('scheduled')) {
            if (! $settings->autoUpdateEnabled()) {
                return self::SUCCESS;
            }

            if (
                now()->format('H:i')
                !== $settings->updateTime()
            ) {
                return self::SUCCESS;
            }
        }

        try {
            $result = $rates->refresh();
        } catch (\Throwable $exception) {
            Log::error(
                'Currency rates update failed.',
                [
                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        if ($result['count'] === 0) {
            $this->info(
                'Brak aktywnych walut obcych do aktualizacji.'
            );

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Zapisano %d kurs(y) NBP. Data tabeli: %s. Waluty: %s.',
            $result['count'],
            $result['effective_date'] ?? '—',
            implode(', ', $result['codes'])
        ));

        return self::SUCCESS;
    }
}
