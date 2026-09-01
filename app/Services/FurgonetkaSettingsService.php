<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class FurgonetkaSettingsService
{
    private const GROUP = 'furgonetka';

    private ?Collection $cache = null;

    public function get(
        string $key,
        ?string $default = null
    ): ?string {
        $setting = $this
            ->settings()
            ->get($key);

        return $setting?->value
            ?? $default;
    }

    public function set(
        string $key,
        ?string $value,
        bool $secret = false
    ): void {
        AppSetting::query()
            ->updateOrCreate(
                [
                    'group' =>
                        self::GROUP,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'is_secret' =>
                        $secret,
                ]
            );

        $this->cache = null;
    }

    public function enabled(): bool
    {
        return filter_var(
            $this->get(
                'enabled',
                '0'
            ),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function universalToken(): ?string
    {
        return $this->get(
            'universal_token'
        );
    }

    public function hasUniversalToken(): bool
    {
        return filled(
            $this->universalToken()
        );
    }

    public function generateUniversalToken(): string
    {
        $token = bin2hex(
            random_bytes(32)
        );

        $this->set(
            'universal_token',
            $token,
            true
        );

        $this->removeLegacyOAuthCredentials();

        return $token;
    }

    public function mapApiKey(): ?string
    {
        return $this->get(
            'map_api_key'
        );
    }

    public function mapEnabled(): bool
    {
        return $this->enabled()
            && filled(
                $this->mapApiKey()
            );
    }

    public function integrationBaseUrl(): string
    {
        return rtrim(
            (string) config(
                'app.url'
            ),
            '/'
        );
    }

    public function ordersUrl(): string
    {
        return $this
            ->integrationBaseUrl()
            . '/orders';
    }

    public function trackingUrlTemplate(): string
    {
        return $this
            ->integrationBaseUrl()
            . '/orders/{id}/tracking_number';
    }

    public function removeLegacyOAuthCredentials(): void
    {
        AppSetting::query()
            ->where(
                'group',
                self::GROUP
            )
            ->whereIn(
                'key',
                [
                    'client_id',
                    'client_secret',
                    'access_token',
                    'refresh_token',
                    'token_expires_at',
                ]
            )
            ->delete();

        $this->cache = null;
    }

    private function settings(): Collection
    {
        if (
            $this->cache
            === null
        ) {
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
