<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class CommerceSettingsService
{
    private const GROUP = 'commerce';

    public const PAYNOW_FOREIGN_CURRENCIES = [
        'EUR',
        'GBP',
        'USD',
    ];

    /**
     * @var Collection<string, AppSetting>|null
     */
    private ?Collection $cache = null;

    public function get(
        string $key,
        ?string $default = null
    ): ?string {
        $setting = $this->settings()->get($key);

        if (! $setting) {
            return $default;
        }

        return $setting->value ?? $default;
    }

    public function bool(
        string $key,
        bool $default = false
    ): bool {
        $value = $this->get(
            $key,
            $default ? '1' : '0'
        );

        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function int(
        string $key,
        int $default
    ): int {
        return (int) (
            $this->get(
                $key,
                (string) $default
            ) ?? $default
        );
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

        $this->flush();
    }

    /**
     * @param array<string, string|null> $values
     * @param list<string> $secretKeys
     */
    public function setMany(
        array $values,
        array $secretKeys = []
    ): void {
        foreach (
            $values
            as $key => $value
        ) {
            $this->set(
                $key,
                $value,
                in_array(
                    $key,
                    $secretKeys,
                    true
                )
            );
        }
    }

    public function has(string $key): bool
    {
        return $this->settings()->has(
            $key
        );
    }

    public function payNowEnabled(): bool
    {
        return $this->bool(
            'paynow.enabled',
            false
        )
            && filled(
                $this->payNowApiKey()
            )
            && filled(
                $this->payNowSignatureKey()
            );
    }

    public function payNowSandbox(): bool
    {
        return $this->bool(
            'paynow.sandbox',
            true
        );
    }

    public function payNowApiKey(): ?string
    {
        return $this->get(
            'paynow.api_key'
        );
    }

    public function payNowSignatureKey(): ?string
    {
        return $this->get(
            'paynow.signature_key'
        );
    }

    public function payNowTimeout(): int
    {
        return max(
            3,
            min(
                60,
                $this->int(
                    'paynow.timeout',
                    15
                )
            )
        );
    }

    public function payNowSupportsCurrency(
        string $currency
    ): bool {
        $currency = strtoupper(
            trim($currency)
        );

        if (! $this->payNowEnabled()) {
            return false;
        }

        if ($currency === 'PLN') {
            return true;
        }

        if (! in_array(
            $currency,
            self::PAYNOW_FOREIGN_CURRENCIES,
            true
        )) {
            return false;
        }

        return $this->bool(
            'paynow.currency.'
            . $currency
            . '.enabled',
            false
        );
    }

    /**
     * @return list<string>
     */
    public function payNowEnabledCurrencies(): array
    {
        $currencies = [];

        if ($this->payNowEnabled()) {
            $currencies[] = 'PLN';
        }

        foreach (
            self::PAYNOW_FOREIGN_CURRENCIES
            as $currency
        ) {
            if (
                $this->payNowSupportsCurrency(
                    $currency
                )
            ) {
                $currencies[] = $currency;
            }
        }

        return $currencies;
    }

    /**
     * @return array<string, string|null>
     */
    public function bankTransfer(): array
    {
        return [
            'recipient' =>
                $this->get(
                    'bank.recipient'
                ),
            'bank_name' =>
                $this->get(
                    'bank.name'
                ),
            'account' =>
                $this->get(
                    'bank.account'
                ),
            'swift' =>
                $this->get(
                    'bank.swift'
                ),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function seller(): array
    {
        return [
            'name' => $this->get(
                'seller.name',
                'Wortal Okulary 3D'
            ),
            'address' =>
                $this->get(
                    'seller.address'
                ),
            'tax_id' =>
                $this->get(
                    'seller.tax_id'
                ),
            'email' =>
                $this->get(
                    'seller.email'
                ),
        ];
    }

    public function maskedSecret(
        string $key
    ): string {
        $value = $this->get($key);

        if (blank($value)) {
            return '';
        }

        $length = mb_strlen($value);

        if ($length <= 8) {
            return str_repeat(
                '•',
                $length
            );
        }

        return mb_substr(
            $value,
            0,
            4
        )
            . str_repeat(
                '•',
                min(
                    16,
                    $length - 8
                )
            )
            . mb_substr(
                $value,
                -4
            );
    }

    public function flush(): void
    {
        $this->cache = null;
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
