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
        $setting = $this->settings()->get($key);

        return $setting?->value ?? $default;
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

    public function enabled(): bool
    {
        return filter_var(
            $this->get('enabled', '0'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function clientId(): ?string
    {
        return $this->get('client_id');
    }

    public function clientSecret(): ?string
    {
        return $this->get('client_secret');
    }

    public function accessToken(): ?string
    {
        return $this->get('access_token');
    }

    public function refreshToken(): ?string
    {
        return $this->get('refresh_token');
    }

    public function tokenExpiresAt(): ?string
    {
        return $this->get('token_expires_at');
    }

    public function connected(): bool
    {
        return filled($this->accessToken())
            && filled($this->refreshToken());
    }

    public function mapApiKey(): ?string
    {
        return $this->get('map_api_key');
    }

    public function mapEnabled(): bool
    {
        return $this->enabled()
            && filled($this->mapApiKey());
    }

    public function sender(): array
    {
        return [
            'name' => $this->get('sender.name'),
            'company' => $this->get('sender.company'),
            'email' => $this->get('sender.email'),
            'phone' => $this->get('sender.phone'),
            'street' => $this->get('sender.street'),
            'city' => $this->get('sender.city'),
            'country_code' => strtoupper(
                $this->get('sender.country_code', 'PL')
                ?? 'PL'
            ),
            'postcode' => $this->get('sender.postcode'),
            'county' => $this->get('sender.county', ''),
        ];
    }

    public function parcelDefaults(): array
    {
        return [
            'width' => max(
                1,
                (int) ($this->get('parcel.width_cm', '30') ?? 30)
            ),
            'height' => max(
                1,
                (int) ($this->get('parcel.height_cm', '20') ?? 20)
            ),
            'depth' => max(
                1,
                (int) ($this->get('parcel.depth_cm', '40') ?? 40)
            ),
        ];
    }

    public function labelFormat(): string
    {
        return in_array(
            $this->get('label.format', 'pdf'),
            ['pdf', 'zpl', 'epl'],
            true
        )
            ? (string) $this->get('label.format', 'pdf')
            : 'pdf';
    }

    public function labelPageFormat(): string
    {
        return in_array(
            $this->get('label.page_format', 'a6'),
            ['a4', 'a6'],
            true
        )
            ? (string) $this->get('label.page_format', 'a6')
            : 'a6';
    }

    public function authorizationCallbackUrl(): string
    {
        return route(
            'admin.shipping.furgonetka.callback'
        );
    }

    public function masked(
        string $key
    ): string {
        $value = $this->get($key);

        if (blank($value)) {
            return '';
        }

        $length = mb_strlen($value);

        if ($length <= 8) {
            return str_repeat('•', $length);
        }

        return mb_substr($value, 0, 4)
            . str_repeat(
                '•',
                min(16, $length - 8)
            )
            . mb_substr($value, -4);
    }

    public function saveTokens(
        string $accessToken,
        ?string $refreshToken,
        int $expiresIn
    ): void {
        $this->set(
            'access_token',
            $accessToken,
            true
        );

        if (filled($refreshToken)) {
            $this->set(
                'refresh_token',
                $refreshToken,
                true
            );
        }

        $this->set(
            'token_expires_at',
            now()
                ->addSeconds(
                    max(60, $expiresIn)
                )
                ->toIso8601String()
        );
    }

    public function clearTokens(): void
    {
        foreach ([
            'access_token',
            'refresh_token',
            'token_expires_at',
        ] as $key) {
            AppSetting::query()
                ->where('group', self::GROUP)
                ->where('key', $key)
                ->delete();
        }

        $this->cache = null;
    }

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
