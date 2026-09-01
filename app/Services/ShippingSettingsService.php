<?php

namespace App\Services;

use App\Models\AppSetting;

class ShippingSettingsService
{
    private const GROUP = 'shipping';

    public const DOMESTIC_COUNTRY = 'PL';

    public function logisticsMarginPercent(): string
    {
        return AppSetting::query()
            ->where('group', self::GROUP)
            ->where(
                'key',
                'logistics_margin_percent'
            )
            ->first()
            ?->value
            ?? '10.00';
    }

    public function saveLogisticsMargin(
        string $value
    ): void {
        AppSetting::query()->updateOrCreate(
            [
                'group' => self::GROUP,
                'key' =>
                    'logistics_margin_percent',
            ],
            [
                'value' => $value,
                'is_secret' => false,
            ]
        );
    }

    /**
     * Apply the logistics margin only to shipments outside Poland.
     *
     * Values are kept in minor units so the calculation can be reused
     * safely by K87.2 before currency conversion.
     */
    public function applyLogisticsMarginCents(
        int $baseCents,
        string $countryCode
    ): int {
        if (
            strtoupper($countryCode)
            === self::DOMESTIC_COUNTRY
        ) {
            return $baseCents;
        }

        $marginBasisPoints = (int) round(
            max(
                0,
                (float) $this
                    ->logisticsMarginPercent()
            ) * 100,
            0,
            PHP_ROUND_HALF_UP
        );

        return intdiv(
            ($baseCents * (
                10000 + $marginBasisPoints
            )) + 5000,
            10000
        );
    }
}
