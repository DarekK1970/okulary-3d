<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\User;
use App\Services\FurgonetkaApiService;
use App\Services\FurgonetkaSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    private function settings(): FurgonetkaSettingsService
    {
        $settings = app(FurgonetkaSettingsService::class);

        foreach ([
            ['enabled', '1', false],
            ['client_id', 'client-id', true],
            ['client_secret', 'client-secret', true],
            ['access_token', 'access-token', true],
            ['refresh_token', 'refresh-token', true],
            ['token_expires_at', now()->addDays(10)->toIso8601String(), false],
            ['sender.name', 'Jan Nadawca', false],
            ['sender.company', 'Elverre', false],
            ['sender.email', 'sender@example.com', false],
            ['sender.phone', '500600700', false],
            ['sender.street', 'Testowa 1', false],
            ['sender.city', 'Toruń', false],
            ['sender.postcode', '87-100', false],
            ['sender.country_code', 'PL', false],
        ] as [$key, $value, $secret]) {
            $settings->set($key, $value, $secret);
        }

        return $settings;
    }

    private function order(string $method = 'courier'): Order
    {
        return Order::create([
            'number' => 'ORD-FURG-001',
            'public_token' => '00000000-0000-4000-8000-000000000777',
            'locale' => 'pl',
            'status' => OrderStatus::Pending,
            'currency' => 'PLN',
            'base_currency' => 'PLN',
            'exchange_rate' => '1.00000000',
            'currency_markup_percent' => '0.00',
            'subtotal_gross' => '100.00',
            'shipping_gross' => '18.99',
            'shipping_base_gross' => '18.99',
            'shipping_base_before_margin' => '18.99',
            'shipping_logistics_margin_percent' => '0.00',
            'shipping_method' => $method,
            'shipping_name_snapshot' => 'Kurier',
            'shipping_weight_grams' => 1200,
            'payment_method' => 'bank_transfer',
            'payment_status' => PaymentStatus::Paid,
            'total_gross' => '118.99',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '600700800',
            'customer_first_name' => 'Anna',
            'customer_last_name' => 'Nowak',
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
            'placed_at' => now(),
        ]);
    }

    public function test_admin_can_open_furgonetka_settings(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/shipping/furgonetka')
            ->assertOk()
            ->assertSee('Furgonetka.pl');
    }

    public function test_connection_test_reads_account_services(): void
    {
        $this->settings();

        Http::fake([
            'https://api.furgonetka.pl/account/services' =>
                Http::response([
                    'services' => [[
                        'id' => 123,
                        'service' => 'dpd',
                        'owner' => 'furgonetka',
                        'name' => 'DPD',
                    ]],
                ], 200),
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/shipping/furgonetka/test')
            ->assertSessionHas('status');

        Http::assertSent(
            fn ($request) =>
                $request->hasHeader(
                    'Authorization',
                    'Bearer access-token'
                )
        );
    }

    public function test_api_validates_and_creates_courier_package(): void
    {
        $this->settings();
        $order = $this->order();

        Http::fake([
            'https://api.furgonetka.pl/packages/validate' =>
                Http::response([], 204),
            'https://api.furgonetka.pl/packages' =>
                Http::response([
                    'package_id' => '90014858123',
                    'service' => 'dpd',
                    'service_id' => 123,
                    'state' => 'waiting',
                ], 200),
        ]);

        $result = app(FurgonetkaApiService::class)
            ->createPackage($order, 123);

        $this->assertSame(
            '90014858123',
            $result['response']['package_id']
        );
    }

    public function test_pickup_point_is_guarded_from_door_delivery(): void
    {
        $this->settings();

        $this->expectException(
            \RuntimeException::class
        );

        app(FurgonetkaApiService::class)
            ->createPackage(
                $this->order('parcel_locker'),
                123
            );
    }

    public function test_order_page_lists_existing_shipment(): void
    {
        $this->settings();
        $order = $this->order();

        OrderShipment::create([
            'order_id' => $order->id,
            'provider' => 'furgonetka',
            'external_package_id' => '90014858123',
            'service_id' => 123,
            'carrier' => 'dpd',
            'state' => 'waiting',
        ]);

        Http::fake([
            'https://api.furgonetka.pl/account/services' =>
                Http::response(['services' => []], 200),
        ]);

        $this->actingAs($this->admin())
            ->get(
                '/admin/orders/'
                . $order->id
                . '/shipping/furgonetka'
            )
            ->assertOk()
            ->assertSee('90014858123')
            ->assertSee('DPD');
    }
}
