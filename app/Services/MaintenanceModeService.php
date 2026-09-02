<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class MaintenanceModeService
{
    private const GROUP = 'maintenance';

    /**
     * @var Collection<string, AppSetting>|null
     */
    private ?Collection $cache = null;

    public function enabled(): bool
    {
        return filter_var(
            $this->get('enabled', '0'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @return list<string>
     */
    public function allowedIps(): array
    {
        $raw = $this->get('allowed_ips', '[]');
        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $ips = [];

        foreach ($decoded as $ip) {
            $ip = trim((string) $ip);

            if (
                $ip !== ''
                && filter_var($ip, FILTER_VALIDATE_IP) !== false
            ) {
                $ips[] = $ip;
            }
        }

        return array_values(array_unique($ips));
    }

    public function allowedIpText(): string
    {
        return implode(PHP_EOL, $this->allowedIps());
    }

    public function isIpAllowed(?string $ip): bool
    {
        if (
            blank($ip)
            || filter_var($ip, FILTER_VALIDATE_IP) === false
        ) {
            return false;
        }

        return in_array($ip, $this->allowedIps(), true);
    }

    /**
     * @param list<string> $allowedIps
     */
    public function save(bool $enabled, array $allowedIps): void
    {
        $normalizedIps = [];

        foreach ($allowedIps as $ip) {
            $ip = trim($ip);

            if (
                $ip !== ''
                && filter_var($ip, FILTER_VALIDATE_IP) !== false
            ) {
                $normalizedIps[] = $ip;
            }
        }

        $normalizedIps = array_values(array_unique($normalizedIps));

        $this->set('enabled', $enabled ? '1' : '0');
        $this->set(
            'allowed_ips',
            json_encode(
                $normalizedIps,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
            )
        );
    }

    public function flush(): void
    {
        $this->cache = null;
    }

    private function get(
        string $key,
        ?string $default = null
    ): ?string {
        $setting = $this->settings()->get($key);

        if (! $setting) {
            return $default;
        }

        return $setting->value ?? $default;
    }

    private function set(
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

        $this->flush();
    }

    /**
     * @return Collection<string, AppSetting>
     */
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
