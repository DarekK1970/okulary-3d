<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\FurgonetkaSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FurgonetkaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function enableIntegration(): string
    {
        $settings = app(FurgonetkaSettingsService::class);
        $settings->set('enabled', '1');

        return $settings->generateUniversalToken();
    }

    private function order(): Order
    {
        $order = Order::create([
            'number' => 'ORD-UNIVERSAL-001',
            'public_token' => '00000000-0000-4000-8000-000000000991',
            'locale' => 'pl',
            'status' => OrderStatus::Processing,
            'currency' => 'EUR',
            'base_currency' => 'PLN',
            'exchange_rate' => '4.00000000',
            'currency_markup_percent' => '5.00',
            'subtotal_gross' => '26.25',
            'subtotal_base_gross' => '100.00',
            'shipping_gross' => '5.25',
            'shipping_base_gross' => '20.00',
            'shipping_base_before_margin' => '20.00',
            'shipping_logistics_margin_percent' => '0.00',
            'shipping_method' => 'courier',
            'shipping_name_snapshot' => 'Kurier',
            'shipping_weight_grams' => 1200,
            'payment_method' => 'bank_transfer',
            'payment_status' => PaymentStatus::Paid,
            'total_gross' => '31.50',
            'total_base_gross' => '120.00',
            'customer_email' => 'buyer@example.com',
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Nowak',
            'customer_phone' => '600700800',
            'billing_address_line1' => 'Odbiorcza 2',
            'billing_postal_code' => '00-001',
            'billing_city' => 'Warszawa',
            'billing_country_code' => 'PL',
            'shipping_same_as_billing' => true,
            'shipping_first_name' => 'Anna',
            'shipping_last_name' => 'Nowak',
            'shipping_address_line1' => 'Odbiorcza 2',
            'shipping_postal_code' => '00-001',
            'shipping_city' => 'Warszawa',
            'shipping_country_code' => 'PL',
            'customer_note' => 'Proszę ostrożnie.',
            'placed_at' => now()->subMinute(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_variant_id' => null,
            'sku_snapshot' => 'OK-3D-001',
            'product_name_snapshot' => 'Kartonowe okulary 3D',
            'variant_name_snapshot' => 'Czerwono-cyjanowe',
            'quantity' => 2,
            'unit_price_gross' => '13.13',
            'base_unit_price_gross' => '50.00',
            'vat_rate' => '23.00',
            'line_total_gross' => '26.25',
            'base_line_total_gross' => '100.00',
            'currency' => 'EUR',
            'base_currency' => 'PLN',
        ]);

        return $order->fresh();
    }

    public function test_admin_can_generate_universal_token(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/shipping/furgonetka/token')
            ->assertSessionHas('status');

        $settings = app(FurgonetkaSettingsService::class);
        $this->assertNotNull($settings->universalToken());
        $this->assertSame(64, strlen($settings->universalToken()));
    }

    public function test_orders_endpoint_rejects_missing_token(): void
    {
        $this->enableIntegration();
        $this->getJson('/orders')->assertUnauthorized();
    }

    public function test_furgonetka_can_pull_orders_with_raw_authorization_token(): void
    {
        $token = $this->enableIntegration();
        $order = $this->order();

        $response = $this
            ->withHeader('Authorization', $token)
            ->getJson('/orders?limit=30');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.sourceOrderId', $order->number)
            ->assertJsonPath('data.0.totalPrice', 120)
            ->assertJsonPath('data.0.shippingCost', 20)
            ->assertJsonPath('data.0.totalPaid', 120)
            ->assertJsonPath('data.0.totalWeight', 1.2)
            ->assertJsonPath('data.0.shippingAddress.countryCode', 'PL')
            ->assertJsonPath('data.0.products.0.sku', 'OK-3D-001')
            ->assertJsonPath('data.0.products.0.priceGross', 50);

        $response->assertJsonMissing(['totalPrice' => 31.5]);
    }

    public function test_local_pickup_is_not_exported(): void
    {
        $token = $this->enableIntegration();
        $order = $this->order();
        $order->update([
            'shipping_method' => 'pickup',
            'shipping_name_snapshot' => 'Odbiór osobisty',
        ]);

        $this->withHeader('Authorization', $token)
            ->getJson('/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_tracking_callback_updates_order_and_is_idempotent(): void
    {
        $token = $this->enableIntegration();
        $order = $this->order();
        $url = '/orders/' . $order->number . '/tracking_number';
        $payload = [
            'tracking' => [
                'number' => 'TEST123456789',
                'carrier' => 'InPost',
                'url' => 'https://example.com/track/TEST123456789',
                'id' => 'SHIP-001',
            ],
        ];

        $this->withHeader('Authorization', $token)
            ->postJson($url, $payload)
            ->assertNoContent();

        $order->refresh();
        $firstUpdatedAt = $order->shipping_tracking_updated_at?->toISOString();

        $this->assertSame('TEST123456789', $order->shipping_tracking_number);
        $this->assertSame('InPost', $order->shipping_carrier);
        $this->assertSame('SHIP-001', $order->shipping_external_id);

        $this->withHeader('Authorization', $token)
            ->postJson($url, $payload)
            ->assertNoContent();

        $order->refresh();
        $this->assertSame(
            $firstUpdatedAt,
            $order->shipping_tracking_updated_at?->toISOString()
        );
    }

    public function test_wrong_token_cannot_write_tracking(): void
    {
        $this->enableIntegration();
        $order = $this->order();

        $this->withHeader('Authorization', 'wrong-token')
            ->postJson(
                '/orders/' . $order->number . '/tracking_number',
                ['tracking' => ['number' => 'NOPE']]
            )
            ->assertUnauthorized();

        $this->assertNull(
            $order->fresh()->shipping_tracking_number
        );
    }
}
