<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\CommerceSettingsService;
use App\Services\PaymentMethodService;
use App\Services\PayNowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayNowCurrencyCapabilityTest extends TestCase
{
    use RefreshDatabase;

    private function configurePayNow(): CommerceSettingsService
    {
        $settings = app(
            CommerceSettingsService::class
        );

        $settings->set(
            'paynow.enabled',
            '1'
        );
        $settings->set(
            'paynow.sandbox',
            '1'
        );
        $settings->set(
            'paynow.api_key',
            'test-api-key',
            true
        );
        $settings->set(
            'paynow.signature_key',
            'test-signature-key',
            true
        );
        $settings->set(
            'paynow.timeout',
            '15'
        );

        return $settings;
    }

    private function order(
        string $currency,
        string $total = '31.23'
    ): Order {
        return Order::create([
            'number' =>
                'ORD-CUR-' . $currency,
            'public_token' =>
                '00000000-0000-4000-8000-'
                . match ($currency) {
                    'PLN' => '000000000001',
                    'EUR' => '000000000002',
                    'GBP' => '000000000003',
                    default => '000000000004',
                },
            'locale' => 'pl',
            'status' => OrderStatus::Pending,
            'currency' => $currency,
            'base_currency' => 'PLN',
            'exchange_rate' =>
                $currency === 'PLN'
                    ? '1.00000000'
                    : '4.00000000',
            'currency_markup_percent' =>
                $currency === 'PLN'
                    ? '0.00'
                    : '5.00',
            'subtotal_gross' => $total,
            'shipping_gross' => '0.00',
            'shipping_method' => 'pickup',
            'shipping_name_snapshot' =>
                'Odbiór osobisty',
            'payment_method' => 'paynow',
            'payment_status' =>
                PaymentStatus::Unpaid,
            'total_gross' => $total,
            'customer_email' =>
                'buyer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' =>
                'Kowalski',
            'billing_address_line1' =>
                'Testowa 1',
            'billing_postal_code' =>
                '87-100',
            'billing_city' => 'Toruń',
            'billing_country_code' => 'PL',
            'shipping_same_as_billing' =>
                true,
            'shipping_first_name' => 'Jan',
            'shipping_last_name' =>
                'Kowalski',
            'shipping_address_line1' =>
                'Testowa 1',
            'shipping_postal_code' =>
                '87-100',
            'shipping_city' => 'Toruń',
            'shipping_country_code' =>
                'PL',
            'placed_at' => now(),
        ]);
    }

    public function test_pln_is_available_when_paynow_is_enabled(): void
    {
        $this->configurePayNow();

        $methods = app(
            PaymentMethodService::class
        )->available('pl', 'PLN');

        $this->assertArrayHasKey(
            'paynow',
            $methods
        );
    }

    public function test_foreign_currencies_are_disabled_by_default(): void
    {
        $settings =
            $this->configurePayNow();

        foreach (
            ['EUR', 'GBP', 'USD']
            as $currency
        ) {
            $this->assertFalse(
                $settings
                    ->payNowSupportsCurrency(
                        $currency
                    )
            );

            $this->assertArrayNotHasKey(
                'paynow',
                app(
                    PaymentMethodService::class
                )->available(
                    'pl',
                    $currency
                )
            );
        }
    }

    public function test_merchant_can_enable_eur_after_activation(): void
    {
        $settings =
            $this->configurePayNow();

        $settings->set(
            'paynow.currency.EUR.enabled',
            '1'
        );

        $this->assertTrue(
            $settings
                ->payNowSupportsCurrency(
                    'EUR'
                )
        );

        $this->assertArrayHasKey(
            'paynow',
            app(
                PaymentMethodService::class
            )->available('pl', 'EUR')
        );
    }

    public function test_paynow_sends_eur_and_minor_unit_amount(): void
    {
        $settings =
            $this->configurePayNow();

        $settings->set(
            'paynow.currency.EUR.enabled',
            '1'
        );

        Http::fake([
            'https://api.sandbox.paynow.pl/v3/payments' =>
                Http::response([
                    'redirectUrl' =>
                        'https://example.test/paynow',
                    'paymentId' => 'PAY-EUR-001',
                    'status' => 'NEW',
                ], 201),
        ]);

        app(PayNowService::class)
            ->start(
                $this->order(
                    'EUR',
                    '31.23'
                )
            );

        Http::assertSent(
            function ($request): bool {
                $payload =
                    $request->data();

                return (
                    $payload['currency']
                    ?? null
                ) === 'EUR'
                    && (
                        $payload['amount']
                        ?? null
                    ) === 3123;
            }
        );
    }

    public function test_paynow_rejects_eur_without_capability(): void
    {
        $this->configurePayNow();

        $this->expectException(
            \RuntimeException::class
        );

        app(PayNowService::class)
            ->start(
                $this->order('EUR')
            );
    }
}
