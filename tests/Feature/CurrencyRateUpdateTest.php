<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\User;
use App\Services\CurrencySettingsService;
use App\Services\NbpCurrencyRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyRateUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(): array
    {
        return [
            'paynow_timeout' => 15,
            'seller_name' => 'Wortal Okulary 3D',
            'bank_recipient' => '',
            'bank_name' => '',
            'bank_account' => '',
            'bank_swift' => '',
            'seller_address' => '',
            'seller_tax_id' => '',
            'seller_email' => '',

            'currency_settings_present' => '1',
            'enabled_currencies' => [
                'PLN',
                'EUR',
                'GBP',
                'USD',
            ],
            'default_currency' => 'PLN',
            'currency_auto_update' => '1',
            'currency_update_time' => '06:00',
            'currency_markup_percent' => '0.00',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function nbpResponse(): array
    {
        return [
            [
                'table' => 'A',
                'no' => '167/A/NBP/2026',
                'effectiveDate' => '2026-08-28',
                'rates' => [
                    [
                        'currency' => 'euro',
                        'code' => 'EUR',
                        'mid' => 4.3328,
                    ],
                    [
                        'currency' => 'funt szterling',
                        'code' => 'GBP',
                        'mid' => 5.0555,
                    ],
                    [
                        'currency' => 'dolar amerykański',
                        'code' => 'USD',
                        'mid' => 3.7209,
                    ],
                ],
            ],
        ];
    }

    private function fakeNbp(): void
    {
        Http::fake([
            NbpCurrencyRateService::ENDPOINT =>
                Http::response(
                    $this->nbpResponse(),
                    200
                ),
        ]);
    }

    public function test_nbp_service_stores_latest_table_a_rates(): void
    {
        $this->fakeNbp();

        $result = app(
            NbpCurrencyRateService::class
        )->refresh();

        $this->assertSame(3, $result['count']);
        $this->assertSame(
            '2026-08-28',
            $result['effective_date']
        );

        foreach (
            [
                'EUR' => '4.33280000',
                'GBP' => '5.05550000',
                'USD' => '3.72090000',
            ]
            as $code => $rate
        ) {
            $currency = Currency::query()
                ->where('code', $code)
                ->firstOrFail();

            $storedRate = CurrencyRate::query()
                ->where('currency_id', $currency->id)
                ->where('source', 'nbp')
                ->firstOrFail();

            $this->assertSame(
                $rate,
                $storedRate->rate_to_base
            );

            $this->assertSame(
                '2026-08-28',
                $storedRate->effective_date
                    ->toDateString()
            );

            $this->assertFalse(
                $storedRate->is_manual
            );
        }

        Http::assertSentCount(1);
    }

    public function test_super_admin_can_fetch_rates_from_settings_button(): void
    {
        $this->fakeNbp();

        $payload = [
            ...$this->settingsPayload(),
            'currency_refresh_now' => '1',
        ];

        $this->actingAs($this->superAdmin())
            ->put('/admin/settings', $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $eur = Currency::query()
            ->where('code', 'EUR')
            ->firstOrFail();

        $storedRate = CurrencyRate::query()
            ->where('currency_id', $eur->id)
            ->where('source', 'nbp')
            ->firstOrFail();

        $this->assertSame(
            '2026-08-28',
            $storedRate->effective_date
                ->toDateString()
        );

        Http::assertSentCount(1);
    }

    public function test_scheduled_command_runs_only_at_configured_time(): void
    {
        $settings = app(
            CurrencySettingsService::class
        );

        $settings->saveConfiguration(
            [
                'PLN',
                'EUR',
                'GBP',
                'USD',
            ],
            'PLN',
            true,
            '06:00',
            '0.00'
        );

        $this->fakeNbp();

        Carbon::setTestNow(
            '2026-08-31 05:59:00'
        );

        $this->artisan(
            'currency:rates-update',
            ['--scheduled' => true]
        )->assertSuccessful();

        Http::assertNothingSent();

        Carbon::setTestNow(
            '2026-08-31 06:00:00'
        );

        $this->artisan(
            'currency:rates-update',
            ['--scheduled' => true]
        )->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_scheduled_command_respects_auto_update_checkbox(): void
    {
        $settings = app(
            CurrencySettingsService::class
        );

        $settings->saveConfiguration(
            [
                'PLN',
                'EUR',
                'GBP',
                'USD',
            ],
            'PLN',
            false,
            '06:00',
            '0.00'
        );

        $this->fakeNbp();

        Carbon::setTestNow(
            '2026-08-31 06:00:00'
        );

        $this->artisan(
            'currency:rates-update',
            ['--scheduled' => true]
        )->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_admin_cannot_use_sensitive_currency_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->fakeNbp();

        $this->actingAs($admin)
            ->put(
                '/admin/settings',
                [
                    ...$this->settingsPayload(),
                    'currency_refresh_now' =>
                        '1',
                ]
            )
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
