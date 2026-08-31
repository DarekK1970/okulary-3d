<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\User;
use App\Services\CurrencySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    /**
     * Existing commerce fields are intentionally present because
     * currencies share the established /admin/settings form.
     *
     * @return array<string, mixed>
     */
    private function commercePayload(): array
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
        ];
    }

    public function test_default_currencies_are_seeded(): void
    {
        $this->assertSame(
            4,
            Currency::query()->count()
        );

        $this->assertSame(
            [
                'PLN',
                'EUR',
                'GBP',
                'USD',
            ],
            Currency::query()
                ->orderBy('sort_order')
                ->pluck('code')
                ->all()
        );

        $pln = Currency::query()
            ->where('code', 'PLN')
            ->firstOrFail();

        $this->assertTrue($pln->is_base);
        $this->assertTrue($pln->is_enabled);
    }

    public function test_currency_panel_is_visible_in_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Waluty i kursy walut')
            ->assertSee('PLN')
            ->assertSee('EUR')
            ->assertSee('GBP')
            ->assertSee('USD')
            ->assertSee(
                'Pobieraj kursy walut automatycznie'
            );
    }

    public function test_super_admin_can_save_currency_configuration_and_manual_rates(): void
    {
        $payload = [
            ...$this->commercePayload(),
            'currency_settings_present' => '1',
            'enabled_currencies' => [
                'PLN',
                'EUR',
                'GBP',
            ],
            'default_currency' => 'EUR',
            'currency_auto_update' => '1',
            'currency_update_time' => '06:00',
            'currency_markup_percent' => '1.50',
            'manual_rates' => [
                'EUR' => '4.25000000',
                'GBP' => '4.95000000',
            ],
        ];

        $this->actingAs($this->superAdmin())
            ->put('/admin/settings', $payload)
            ->assertSessionHasNoErrors();

        $service = app(
            CurrencySettingsService::class
        );

        $this->assertSame(
            'EUR',
            $service->defaultCode()
        );

        $this->assertTrue(
            $service->autoUpdateEnabled()
        );

        $this->assertSame(
            '06:00',
            $service->updateTime()
        );

        $this->assertSame(
            '1.50',
            $service->markupPercent()
        );

        $this->assertTrue(
            Currency::query()
                ->where('code', 'PLN')
                ->value('is_enabled')
        );

        $this->assertFalse(
            (bool) Currency::query()
                ->where('code', 'USD')
                ->value('is_enabled')
        );

        $eur = Currency::query()
            ->where('code', 'EUR')
            ->firstOrFail();

        $this->assertDatabaseHas(
            'currency_rates',
            [
                'currency_id' => $eur->id,
                'rate_to_base' => '4.25000000',
                'source' => 'manual',
                'is_manual' => 1,
            ]
        );
    }

    public function test_base_currency_cannot_be_disabled(): void
    {
        $payload = [
            ...$this->commercePayload(),
            'currency_settings_present' => '1',
            'enabled_currencies' => [
                'EUR',
            ],
            'default_currency' => 'EUR',
            'currency_update_time' => '06:00',
            'currency_markup_percent' => '0.00',
        ];

        $this->actingAs($this->superAdmin())
            ->put('/admin/settings', $payload)
            ->assertSessionHasNoErrors();

        $pln = Currency::query()
            ->where('code', 'PLN')
            ->firstOrFail();

        $this->assertTrue($pln->is_enabled);
        $this->assertTrue($pln->is_base);
    }

    public function test_default_currency_must_be_enabled(): void
    {
        $payload = [
            ...$this->commercePayload(),
            'currency_settings_present' => '1',
            'enabled_currencies' => [
                'PLN',
                'EUR',
            ],
            'default_currency' => 'USD',
            'currency_update_time' => '06:00',
            'currency_markup_percent' => '0.00',
        ];

        $this->actingAs($this->superAdmin())
            ->put('/admin/settings', $payload)
            ->assertSessionHasErrors(
                'default_currency'
            );
    }
}
