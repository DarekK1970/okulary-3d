<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\SalesDocument;
use App\Services\CommerceSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCurrencyDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_confirmation_shows_currency_snapshot(): void
    {
        app(
            CommerceSettingsService::class
        )->set(
            'seller.name',
            'Wortal Okulary 3D'
        );

        $order = Order::create([
            'number' => 'ORD-DOC-EUR',
            'public_token' =>
                '00000000-0000-4000-8000-000000000055',
            'locale' => 'pl',
            'status' => OrderStatus::Pending,
            'currency' => 'EUR',
            'base_currency' => 'PLN',
            'exchange_rate' => '4.00000000',
            'exchange_rate_source' => 'nbp',
            'exchange_rate_effective_date' =>
                '2026-08-31',
            'currency_markup_percent' =>
                '5.00',
            'subtotal_gross' => '26.25',
            'subtotal_base_gross' =>
                '100.00',
            'shipping_gross' => '4.98',
            'shipping_base_gross' =>
                '18.99',
            'shipping_method' => 'courier',
            'shipping_name_snapshot' =>
                'Kurier',
            'payment_method' =>
                'bank_transfer',
            'payment_status' =>
                PaymentStatus::Unpaid,
            'total_gross' => '31.23',
            'total_base_gross' => '118.99',
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

        $document = SalesDocument::create([
            'order_id' => $order->id,
            'type' =>
                SalesDocument
                    ::TYPE_ORDER_CONFIRMATION,
            'number' => 'PZ/2026/000055',
            'currency' => 'EUR',
            'subtotal_gross' => '26.25',
            'shipping_gross' => '4.98',
            'total_gross' => '31.23',
            'buyer_name' => 'Jan Kowalski',
            'buyer_email' =>
                'buyer@example.com',
            'billing_address' =>
                "Testowa 1\n87-100 Toruń\nPL",
            'issued_at' => now(),
        ]);

        $response = $this->get(
            '/pl/order/'
            . $order->public_token
            . '/document/'
            . $document->id
        );

        $response
            ->assertOk()
            ->assertSee(
                'Rozliczenie walutowe zamówienia'
            )
            ->assertSee('4,00000000')
            ->assertSee('5,00%')
            ->assertSee('NBP')
            ->assertSee('Wartość bazowa:')
            ->assertSee('118,99')
            ->assertSee('PLN');
    }
}
