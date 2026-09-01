<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Mail\PaymentConfirmedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\SalesDocument;
use App\Models\User;
use App\Services\CommerceSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function variant(): ProductVariant
    {
        $category = ProductCategory::create([
            'source_locale' => 'pl',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'pl',
            'name' => 'Okulary 3D',
            'slug' => 'okulary-3d',
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Active,
            'brand' => 'Elverre',
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'pl',
            'name' => 'Okulary testowe',
            'slug' => 'okulary-testowe',
            'description_html' => '<p>Opis.</p>',
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PAY-TEST-001',
            'name' => 'Standard',
            'price_gross' => 19.99,
            'vat_rate' => 23,
            'currency' => 'PLN',
            'stock_quantity' => 20,
            'weight_grams' => 500,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_email' => 'buyer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'customer_phone' => '123456789',

            'billing_company' => '',
            'billing_tax_id' => '',
            'billing_address_line1' => 'ul. Testowa 1',
            'billing_address_line2' => '',
            'billing_postal_code' => '87-100',
            'billing_city' => 'Toruń',
            'billing_country_code' => 'PL',

            'shipping_same_as_billing' => '1',
            'shipping_first_name' => '',
            'shipping_last_name' => '',
            'shipping_company' => '',
            'shipping_address_line1' => '',
            'shipping_address_line2' => '',
            'shipping_postal_code' => '',
            'shipping_city' => '',
            'shipping_country_code' => 'PL',

            'shipping_method' => 'courier',
            'shipping_point' => '',
            'payment_method' => 'bank_transfer',
            'customer_note' => '',
            'accept_terms' => '1',
        ], $overrides);
    }

    public function test_shipping_cost_is_added_and_document_is_created(): void
    {
        $variant = $this->variant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/pl/checkout', $this->payload())
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame('19.99', $order->subtotal_gross);
        $this->assertSame('18.99', $order->shipping_gross);
        $this->assertSame('38.98', $order->total_gross);
        $this->assertSame('courier', $order->shipping_method);
        $this->assertSame(
            PaymentStatus::Unpaid,
            $order->payment_status
        );

        $this->assertDatabaseHas('sales_documents', [
            'order_id' => $order->id,
            'type' => SalesDocument::TYPE_ORDER_CONFIRMATION,
            'total_gross' => '38.98',
        ]);
    }

    public function test_parcel_locker_requires_pickup_point(): void
    {
        $variant = $this->variant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/pl/checkout', $this->payload([
            'shipping_method' => 'parcel_locker',
            'shipping_point' => '',
        ]))
            ->assertSessionHasErrors('shipping_point');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unpaid_order_cannot_start_processing(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $variant = $this->variant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/pl/checkout', $this->payload());

        $order = Order::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch('/admin/orders/' . $order->id . '/status', [
                'status' => OrderStatus::Processing->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(
            OrderStatus::Pending,
            $order->fresh()->status
        );
    }

    public function test_admin_can_mark_bank_transfer_paid_and_process_order(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $variant = $this->variant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/pl/checkout', $this->payload());

        $order = Order::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch('/admin/orders/' . $order->id . '/payment', [
                'payment_action' => 'paid',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            PaymentStatus::Paid,
            $order->fresh()->payment_status
        );

        Mail::assertSent(PaymentConfirmedMail::class);

        $this->actingAs($admin)
            ->patch('/admin/orders/' . $order->id . '/status', [
                'status' => OrderStatus::Processing->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            OrderStatus::Processing,
            $order->fresh()->status
        );
    }

    public function test_paynow_checkout_redirects_to_gateway(): void
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

        config()->set(
            'shop.payments.paynow.active',
            true
        );

        Http::fake([
            'https://api.sandbox.paynow.pl/v3/payments' =>
                Http::response([
                    'redirectUrl' => 'https://example.test/paynow',
                    'paymentId' => 'ABCD-123-456-789',
                    'status' => 'NEW',
                ], 201),
        ]);

        $variant = $this->variant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/pl/checkout', $this->payload([
            'shipping_method' => 'pickup',
            'payment_method' => 'paynow',
        ]))
            ->assertRedirect('https://example.test/paynow');

        $order = Order::query()->firstOrFail();

        $this->assertSame(
            PaymentStatus::Pending,
            $order->payment_status
        );

        $this->assertSame(
            'ABCD-123-456-789',
            $order->payment_external_id
        );

        $this->assertNotNull(
            $order->payment_merchant_external_id
        );

        $this->assertNotNull(
            $order->payment_idempotency_key
        );
    }

    public function test_valid_paynow_notification_marks_order_paid(): void
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
            'notification-secret',
            true
        );

        $variant = $this->variant();

        $order = Order::create([
            'number' => 'ORD-TEST-001',
            'public_token' => '00000000-0000-4000-8000-000000000001',
            'locale' => 'pl',
            'status' => OrderStatus::Pending,
            'currency' => 'PLN',
            'subtotal_gross' => '19.99',
            'shipping_gross' => '0.00',
            'shipping_method' => 'pickup',
            'shipping_name_snapshot' => 'Odbiór osobisty',
            'payment_method' => 'paynow',
            'payment_status' => PaymentStatus::Pending,
            'payment_merchant_external_id' => 'merchant-order-001',
            'payment_external_id' => 'ABCD-123-456-789',
            'total_gross' => '19.99',
            'customer_email' => 'buyer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'billing_address_line1' => 'Testowa 1',
            'billing_postal_code' => '87-100',
            'billing_city' => 'Toruń',
            'billing_country_code' => 'PL',
            'shipping_same_as_billing' => true,
            'shipping_first_name' => 'Jan',
            'shipping_last_name' => 'Kowalski',
            'shipping_address_line1' => 'Testowa 1',
            'shipping_postal_code' => '87-100',
            'shipping_city' => 'Toruń',
            'shipping_country_code' => 'PL',
            'placed_at' => now(),
        ]);

        $body = json_encode([
            'paymentId' => 'ABCD-123-456-789',
            'externalId' => 'merchant-order-001',
            'status' => 'CONFIRMED',
            'modifiedAt' => now()->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES);

        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $body,
                'notification-secret',
                true
            )
        );

        $response = $this->call(
            'POST',
            '/payments/paynow/notification',
            [],
            [],
            [],
            [
                'HTTP_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );

        $response->assertStatus(202);

        $this->assertSame(
            PaymentStatus::Paid,
            $order->fresh()->payment_status
        );

        Mail::assertSent(PaymentConfirmedMail::class);
    }

    public function test_public_order_document_can_be_opened_by_token(): void
    {
        $variant = $this->variant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/pl/checkout', $this->payload());

        $order = Order::query()
            ->with('salesDocuments')
            ->firstOrFail();

        $document = $order->salesDocuments->firstOrFail();

        $this->get(
            '/pl/order/'
            . $order->public_token
            . '/document/'
            . $document->id
        )
            ->assertOk()
            ->assertSee($document->number)
            ->assertSee($order->number);
    }
}
